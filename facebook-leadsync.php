<?php
session_start();

if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit;
}

include('db/config.php');

date_default_timezone_set('Asia/Kolkata');

$loginUser = mysqli_real_escape_string($db, $_SESSION["login_user"]);
$msg = "";

/* =====================================================
   FACEBOOK GRAPH API CALL (PRODUCTION SAFE - CURL)
===================================================== */
function fbCurl($url)
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ["error" => $error];
    }

    curl_close($ch);

    return json_decode($response, true);
}

/* =====================================================
   FETCH LEADS
===================================================== */
if (isset($_POST['action']) && $_POST['action'] == 'get_leads') {

    header('Content-Type: application/json');

    $pageId = mysqli_real_escape_string($db, $_POST['pageId']);

    $tokenQuery = mysqli_query(
        $db,
        "SELECT page_access_token FROM facebook_pages 
         WHERE page_id='$pageId' AND user_id='$loginUser' LIMIT 1"
    );

    if (!$tokenQuery || mysqli_num_rows($tokenQuery) == 0) {
        echo json_encode(["error" => "Page not connected"]);
        exit;
    }

    $tokenRow = mysqli_fetch_assoc($tokenQuery);
    $pageToken = $tokenRow['page_access_token'];

    /* -------- GET FORMS -------- */
    $formsUrl = "https://graph.facebook.com/v19.0/$pageId/leadgen_forms?access_token=$pageToken";
    $formsData = fbCurl($formsUrl);

    if (isset($formsData['error'])) {
        echo json_encode(["error" => $formsData['error']['message'] ?? "Facebook API error"]);
        exit;
    }

    if (!empty($formsData['data'])) {

        foreach ($formsData['data'] as $form) {

            $formId = $form['id'];
            $formName = mysqli_real_escape_string($db, $form['name']);

            $leadsUrl = "https://graph.facebook.com/v19.0/$formId/leads?access_token=$pageToken";
            $leadsData = fbCurl($leadsUrl);

            if (!empty($leadsData['data'])) {

                foreach ($leadsData['data'] as $lead) {

                    $leadId = mysqli_real_escape_string($db, $lead['id']);

                    /* ✅ UTC → IST Conversion */
                    $utcTime = new DateTime($lead['created_time'], new DateTimeZone('UTC'));
                    $utcTime->setTimezone(new DateTimeZone('Asia/Kolkata'));
                    $createdTime = $utcTime->format('Y-m-d H:i:s');

                    $fullName = '';
                    $email = '';
                    $phone = '';
                    $city = '';
                    $firstName = '';
                    $lastName = '';

                    if (!empty($lead['field_data'])) {
                        foreach ($lead['field_data'] as $field) {
                            $fieldName = $field['name'];
                            $value = mysqli_real_escape_string($db, $field['values'][0]);

                            if ($fieldName == 'full_name') $fullName = $value;
                            if ($fieldName == 'first_name') $firstName = $value;
                            if ($fieldName == 'last_name') $lastName = $value;
                            if ($fieldName == 'email') $email = $value;
                            if (in_array($fieldName, ['phone', 'phone_number'])) $phone = $value;
                            if ($fieldName == 'city') $city = $value;
                        }
                    }

                    /* Build full_name if missing */
                    if (empty($fullName) && (!empty($firstName) || !empty($lastName))) {
                        $fullName = trim($firstName . ' ' . $lastName);
                    }

                    /* -------- 1. SAVE TO facebook_leads (UNCHANGED) -------- */
                    $insertFbQuery = "
                        INSERT INTO facebook_leads
                        (user_id, page_id, form_name, lead_id, full_name, email, phone_number, city, created_time)
                        VALUES
                        ('$loginUser', '$pageId', '$formName', '$leadId', '$fullName', '$email', '$phone', '$city', '$createdTime')
                        ON DUPLICATE KEY UPDATE
                        full_name = VALUES(full_name),
                        email = VALUES(email),
                        phone_number = VALUES(phone_number),
                        city = VALUES(city)
                    ";
                    mysqli_query($db, $insertFbQuery);

                    /* -------- 2. ALSO SAVE TO admission_enquiry (NEW) -------- */
                   /* -------- 2. ALSO SAVE TO admission_enquiry (FIXED) -------- */
// Check if lead already exists in admission_enquiry (by phone/email)
$checkStmt = mysqli_prepare($db, "
    SELECT admission_id FROM admission_enquiry 
    WHERE mobile = ? OR email = ?
");
mysqli_stmt_bind_param($checkStmt, "ss", $phone, $email);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);

if (mysqli_stmt_num_rows($checkStmt) == 0) {
    // Insert new lead into admission_enquiry with status 'untouched'
    // Using actual columns from your table structure
    $insertAdmission = mysqli_prepare($db, "
        INSERT INTO admission_enquiry 
        (name, email, mobile, city, state, course_type, lead_status, date, remarks, source, user_id, status)
        VALUES (?, ?, ?, ?, '', '', 'untouched', ?, 'Imported from Facebook', 'facebook', ?, '1')
    ");
    mysqli_stmt_bind_param($insertAdmission, "sssssi", 
        $fullName, $email, $phone, $city, $createdTime, $loginUser
    );
    
    if (!mysqli_stmt_execute($insertAdmission)) {
        // Log error but don't break the flow
        error_log("Failed to insert lead: " . mysqli_stmt_error($insertAdmission));
    }
    mysqli_stmt_close($insertAdmission);
}
mysqli_stmt_close($checkStmt);
                }
            }
        }
    }

    /* -------- RETURN SAVED LEADS (from facebook_leads) -------- */
    $result = mysqli_query(
        $db,
        "SELECT * FROM facebook_leads 
         WHERE user_id='$loginUser' AND page_id='$pageId'
         ORDER BY created_time DESC"
    );

    $allLeads = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $allLeads[] = $row;
    }

    echo json_encode(["data" => $allLeads]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Facebook Leads</title>

    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        body {
            background-color: #f9fafb;
        }

        .card-header {
            font-weight: 600;
        }
    </style>
</head>

<body>

    <?php include("header.php"); ?>
    <?php include("navbar.php"); ?>

    <section class="pcoded-main-container">
        <div class="pcoded-content">

            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <h5 class="m-b-10">Facebook Leads</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header table-card-header">

                            <div class="container py-5">

                                <!-- CONNECT FACEBOOK BUTTON -->
                                <div class="mb-4">
                                    <a href="facebook_login.php" class="btn btn-primary">
                                        Connect Facebook
                                    </a>
                                </div>

                                <!-- CONNECTED PAGES -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-dark text-white">
                                        Connected Pages
                                    </div>
                                    <div class="card-body mt-3">
                                        

                                       
                                        <?php
                                        $pageResult = mysqli_query(
                                            $db,
                                            "SELECT * FROM facebook_pages WHERE user_id='$loginUser'"
                                        );
                                        ?>

                                        <?php if (mysqli_num_rows($pageResult) > 0) { ?>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Page Name</th>
                                                        <th>Page ID</th>
                                                        <th>Connected On</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($row = mysqli_fetch_assoc($pageResult)) { ?>
                                                        <tr>
                                                            <td><?= $row['page_name']; ?></td>
                                                            <td><?= $row['page_id']; ?></td>
                                                            <td><?= $row['created_at']; ?></td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        <?php } else { ?>
                                            <p class="text-muted">No Facebook pages connected yet.</p>
                                        <?php } ?>
 </div>
                                    </div>
                                </div>

                                <!-- FETCH LEADS -->
                                <div class="card shadow-sm">
                                    <div class="card-header bg-success text-white">
                                        Fetch Leads
                                    </div>
                                    <div class="card-body mt-3">

                                        <form id="fetchLeadsForm" class="row g-3">
                                            <div class="col-md-6">
                                                <select name="pageId" id="pageDropdown" class="form-control" required>
                                                    <option value="">Choose Page...</option>
                                                    <?php
                                                    $pageResult2 = mysqli_query(
                                                        $db,
                                                        "SELECT * FROM facebook_pages WHERE user_id='$loginUser'"
                                                    );
                                                    while ($row = mysqli_fetch_assoc($pageResult2)) {
                                                        echo "<option value='" . $row['page_id'] . "'>" . $row['page_name'] . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="col-md-3 align-self-end">
                                                <button class="btn btn-primary w-100" type="submit">
                                                    Fetch Leads
                                                </button>
                                            </div>
                                        </form>

                                        <div id="leadsResult" class="mt-4 table-responsive"></div>

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </section>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>

    <script>
        function loaderHTML(text = "Loading...") {
            return `<div class="text-center text-muted py-3">
        <div class="spinner-border text-primary"></div>
        <p class="mt-2">${text}</p>
    </div>`;
        }

        $('#fetchLeadsForm').on('submit', function(e) {
            e.preventDefault();

            const pageId = $('#pageDropdown').val();
            if (!pageId) return alert('Please select a page!');

            $('#leadsResult').html(loaderHTML('Fetching leads...'));

            $.post('', {
                action: 'get_leads',
                pageId
            }, function(res) {

                if (res.error) {
                    return $('#leadsResult').html(
                        `<div class="alert alert-danger">${res.error}</div>`
                    );
                }

                const leads = res.data || [];

                if (!leads.length) {
                    return $('#leadsResult').html(
                        '<div class="alert alert-warning">No leads found.</div>'
                    );
                }

                let html = `<table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Form</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>`;

                leads.forEach((lead, i) => {
                    html += `<tr>
                <td>${i + 1}</td>
                <td>${lead.form_name || '-'}</td>
                <td>${lead.full_name || '-'}</td>
                <td>${lead.email || '-'}</td>
                <td>${lead.phone_number || '-'}</td>
                <td>${lead.city || '-'}</td>
                <td>${lead.created_time || '-'}</td>
            </tr>`;
                });

                html += `</tbody></table>`;

                $('#leadsResult').html(html);

            }, 'json');
        });
    </script>

</body>

</html>