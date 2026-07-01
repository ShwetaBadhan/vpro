<?php
include("db/config.php");

// Decode and validate greeting ID
$birthdayId = isset($_GET['id']) ? base64_decode($_GET['id']) : null;
if (!$birthdayId || !is_numeric($birthdayId)) {
    die("Invalid ID.");
}

// Fetch data from DB
$query = "SELECT 
    bg.birthday_id,
    bg.employee_id,
    bg.employee_image,
    bg.status,
    bg.template_image,
    pd.name AS emp_name
FROM birthday_greetings AS bg
LEFT JOIN personal_details AS pd ON pd.personal_id = bg.employee_id
WHERE birthday_id = $birthdayId";
$result = mysqli_query($db, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die("No record found.");
}

$emp_name = $row['emp_name'];

$emp_image_path = $row['employee_image']; // e.g., uploads/emp1.jpg
$template_path = $row['template_image']; // e.g., templates/welcome.jpg


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Birthday Greetings <?= htmlspecialchars($emp_name) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,600;1,600&family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Times", sans-serif;
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
            top: 422px;
            left: 300px;
        }


      

  
        .emp-name {
            position: absolute;
            top: 860px;
            left: 52%;
            transform: translateX(-50%);
            font-size: 60px;
            margin: 0;
            color: #fff;
            font-weight: bold;
            text-align: center;
            z-index: 1;
            transform: translateX(-50%) rotate(-5deg);
        }
.bg-tab {
    background-color: #051181;
    width: 40%;
    height: 80px;
    border-radius: 10px;
    position: absolute;
    top: 860px;
    left: 52%;
    transform: translateX(-50%) rotate(-5deg);
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
            <div class="bg-tab">

            </div>
        </div>
    </div>
    <div id="downloadCard" class="greeting-card" style="display: none;">
        <img src="<?= htmlspecialchars($template_path) ?>" class="template-bg" alt="Template Background">

       <div class="content">
            <img src="<?= htmlspecialchars($emp_image_path) ?>" class="emp-photo" alt="Employee">
            <h2 class="emp-name"><?= htmlspecialchars($emp_name) ?></h2>
            <div class="bg-tab">

            </div>
        </div>
    </div>




    <!-- html2canvas for screenshot -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        document.getElementById('downloadBtn').addEventListener('click', function() {
            const element = document.getElementById('downloadCard');
            element.style.display = 'block'; // temporarily show hidden version

            html2canvas(element, {
                scale: 2,
                useCORS: true
            }).then(function(canvas) {
                const link = document.createElement('a');
                link.download = 'birthday-greetings.jpg';
                link.href = canvas.toDataURL('image/jpeg', 1.0);
                link.click();

                element.style.display = 'none'; // hide again
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const monthEl = document.getElementById("empMonth");
            const monthText = monthEl.textContent.trim();

            if (monthText.length >= 8) {
                monthEl.style.fontSize = "40px"; // for longest like 'September'
            } else if (monthText.length >= 3) {
                monthEl.style.fontSize = "55px"; // medium length like 'August'
            }
            // else default remains (70px)
        });
    </script>




</body>

</html>