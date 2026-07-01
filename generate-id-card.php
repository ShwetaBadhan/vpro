<?php
include('db/config.php');

if (!isset($_GET['id'])) {
    die("No ID Card ID provided!");
}
$encoded_id = $_GET['id'];
$id_card_id = base64_decode($encoded_id);

$query = "
SELECT 
    ic.id_card_id,
    ic.template_front,
    ic.template_back,
    p.name AS employee_name,
    p.photo AS employee_photo,
    p.address,
    p.emergency_no,
    d.name AS designation_name,  -- FIX: Get name from position table
    c.employee_code,
    c.doj
FROM id_cards ic
LEFT JOIN personal_details p ON ic.employee_id = p.personal_id
LEFT JOIN company_details c ON p.personal_id = c.user_id
LEFT JOIN position d ON c.designation = d.position_id  -- FIX: Correct relation
WHERE ic.id_card_id = '$id_card_id'
";


$result = mysqli_query($db, $query);
if (!$result || mysqli_num_rows($result) == 0) {
    die("No matching ID Card record found!");
}
$data = mysqli_fetch_assoc($result);

$frontTemplate = !empty($data['template_front']) ? $data['template_front'] : 'assets/images/template/idcard/default_front.jpeg';
$backTemplate = !empty($data['template_back']) ? $data['template_back'] : 'assets/images/template/idcard/default_back.jpeg';
$photo = !empty($data['employee_photo']) ? $data['employee_photo'] : 'assets/images/default-user.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ID Card - <?php echo htmlspecialchars($data['employee_name']); ?></title>

<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Nunito Sans', sans-serif;
        background: #f5f5f5;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 30px;
        min-height: 100vh;
        padding: 20px;
    }

    .id-wrapper {
        display: flex;
        gap: 40px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .id-card {
        width: 340px;
        height: 540px;
        position: relative;
        background-size: cover;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        color: #000;
    }

    .front { background-image: url('<?php echo $frontTemplate; ?>'); }
    .back { background-image: url('<?php echo $backTemplate; ?>'); }

    .photo {
        position: absolute;
        top: 127px;
        left: 75px;
        width: 56%;
        height: 37%;
        border-radius: 50%;
        object-fit: cover;
    }

    .emp_code { position: absolute; top: 82%; left: 16%; }
    .location { position: absolute; top: 82%; left: 64%; }
  .emp-header {
    text-align: center;
    width: 100%;
}

/* Employee Name */
.emp_name {
    font-size: 22px;
    font-weight: bold;
    margin: 22rem 0 2px;   /* top bottom spacing */
    color: #1c4969;
}

/* Designation */
.emp_designation {
    font-size: 21px;
    font-weight: bolder;
    margin: 2px 0 10px;  /* top bottom spacing */
}


    .address {
        position: absolute;
        top: 12%;
        left: 10%;
        width: 80%;
        text-align: center;
        line-height: 1.4;
        font-size: 14px;
    }

    .phone { position: absolute; top: 31%; left: 35%; }

    button {
        background: #007bff;
        border: none;
        color: #fff;
        padding: 10px 25px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
    }

    button:hover {
        background: #0056b3;
    }
</style>
</head>
<body>

<h2>ID Card Preview</h2>

<div class="id-wrapper" id="idCardArea">
    <!-- FRONT -->
    <div class="id-card front">
        <img src="<?php echo htmlspecialchars($photo); ?>" class="photo" alt="Employee Photo">
        <div class="emp-header">
    <h3 class="emp_name"><?php echo htmlspecialchars($data['employee_name']); ?></h3>
    <p class="emp_designation"><?php echo htmlspecialchars($data['designation_name']); ?></p>
</div>

        <h5 class="emp_code"><?php echo htmlspecialchars($data['employee_code'])?></h5>
        <h5 class="location">Mohali Pb.</h5>
    </div>

    <!-- BACK -->
    <div class="id-card back">
        <h5 class="address">
            <?php
            $address = htmlspecialchars($data['address']); 
            $formatted_address = wordwrap($address, 30, "<br>", true);
            echo $formatted_address;
            ?>
        </h5>
        <h5 class="phone">
           +91 <?php echo htmlspecialchars($data['emergency_no'])?>
        </h5>
    </div>
</div>

<button id="downloadBtn">Download ID Card</button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
document.getElementById("downloadBtn").addEventListener("click", async function() {
    const { jsPDF } = window.jspdf;

    // Create landscape PDF at 300 DPI size
    const pdf = new jsPDF({
        orientation: 'landscape',
        unit: 'px',
        format: [1200, 900], // larger canvas for print quality
        hotfixes: ["px_scaling"]
    });

    const cardArea = document.getElementById("idCardArea");

    // Capture at high scale for HD
    const canvas = await html2canvas(cardArea, {
        backgroundColor: '#ffffff',
        scale: 5, // 4–5 = near 300 DPI quality
        useCORS: true,
        logging: false,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });

    const imgData = canvas.toDataURL("image/png", 1.0);
    const imgWidth = pdf.internal.pageSize.getWidth();
    const imgHeight = (canvas.height * imgWidth) / canvas.width;

    pdf.addImage(imgData, "PNG", 0, 0, imgWidth, imgHeight);
    pdf.save("ID_Card_<?php echo htmlspecialchars($data['employee_name']); ?>_HD.pdf");
});
</script>


</body>
</html>
