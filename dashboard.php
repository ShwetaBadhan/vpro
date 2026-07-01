<?php
session_start();
include("db/config.php");
// echo '<pre>';
// print_r($_SESSION);
// exit;
if (!isset($_SESSION["login_user"])) {
    header("location: index.php");
    exit();
}

$admin_id = $_SESSION['login_user_id'];
$role_id = $_SESSION['admin_role'] ?? 0;
$employee_id = $_SESSION['employee_id'] ?? null;
$role_name = '';
$employee_data = null;
// echo $employee_id;
// exit;
// Fetch role name
$role_stmt = $db->prepare("SELECT role_name FROM roles WHERE role_id = ?");
$role_stmt->bind_param("i", $role_id);
$role_stmt->execute();
$role_result = $role_stmt->get_result();

if ($role_row = $role_result->fetch_assoc()) {
    $role_name = strtolower($role_row['role_name']);
}

// If role is employee, fetch employee data
if ($role_name === 'employee' && $employee_id) {
    $emp_stmt = $db->prepare("SELECT * FROM personal_details WHERE personal_id = ?");
    $emp_stmt->bind_param("i", $employee_id);
    $emp_stmt->execute();
    $emp_result = $emp_stmt->get_result();

    if ($emp_data = $emp_result->fetch_assoc()) {
        $employee_data = $emp_data;
    }
}
// echo $employee_id;
// exit;

$query = "SELECT * FROM admin";
$result = mysqli_query($db, $query);


// Agar lead_status column mein exact string 'own' stored hai, to:
$sql = "SELECT COUNT(*) AS own_leads_count FROM admission_enquiry WHERE lead_status = 'lead own'";

$result = mysqli_query($db, $sql);
$row = mysqli_fetch_assoc($result);
$own_leads_count = $row['own_leads_count'] ?? 0;

// Agar lead_status column mein exact string 'own' stored hai, to:
$hotlead = "SELECT COUNT(*) AS hot_leads_count FROM admission_enquiry WHERE lead_status = 'hot'";

$result_hot = mysqli_query($db, $hotlead);
$row = mysqli_fetch_assoc($result_hot);
$hot_leads_count = $row['hot_leads_count'] ?? 0;


// Agar lead_status column mein exact string 'own' stored hai, to:
$warmlead = "SELECT COUNT(*) AS warm_leads_count FROM admission_enquiry WHERE lead_status = 'own'";

$result_warm = mysqli_query($db, $warmlead);
$row = mysqli_fetch_assoc($result_warm);
$warm_leads_count = $row['warm_leads_count'] ?? 0;



// Agar lead_status column mein exact string 'own' stored hai, to:
$clients = "SELECT COUNT(*) AS clients_total_count FROM clients WHERE status = 1 AND is_deleted = 0";

$result_client = mysqli_query($db, $clients);
$row = mysqli_fetch_assoc($result_client);
$clients_total_count = $row['clients_total_count'] ?? 0;


$count_query = "
    SELECT COUNT(*) AS employee_total_count
    FROM personal_details pd
    JOIN company_details cd ON pd.personal_id = cd.user_id
    WHERE cd.employee_status = '1'
";

$count_result = mysqli_query($db, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$employee_total_count = $count_row['employee_total_count'] ?? 0;


// ✅ Get total employees
$total_query = "
    SELECT COUNT(*) AS total_employees
    FROM personal_details pd
    INNER JOIN company_details cd ON pd.personal_id = cd.user_id
    WHERE cd.employee_status = '1'
";
$total_result = mysqli_query($db, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_employees = $total_row['total_employees'] ?? 0;

// ✅ Get employees by employment type
$type_query = "
    SELECT cd.employement_type, COUNT(*) AS count
    FROM company_details cd
    INNER JOIN personal_details pd ON pd.personal_id = cd.user_id
    WHERE cd.employee_status = '1'
    GROUP BY cd.employement_type
";
$type_result = mysqli_query($db, $type_query);

$employment_counts = [];
while ($row = mysqli_fetch_assoc($type_result)) {
    $employment_counts[$row['employement_type']] = $row['count'];
}

// Example usage in HTML
$fulltime_count = $employment_counts['Regular'] ?? 0;
$contract_count = $employment_counts['Trainee'] ?? 0;
$probation_count = $employment_counts['Intern'] ?? 0;
$wfh_count = $employment_counts['WFM'] ?? 0;


$service = "SELECT COUNT(*) AS services_total_count FROM service";
$result_service = mysqli_query($db, $service);
$row = mysqli_fetch_assoc($result_service);
$services_total_count    = $row['services_total_count'] ?? 0;


// Get current date information
$currentMonth = date('m');
$currentYear = date('Y');
$today = date('Y-m-d');
$today_md = date('m-d');
$tomorrow_md = date('m-d', strtotime('+1 day'));
$currentDay = date('d');

// Check if we're in the last week of the month
$daysInCurrentMonth = date('t'); // Total days in current month
$isLastWeek = ($daysInCurrentMonth - $currentDay) <= 7;

// Prepare query based on whether we're in last week or not
if ($isLastWeek) {
    // Get current month + next month birthdays for active employees
    $nextMonth = date('m', strtotime('+1 month'));
    $nextMonthYear = date('Y', strtotime('+1 month'));

    $query = "
SELECT pd.name,
       pd.dob,
       DATE_FORMAT(pd.dob, '%d %b') as dob_display,
       MONTH(pd.dob) as birth_month,
       DAY(pd.dob) as birth_day
FROM personal_details pd
JOIN company_details cd ON pd.personal_id = cd.user_id
WHERE cd.employee_status = '1'
ORDER BY birth_month, birth_day
";
} else {
    // Get only current month birthdays for active employees
    $query = "
        SELECT pd.name, 
               pd.dob,
               DATE_FORMAT(pd.dob, '%m-%d') as md, 
               DATE_FORMAT(pd.dob, '%d %b') as dob_display,
               MONTH(pd.dob) as birth_month,
               DAY(pd.dob) as birth_day
        FROM personal_details pd
        JOIN company_details cd ON pd.personal_id = cd.user_id
        WHERE cd.employee_status = '1'
          AND MONTH(pd.dob) = '$currentMonth'
        ORDER BY DAY(pd.dob) ASC
    ";
}

$result = mysqli_query($db, $query);

$todayDate = new DateTime(date('Y-m-d'));

$todayList = [];
$tomorrowList = [];
$upcomingList = [];

while ($row = mysqli_fetch_assoc($result)) {

    $birthdayDate = new DateTime(
        date('Y') . '-' .
            str_pad($row['birth_month'], 2, '0', STR_PAD_LEFT) . '-' .
            str_pad($row['birth_day'], 2, '0', STR_PAD_LEFT)
    );

    // If birthday already passed this year → next year
    if ($birthdayDate < $todayDate) {
        $birthdayDate->modify('+1 year');
    }

    $daysDiff = (int)$todayDate->diff($birthdayDate)->days;

    if ($daysDiff === 0) {
        $todayList[] = $row;
    } elseif ($daysDiff === 1) {
        $tomorrowList[] = $row;
    } elseif ($daysDiff > 1 && $daysDiff <= 30) {
        $upcomingList[] = $row;
    }
}


// Sort upcoming list by actual birthday date
usort($upcomingList, function ($a, $b) use ($currentYear) {
    $dateA = mktime(0, 0, 0, $a['birth_month'], $a['birth_day'], $currentYear);
    $dateB = mktime(0, 0, 0, $b['birth_month'], $b['birth_day'], $currentYear);

    // If date has passed this year, use next year
    if ($dateA < time()) {
        $dateA = mktime(0, 0, 0, $a['birth_month'], $a['birth_day'], $currentYear + 1);
    }
    if ($dateB < time()) {
        $dateB = mktime(0, 0, 0, $b['birth_month'], $b['birth_day'], $currentYear + 1);
    }

    return $dateA - $dateB;
});


// work anniversary
// Get current date information
$currentMonth = date('m');
$currentYear  = date('Y');
$currentDay   = date('d');

// Check if we're in the last week of the month
$daysInCurrentMonth = date('t');
$isLastWeek = ($daysInCurrentMonth - $currentDay) <= 7;

// Prepare query
if ($isLastWeek) {
    $nextMonth = date('m', strtotime('+1 month'));

    $query = "
    SELECT cd.user_id,
           pd.name,
           cd.doj,
           DATE_FORMAT(cd.doj, '%d %b') as doj_display,
           MONTH(cd.doj) as doj_month,
           DAY(cd.doj) as doj_day
    FROM company_details cd
    JOIN personal_details pd ON pd.personal_id = cd.user_id
    WHERE cd.employee_status = '1'
";
} else {
    $query = "
        SELECT cd.user_id,
               pd.name,
               cd.doj,
               DATE_FORMAT(cd.doj, '%d %b') as doj_display,
               MONTH(cd.doj) as doj_month,
               DAY(cd.doj) as doj_day
        FROM company_details cd
        JOIN personal_details pd ON pd.personal_id = cd.user_id
        WHERE cd.employee_status = '1'
          AND MONTH(cd.doj) = '$currentMonth'
    ";
}

$result = mysqli_query($db, $query);


date_default_timezone_set('Asia/Kolkata');

$today = new DateTime('today');

$todayAnniversary    = [];
$tomorrowAnniversary = [];
$upcomingAnniversary = [];

while ($row = mysqli_fetch_assoc($result)) {

    if (empty($row['doj']) || $row['doj'] === '0000-00-00') {
        continue;
    }

    $doj = new DateTime($row['doj']);

    // future joining → skip
    if ($doj > $today) {
        continue;
    }

    // joined today → anniversary next year
    if ($doj->format('Y-m-d') === $today->format('Y-m-d')) {
        continue;
    }

    // anniversary date for current year
    $anniversary = new DateTime(
        $today->format('Y') . '-' . $doj->format('m-d')
    );

    // if already passed this year, move to next year
    if ($anniversary < $today) {
        $anniversary->modify('+1 year');
    }

    $daysDiff = (int)$today->diff($anniversary)->format('%a');

    if ($daysDiff === 0) {
        $todayAnniversary[] = $row;
    } elseif ($daysDiff === 1) {
        $tomorrowAnniversary[] = $row;
    } elseif ($daysDiff <= 30) {
        $row['__sortDate'] = $anniversary->format('Y-m-d');
        $upcomingAnniversary[] = $row;
    }
}

usort($upcomingAnniversary, function ($a, $b) {
    return strtotime($a['__sortDate']) <=> strtotime($b['__sortDate']);
});

$query = "SELECT * FROM login_settings";
$settingsResult = mysqli_query($db, $query);
$settings = mysqli_fetch_assoc($settingsResult);

$logoPath = $settings['backend_panel_logo'];
$helpdeskNumber = $settings['helpdesk_no'];
$favicon = $settings['favicon'];
// echo $favicon;
// exit;


// Get current month/year or from URL parameters
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Fetch events from database
$query = "SELECT * FROM event_calendar WHERE MONTH(event_date) = $month AND YEAR(event_date) = $year ORDER BY event_date";
$result = mysqli_query($db, $query);
$events = [];
while ($row = mysqli_fetch_assoc($result)) {
    $day = date('j', strtotime($row['event_date']));
    $events[$day][] = $row;
}

// Get current month/year or from URL parameters
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Fetch events from database
$query = "SELECT * FROM event_calendar 
WHERE MONTH(event_date) = $month 
  AND YEAR(event_date) = $year 
  AND status = 1
ORDER BY event_date;
";
$result = mysqli_query($db, $query);
$events = [];
while ($row = mysqli_fetch_assoc($result)) {
    $day = date('j', strtotime($row['event_date']));
    $events[$day][] = $row;
}

// Calendar calculations
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$dayOfWeek = date('w', $firstDay);
$monthName = date('F Y', $firstDay);

// Navigation
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}
?>

<!DOCTYPE html>
<html lang="en">
<meta http-equiv="content-type" content="text/html;charset=UTF-8" />

<head>
    <title>Control Management System</title>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="" />
    <meta name="keywords" content="">
    <meta name="author" content="" />

    <link rel="icon" href="<?php echo $favicon; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/plugins/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .birthday-list {
            max-height: 100px;
            overflow-y: auto;
            /* keep scroll functionality */
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE and Edge */
        }

        .birthday-list::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }
    </style>

</head>

<body class="">

    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>

    <!-- Header -->
    <?php
    include("header.php");
    ?>
    <!-- /Header -->

    <!-- navbar -->
    <?php
    include("navbar.php");
    ?>
    <!-- /navbar -->


    <section class="pcoded-main-container">
        <div class="pcoded-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="page-header-title">
                                <div class="row">
                                    <div class="col-md-5">
                                        <h5 style=" color:#003399; font-size:24px; font-weight:500; "> <i class="feather icon-clock"></i> &nbsp; <span id="ct6" style=" color:#003399; font-size:24px; font-weight:500; letter-spacing:2px;">10-10-2024 - 10:25:51: AM</span>
                                        </h5>
                                    </div>
                                    <div class="col-md-7">
                                        <h5 style=" color:#003399; font-size:24px; font-weight:500; "><i class="feather icon-server"></i>
                                            2401:4900:1c2b:e4c4:4d75:a874:82b2:9d95</h5>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <?php
            if (isset($_GET['loginin'])) {
                $st = $_GET['loginin'];
                $st1 = base64_decode($st);

                if ($st1 > 0) {
                    // check role
                    if ($role_name === 'employee') {
                        $userName = htmlspecialchars($_SESSION['login_user']); // session se naam
                        $welcomeMsg = "<strong><i class='feather icon-check'></i> Welcome, $userName!</strong> You have logged in successfully.";
                    } else {
                        $welcomeMsg = "<strong><i class='feather icon-check'></i> Welcome!</strong> User has been Login Successfully.";
                    }

                    echo "
        <div class='alert alert-success alert-dismissible fade show' role='alert' style='font-size:16px;' id='logged'>
            $welcomeMsg
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
                }
            }
            ?>


            <div class="row">

                <?php if ($role_name === 'employee') { ?>
                    <!-- Only Employee-specific section -->
                    <div class="col-md-6 col-xl-4">
                        <div class="card bg-c-blue order-card">
                            <div class="card-body d-flex flex-column align-items-center text-center">
                                <i class="feather icon-user text-white mb-2" style="font-size: 36px;"></i>
                                <a href="view-profile.php">
                                    <h6 class="text-white">View Profile</h6>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-4">
                        <div class="card bg-c-green order-card">
                            <div class="card-body d-flex flex-column align-items-center text-center">
                                <i class="feather icon-file-text text-white mb-2" style="font-size: 36px;"></i>
                                <a href="view-profile.php">
                                    <h6 class="text-white">Request Salary Slip</h6>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-4">
                        <div class="card bg-c-yellow order-card">
                            <div class="card-body d-flex flex-column align-items-center text-center">
                                <i class="feather icon-lock text-white mb-2" style="font-size: 36px;"></i>
                                <a href="changepass.php">
                                    <h6 class="text-white">Change Password</h6>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="custom-header">
                            <div class="d-flex flex-column justify-content-center align-items-center text-center">
                                <h4 class="text-white">
                                    <img src="assets/images/birthday-cake.png" alt="cake"> Birthdays
                                </h4>
                                <?php if ($isLastWeek): ?>
                                    <!-- <small class="text-muted">Showing current & next month</small> -->
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="birthday-card" style="height: 450px;">

                            <!-- <hr> -->

                            <!-- Today -->
                            <h6 class="text-primary">Today</h6>
                            <ul class="birthday-list">
                                <?php if (!empty($todayList)) {
                                    foreach ($todayList as $emp) { ?>
                                        <li>
                                            <div class="emp-info">
                                                <span><?= htmlspecialchars($emp['name']) ?></span>
                                            </div>
                                            <span><img src="assets/images/confetti.png"></span>
                                        </li>
                                    <?php }
                                } else { ?>
                                    <li><span>No birthdays today</span></li>
                                <?php } ?>
                            </ul>
                            <hr>

                            <!-- Tomorrow -->
                            <h6 class="text-success mt-3">Tomorrow</h6>
                            <ul class="birthday-list">
                                <?php if (!empty($tomorrowList)) {
                                    foreach ($tomorrowList as $emp) { ?>
                                        <li>
                                            <div class="emp-info">
                                                <span><?= htmlspecialchars($emp['name']) ?></span>
                                            </div>
                                            <span><img src="assets/images/giftbox.png"></span>
                                        </li>
                                    <?php }
                                } else { ?>
                                    <li><span>No birthdays tomorrow</span></li>
                                <?php } ?>
                            </ul>
                            <hr>

                            <!-- Upcoming -->
                            <h6 class="text-warning mt-3">
                                Upcoming
                                <?php if ($isLastWeek): ?>
                                    <small>(This & Next Month)</small>
                                <?php else: ?>
                                    <small>(This Month)</small>
                                <?php endif; ?>
                            </h6>
                            <ul class="birthday-list">
                                <?php if (!empty($upcomingList)) {
                                    foreach ($upcomingList as $emp) {
                                        // Calculate days until birthday
                                        $birth_month = $emp['birth_month'];
                                        $birth_day = $emp['birth_day'];
                                        $birthdayThisYear = mktime(0, 0, 0, $birth_month, $birth_day, $currentYear);

                                        if ($birthdayThisYear < time()) {
                                            $birthdayThisYear = mktime(0, 0, 0, $birth_month, $birth_day, $currentYear + 1);
                                        }

                                        $daysUntil = ceil(($birthdayThisYear - time()) / (60 * 60 * 24));
                                ?>
                                        <li>
                                            <div class="emp-info">
                                                <span><?= htmlspecialchars($emp['name']) ?></span>
                                                <small class="text-muted d-block">In <?= $daysUntil ?> day<?= $daysUntil != 1 ? 's' : '' ?></small>
                                            </div>
                                            <span><img src="assets/images/birthday-cake.png"> <?= $emp['dob_display'] ?></span>
                                        </li>
                                    <?php }
                                } else { ?>
                                    <li><span>No upcoming birthdays <?= $isLastWeek ? 'in next 30 days' : 'this month' ?></span></li>
                                <?php } ?>
                            </ul>

                        </div>

                    </div>
                    <div class="col-md-4">
                        <div class="custom-header">
                            <div class="d-flex flex-column justify-content-center align-items-center text-center">
                                <h4 class="text-white">
                                    <img src="assets/images/cheers.png" alt=""> Work Anniversary
                                </h4>
                            </div>
                        </div>

                        <div class="birthday-card" style="height: 349px;">
                            <!-- Today -->
                            <h6 class="text-primary">Today</h6>
                            <ul class="birthday-list">
                                <?php if (!empty($todayAnniversary)) {
                                    foreach ($todayAnniversary as $emp) { ?>
                                        <li>
                                            <div class="emp-info">
                                                <span><?= htmlspecialchars($emp['name']) ?></span>
                                            </div>
                                            <span><img src="assets/images/retreat.png"></span>
                                        </li>
                                    <?php }
                                } else { ?>
                                    <li><span>No anniversaries today</span></li>
                                <?php } ?>
                            </ul>
                            <hr>

                            <!-- Tomorrow -->
                            <h6 class="text-success mt-3">Tomorrow</h6>
                            <ul class="birthday-list">
                                <?php if (!empty($tomorrowAnniversary)) {
                                    foreach ($tomorrowAnniversary as $emp) { ?>
                                        <li>
                                            <div class="emp-info">
                                                <span><?= htmlspecialchars($emp['name']) ?></span>
                                            </div>
                                            <span><img src="assets/images/giftbox.png"></span>
                                        </li>
                                    <?php }
                                } else { ?>
                                    <li><span>No anniversaries tomorrow</span></li>
                                <?php } ?>
                            </ul>
                            <hr>

                            <!-- Upcoming -->
                            <h6 class="text-warning mt-3">
                                Upcoming
                                <?php if ($isLastWeek): ?>
                                    <small>(This & Next Month)</small>
                                <?php else: ?>
                                    <small>(This Month)</small>
                                <?php endif; ?>
                            </h6>
                            <ul class="birthday-list">
                                <?php if (!empty($upcomingAnniversary)) {
                                    foreach ($upcomingAnniversary as $emp) {
                                        $doj_month = $emp['doj_month'];
                                        $doj_day = $emp['doj_day'];
                                        $anniversaryThisYear = mktime(0, 0, 0, $doj_month, $doj_day, $currentYear);

                                        if ($anniversaryThisYear < time()) {
                                            $anniversaryThisYear = mktime(0, 0, 0, $doj_month, $doj_day, $currentYear + 1);
                                        }

                                        $daysUntil = ceil(($anniversaryThisYear - time()) / (60 * 60 * 24));
                                ?>
                                        <li>
                                            <div class="emp-info">
                                                <span><?= htmlspecialchars($emp['name']) ?></span>
                                                <small class="text-muted d-block">In <?= $daysUntil ?> day<?= $daysUntil != 1 ? 's' : '' ?></small>
                                            </div>
                                            <span><img src="assets/images/cheers.png"> <?= $emp['doj_display'] ?></span>
                                        </li>
                                    <?php }
                                } else { ?>
                                    <li><span>No upcoming anniversaries <?= $isLastWeek ? 'in next 30 days' : 'this month' ?></span></li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>


                    <div class="col-md-4">
                        <div class="custom-emp text-center">
                            <h4 class="text-white"> Clients Assigned</h4>

                        </div>
                        <div class="birthday-card" style="height: 450px;">

                            <div class="employee-status-card">




                                <!-- Clients Assigned -->
                                <div class="assigned-clients">

                                    <?php
                                    $sql = "SELECT c.name
                                    FROM company_details cd
                                    JOIN clients c ON FIND_IN_SET(c.client_id, cd.assigned_client)
                                    WHERE cd.user_id = ?";
                                    $stmt = $db->prepare($sql);
                                    $stmt->bind_param("i", $employee_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    ?>

                                    <?php if ($result->num_rows > 0): ?>
                                        <ul class="client-list">
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <li class="client-card">
                                                    <div class="client-avatar">
                                                        <i class="fas fa-user-circle"></i>
                                                    </div>
                                                    <div class="client-info">
                                                        <strong><?= htmlspecialchars($row['name']); ?></strong>
                                                    </div>
                                                </li>
                                            <?php endwhile; ?>
                                        </ul>

                                    <?php else: ?>
                                        <p class="text-muted">No clients assigned.</p>
                                    <?php endif; ?>
                                </div>




                            </div>
                        </div>

                    </div>

                <?php } else { ?>
                    <!-- Full dashboard for other roles -->
                    <!-- order-card start -->
                    <?php if ($isAdmin || in_array('View Total Leads', $permissions)): ?>

                        <div class="col-md-6 col-xl-3">
                            <div class="card bg-c-blue order-card">
                                <div class="card-body">
                                    <a href="admission-leads.php">
                                        <h6 class="text-white">Total Leads</h6>
                                    </a>
                                    <h2 class="text-right text-white"><i
                                            class="feather icon-message-square float-left"></i><span id="new4"></span>
                                    </h2>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($isAdmin || in_array('View New Leads', $permissions)): ?>

                        <div class="col-md-6 col-xl-3">
                            <div class="card bg-c-green order-card">
                                <div class="card-body">
                                    <a href="admission-leads.php">
                                        <h6 class="text-white">New Leads</h6>
                                    </a>
                                    <h2 class="text-right text-white"><i
                                            class="feather icon-user float-left"></i><span id="total"></span>
                                    </h2>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($isAdmin || in_array('View Admin Users', $permissions)): ?>
                        <div class="col-md-6 col-xl-3">
                            <div class="card bg-c-yellow order-card">
                                <div class="card-body">
                                    <a href="manage-user.php">
                                        <h6 class="text-white">Admin Users</h6>
                                    </a>
                                    <h2 class="text-right text-white"><i
                                            class="feather icon-users float-left"></i><span id="new2"></span>
                                    </h2>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($isAdmin || in_array('View Total Downloads', $permissions)): ?>

                        <div class="col-md-6 col-xl-3">
                            <div class="card bg-c-red order-card">
                                <div class="card-body">
                                    <a href="admission-leads.php">
                                        <h6 class="text-white">Total Downloads</h6>
                                    </a>
                                    <h2 class="text-right text-white"><i
                                            class="feather icon-download float-left"></i><span id="new3"></span>
                                    </h2>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <!-- <?php if ($isAdmin || in_array('View Total Employees', $permissions)): ?>
                    <div class="col-md-6 col-xl-3">
                        <div class="card bg-c-grren order-card">
                            <div class="card-body">
                                <a href="manage-employees.php">
                                    <h6 class="text-white">Total Employees</h6>
                                </a>
                                <h2 class="text-right text-white">
                                    <i class="feather icon-user float-left"></i>

                                    <span><?= htmlspecialchars($employee_total_count) ?></span>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($isAdmin || in_array('View Total Services', $permissions)): ?>

                    <div class="col-md-6 col-xl-3">
                        <div class="card bg-c-reds order-card">
                            <div class="card-body">
                                <a href="manage-service.php">
                                    <h6 class="text-white">Total Services</h6>
                                </a>
                                <h2 class="text-right text-white">
                                    <i class="feather icon-user float-left"></i>

                                    <span><?= htmlspecialchars($services_total_count) ?></span>
                                </h2>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($isAdmin || in_array('View Total Clients', $permissions)): ?>

                    <div class="col-md-6 col-xl-3">
                        <div class="card bg-c-blue order-card">
                            <div class="card-body">
                                <a href="manage-client.php">
                                    <h6 class="text-white">Total Clients</h6>
                                </a>
                                <h2 class="text-right text-white"><i
                                        class="feather icon-users float-left"></i><?= htmlspecialchars($clients_total_count) ?></span>
                                </h2>
                            </div>
                        </div>
                    </div>
                     <?php endif; ?>
                    <?php if ($isAdmin || in_array('View Own Leads', $permissions)): ?> -->

                    <!-- <div class="col-md-6 col-xl-3">
                        <div class="card bg-c-warms order-card">
                            <div class="card-body">
                                <a href="own-leads.php">
                                    <h6 class="text-white">Own Leads</h6>
                                </a>
                                <h2 class="text-right text-white">
                                    <i class="feather icon-user float-left"></i>

                                    <span><?= htmlspecialchars($warm_leads_count) ?></span>
                                </h2>
                            </div>
                        </div>
                    </div> -->
                <?php endif; ?>
            </div>


          
        <?php } ?>
        </div>
        </div>
    </section>

    <!-- Event Modal -->
    <!-- Event Tooltip Overlay -->
    <div id="tooltipOverlay" class="tooltip-overlay" onclick="closeEventTooltip()"></div>

    <!-- Event Tooltip -->
    <div id="eventTooltip" class="event-tooltip">
        <div class="tooltip-header">
            <h3 id="tooltipDate"></h3>
            <button class="tooltip-close" onclick="closeEventTooltip()">&times;</button>
        </div>
        <div id="tooltipBody"></div>
    </div>

    <script src="assets/js/vendor-all.min.js"></script>
    <script src="assets/js/plugins/bootstrap.min.js"></script>
    <script src="assets/js/pcoded.min.js"></script>
    <!--<script src="assets/js/menu-setting.min.js"></script>-->

    <script src="assets/js/plugins/jquery.dataTables.min.js"></script>
    <script src="assets/js/plugins/dataTables.bootstrap4.min.js"></script>
    <script src="assets/js/plugins/buttons.colVis.min.js"></script>
    <script src="assets/js/plugins/buttons.print.min.js"></script>
    <script src="assets/js/plugins/pdfmake.min.js"></script>
    <script src="assets/js/plugins/jszip.min.js"></script>
    <script src="assets/js/plugins/dataTables.buttons.min.js"></script>
    <script src="assets/js/plugins/buttons.html5.min.js"></script>
    <script src="assets/js/plugins/buttons.bootstrap4.min.js"></script>
    <script src="assets/js/pages/data-export-custom.js"></script>
</body>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#new").load("loadpost.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#total").load("loadnewleads.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#user").load("load-category.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#new1").load("loadpage.php");

        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#new2").load("loadadmin.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#new3").load("totaldownloads.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#new4").load("loadleads.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        setInterval(function() {


            $("#new5").load("loadclients.php");


        }, 100);
    });
</script>

<script>
    $(document).ready(function() {
        $("#logged").delay(5000).slideUp(300);
    });
</script>

<script>
    function display_ct6() {
        var x = new Date()
        var ampm = x.getHours() >= 12 ? ' PM' : ' AM';
        hours = x.getHours() % 12;
        hours = hours ? hours : 12;
        var x1 = x.getMonth() + 1 + "-" + x.getDate() + "-" + x.getFullYear();
        x1 = x1 + " - " + hours + ":" + x.getMinutes() + ":" + x.getSeconds() + ":" + ampm;
        document.getElementById('ct6').innerHTML = x1;
        display_c6();
    }

    function display_c6() {
        var refresh = 1000; // Refresh rate in milli seconds
        mytime = setTimeout('display_ct6()', refresh)
    }
    display_c6()
</script>


<script>
    function showEventModal(events, date) {
        const tooltip = document.getElementById('eventTooltip');
        const overlay = document.getElementById('tooltipOverlay');
        const tooltipDate = document.getElementById('tooltipDate');
        const tooltipBody = document.getElementById('tooltipBody');

        tooltipDate.textContent = date;

        let html = '';
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        events.forEach(event => {
            const eventDate = new Date(event.event_date);
            eventDate.setHours(0, 0, 0, 0);

            const isExpired = eventDate < today;
            const statusText = isExpired ? 'Expired' : 'Upcoming';
            const statusClass = isExpired ? 'expired' : 'upcoming';

            html += `
                    <div class="event-details">
                        <div class="event-detail-title">${escapeHtml(event.title)}</div>
                        ${event.description ? `<div class="event-detail-row"><strong>Description:</strong> ${escapeHtml(event.description)}</div>` : ''}
                        ${event.type ? `<div class="event-detail-row"><strong>Type:</strong> ${escapeHtml(event.type)}</div>` : ''}
                        ${event.location ? `<div class="event-detail-row"><strong>Location:</strong> ${escapeHtml(event.location)}</div>` : ''}
                        
                        <span class="event-badge ${statusClass}">${statusText}</span>
                       
                    </div>
                `;
        });

        tooltipBody.innerHTML = html;

        // Position tooltip in center
        overlay.classList.add('show');
        tooltip.classList.add('show');

        // Center the tooltip
        setTimeout(() => {
            const rect = tooltip.getBoundingClientRect();
            tooltip.style.left = `${(window.innerWidth - rect.width) / 2}px`;
            tooltip.style.top = `${Math.max(50, (window.innerHeight - rect.height) / 2)}px`;
        }, 10);
    }

    function closeEventTooltip() {
        const tooltip = document.getElementById('eventTooltip');
        const overlay = document.getElementById('tooltipOverlay');
        tooltip.classList.remove('show');
        overlay.classList.remove('show');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Close tooltip with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeEventTooltip();
        }
    });
</script>

<script>
    // AJAX month navigation
    $(document).on('click', '.nav-btn', function(e) {
        e.preventDefault();
        const month = $(this).data('month');
        const year = $(this).data('year');

        $('#calendar-container').html('<div class="loading">Loading...</div>');

        $.ajax({
            url: 'calendar-section.php',
            type: 'GET',
            data: {
                month: month,
                year: year
            },
            success: function(response) {
                $('#calendar-container').html(response);
            },
            error: function() {
                $('#calendar-container').html('<div class="loading">Error loading calendar.</div>');
            }
        });
    });
</script>

</html>