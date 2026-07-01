<?php
include("db/config.php");

// Decode and validate greeting ID
$greetingId = isset($_GET['id']) ? base64_decode($_GET['id']) : null;
if (!$greetingId || !is_numeric($greetingId)) {
    die("Invalid ID.");
}

// Fetch data from DB
$query = "SELECT 
eg.greeting_id,
eg.emp_name,
eg.emp_image,
eg.status,
eg.template_image,
position.name AS position_name
FROM emp_greetings as eg
LEFT JOIN position on position.position_id = eg.emp_designation WHERE greeting_id = $greetingId";
$result = mysqli_query($db, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die("No record found.");
}

$emp_name = $row['emp_name'];
$designation = $row['position_name'];
$emp_image_path = $row['emp_image']; // e.g., uploads/emp1.jpg
$template_path = $row['template_image']; // e.g., templates/welcome.jpg
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Greeting for <?= htmlspecialchars($emp_name) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Montserrat", sans-serif;
            background: #f5f5f5;
            text-align: center;
            font-optical-sizing: auto;
            font-weight: 600;
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
            width: 350px;
            height: 600px;
            border-radius: 150px;
            object-fit: cover;
            position: absolute;
            top: 280px;
            left: 45px;
            border: 5px solid white;
            box-shadow: 0 0 15px 15px #191970;
            z-index: 1;
        }

        /* For download version */
        .emp-photo-bg {
            width: 350px;
            height: 600px;
            border-radius: 150px;
            background-size: cover;
            background-position: center;
            position: absolute;
            top: 280px;
            left: 45px;
            border: 5px solid white;
            box-shadow: 0 0 15px 15px #191970;
            z-index: 1;
        }




        .emp-name {
            position: absolute;
            top: 640px;
            left: 440px;
            font-size: 62px;
            margin: 0;

            width: 50%
        }

        .line {
            position: absolute;
            top: 726px;
            left: 420px;
            font-size: 50px;
            margin: 0;
            border-bottom: 1px solid #fff;
            width: 50%;
            margin-top: 4px;
        }

        .emp-desg {
            position: absolute;
            top: 740px;
            left: 440px;
            font-size: 56px;
            margin: 0;
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
            <h1 class="emp-name"><?= htmlspecialchars($emp_name) ?></h1>
            <div class="line"></div>
            <h3 class="emp-desg"><?= htmlspecialchars($designation) ?></h3>
        </div>
    </div>
    <div id="downloadCard" class="greeting-card" style="display: none;">
        <img src="<?= htmlspecialchars($template_path) ?>" class="template-bg" alt="Template Background">

        <div class="content">
            <div class="emp-photo-bg" style="background-image: url('<?= htmlspecialchars($emp_image_path) ?>');"></div>
            <h1 class="emp-name"><?= htmlspecialchars($emp_name) ?></h1>
            <div class="line"></div>
            <h3 class="emp-desg"><?= htmlspecialchars($designation) ?></h3>
        </div>
    </div>




    <!-- html2canvas for screenshot -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
   <script>
    async function downloadGreeting() {
        const element = document.getElementById('downloadCard');
        element.style.display = 'block';

        // Wait for fonts to load
        await document.fonts.ready;

        html2canvas(element, {
            scale: 2,
            useCORS: true
        }).then(function (canvas) {
            const link = document.createElement('a');
            link.download = 'greeting.jpg';
            link.href = canvas.toDataURL('image/jpeg', 1.0);
            link.click();
            element.style.display = 'none';
        });
    }

    document.getElementById('downloadBtn').addEventListener('click', downloadGreeting);
</script>
<script>
    function adjustFontSize(selector, maxFontSize, minFontSize, maxLength) {
        const element = document.querySelector(selector);
        if (!element) return;

        const textLength = element.textContent.trim().length;

        // Calculate new font size based on length
        let newSize = maxFontSize;
        if (textLength > maxLength) {
            const diff = textLength - maxLength;
            newSize = Math.max(minFontSize, maxFontSize - diff * 2); // reduce 2px per extra character
        }

        element.style.fontSize = newSize + "px";
    }

    // Adjust when page loads
    window.addEventListener('DOMContentLoaded', () => {
        adjustFontSize(".emp-name", 62, 38, 15);       // maxFontSize, minFontSize, maxLength
        adjustFontSize(".emp-desg", 56, 32, 18);
    });
</script>



</body>

</html>