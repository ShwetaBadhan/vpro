<?php
include("db/config.php");

// Decode and validate greeting ID
$monthId = isset($_GET['id']) ? base64_decode($_GET['id']) : null;
if (!$monthId || !is_numeric($monthId)) {
    die("Invalid ID.");
}

// Fetch data from DB
$query = "SELECT 
em.month_id,
em.employee_id,
em.designation,
em.employee_image,
em.status,
em.month_year,
em.template_image,
position.name AS position_name,
personal_details.name AS emp_name
FROM emp_month as em
LEFT JOIN position on position.position_id = em.designation
LEFT JOIN personal_details on personal_details.personal_id = em.employee_id WHERE month_id = $monthId";
$result = mysqli_query($db, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die("No record found.");
}

$emp_name = $row['emp_name'];
$designation = $row['position_name'];
$emp_image_path = $row['employee_image']; // e.g., uploads/emp1.jpg
$template_path = $row['template_image']; // e.g., templates/welcome.jpg
$month = $row['month_year']; // e.g., "2025-07"
$dateObj = DateTime::createFromFormat('Y-m', $month);
$monthName = $dateObj->format('F'); // "July"

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee of the Month <?= htmlspecialchars($emp_name) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,600;1,600&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        

        body {
           font-family: "Montserrat", sans-serif;
            background: #f5f5f5;
            text-align: center;
            font-optical-sizing: auto;
  font-weight: 800;
  font-style: normal;
            margin: 0;
            padding: 20px;
        }

        .greeting-card {
            position: relative;
            width: 1080px;
            height: 1080px;
            margin: 0 auto;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            background-color: #000;
        }

        .template-bg {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            top: 0;
            left: 0;
            z-index: 0;
        }

        .content {
            position: relative;
            z-index: 1;
            padding: 100px;
            color: white;
            text-align: left;
        }

 .emp-photo {
     width: 489px;
            height: 490px;
    border-radius: 50%;
    object-fit: cover;
    position: absolute;
    top: 275px;
    left: 282px;
}


/* For download version */
.emp-photo-bg {
    width: 550px;
    height: 678px;
    border-radius: 50%;
    object-fit: cover; /* ✅ This was missing */
    position: absolute;
    top: 95px;
    left: 254px;
}


.circle img{
    position: absolute;
    top: 465px;
    left: 130px;
    /* background: linear-gradient(45deg, #00076c, #003399); Blue gradient fill
    border: 8px solid #e2b750; Golden border */
    width: 220px;
    height: 220px;
    /* border-radius: 50%;  */
}
/* .circle-name{
    position:absolute;
    top: 510px;
    left: 170px;
    font-size: 50px;
            margin: 0;
            color: #fff;
            width: 50%;
            font-weight: bold; 
}
     
.emp-month{
    position:absolute;
    top: 530px;
    left: 170px;
    font-size: 75px;
            margin: 0;
            color: #fff;
            width: 100px;
            font-weight: bold;
            margin-top: 20px;
} */
   .emp-name {
    position: absolute;
    top: 770px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 60px;
    margin: 0;
    color: #003399;
    font-weight: bold;
    text-align: center;
}

.emp-desg {
    position: absolute;
    top: 850px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 30px;
    margin: 0;
    color: #000;
    text-align: center;
}

.circle-name {
    position: absolute;
    top: 500px;
    left: 23%;
    transform: translateX(-50%);
    font-size: 40px;
    margin: 0;
    color: #fff;
    font-weight: bold;
    text-align: center;
}

.emp-month {
    position: absolute;
    top: 535px;
    left: 22%;
    transform: translateX(-50%);
    font-size: 33px;
    margin: 0;
    color: #fff;
    font-weight: bold;
    text-align: center;
    white-space: nowrap;
}


        .quote {
            position: absolute;
            bottom: 100px;
            left: 100px;
            font-size: 20px;
            color: #3498db;
            width: 800px;
        }

        #downloadBtn {
            margin-top: 30px;
            padding: 10px 20px;
      
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            border-radius: 5px;
            background-color: #191970;
            margin-bottom: 20px;
        }

        #downloadBtn:hover {
            background-color: #3498db;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<button id="downloadBtn">Download JPG</button>
<div id="greetingCard" class="greeting-card">
    <img src="<?= htmlspecialchars($template_path) ?>" class="template-bg" alt="Template Background">

    <div class="content">
        <img src="<?= htmlspecialchars($emp_image_path) ?>" class="emp-photo" alt="Employee">
        <h2 class="emp-name"><?= htmlspecialchars($emp_name) ?></h2>
       
        <h3 class="emp-desg"><?= htmlspecialchars($designation) ?></h3>
        <div class="circle">
            <img src="assets/images/template/employee/month.jpg" alt="">
            <h5 class="circle-name">Month</h5>
        <h2 class="emp-month" id="empMonth"><?= htmlspecialchars($monthName) ?></h2>

        </div>
    </div>
</div>
<div id="downloadCard" class="greeting-card" style="display: none;">
     <img src="<?= htmlspecialchars($template_path) ?>" class="template-bg" alt="Template Background">

    <div class="content">
        <img src="<?= htmlspecialchars($emp_image_path) ?>" class="emp-photo" alt="Employee">
        <h1 class="emp-name"><?= htmlspecialchars($emp_name) ?></h1>
       
        <h3 class="emp-desg"><?= htmlspecialchars($designation) ?></h3>
        <div class="circle">
            <img src="assets/images/template/employee/month.jpg" alt="">
            <h5 class="circle-name">Month</h5>
        <h2 class="emp-month" id="empMonth"><?= htmlspecialchars($monthName) ?></h2>

        </div>
    </div>
</div>




<!-- html2canvas for screenshot -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.getElementById('downloadBtn').addEventListener('click', function () {
    const element = document.getElementById('downloadCard');
    element.style.display = 'block'; // temporarily show hidden version

    html2canvas(element, {
        scale: 2,
        useCORS: true
    }).then(function (canvas) {
        const link = document.createElement('a');
        link.download = 'employee-of-the-month.jpg';
        link.href = canvas.toDataURL('image/jpeg', 1.0);
        link.click();

        element.style.display = 'none'; // hide again
    });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const monthEl = document.getElementById("empMonth");
    const monthText = monthEl.textContent.trim();

    if (monthText.length >= 8) {
        monthEl.style.fontSize = "40px"; // for longest like 'September'
    }
    else if (monthText.length >= 3) {
        monthEl.style.fontSize = "55px"; // medium length like 'August'
    }
    // else default remains (70px)
});
</script>




</body>
</html>
