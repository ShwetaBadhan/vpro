<?php
include("db/config.php");

// Decode and validate greeting ID
$monthId = isset($_GET['id']) ? base64_decode($_GET['id']) : null;
if (!$monthId || !is_numeric($monthId)) {
    die("Invalid ID.");
}

// Fetch data from DB
$query = "SELECT 
em.work_id,
em.name,
em.designation,
em.employee_image,
em.work_year,
em.status,
em.description,
em.template_image,
position.name AS position_name,
personal_details.name AS emp_name
FROM work_anniversary as em
LEFT JOIN position on position.position_id = em.designation
LEFT JOIN personal_details on personal_details.personal_id = em.name WHERE work_id = $monthId";
$result = mysqli_query($db, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    die("No record found.");
}

$emp_name = $row['emp_name'];
$designation = $row['position_name'];
$emp_image_path = $row['employee_image']; // e.g., uploads/emp1.jpg
$template_path = $row['template_image']; // e.g., templates/welcome.jpg
$desc = $row['description'];
function getOrdinal($number)
{
    $suffix = 'th';
    if (!in_array(($number % 100), [11, 12, 13])) {
        switch ($number % 10) {
            case 1:
                $suffix = 'st';
                break;
            case 2:
                $suffix = 'nd';
                break;
            case 3:
                $suffix = 'rd';
                break;
        }
    }
    return $number . '<sup>' . $suffix . '</sup>';
}

$year = $row['work_year']; // e.g., 1, 2, 5
$ordinalYear = getOrdinal($year); // will return "1<sup>st</sup>" etc.



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Work Anniversary <?= htmlspecialchars($emp_name) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lusitana:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Lusitana", serif;
            background: #f5f5f5;
            text-align: center;
            font-optical-sizing: auto;
            /* font-weight: 800; */
            font-weight: 400;
            font-style: normal;
            margin: 0;
            padding: 20px;
        }

        .greeting-card {
            position: relative;
            width: 1024px;
            height: 1280px;
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
            width: 470px;
            height: 470px;
            border-radius: 50%;
            object-fit: cover;
            position: absolute;
            top: 450px;
            left: 278px;
        }


        /* For download version */
        .emp-photo-bg {
            width: 550px;
            height: 678px;
            border-radius: 50%;
            object-fit: cover;
            /* ✅ This was missing */
            position: absolute;
            top: 95px;
            left: 254px;
        }


        .circle img {
            position: absolute;
            top: 465px;
            left: 130px;
            /* background: linear-gradient(45deg, #00076c, #003399); Blue gradient fill
    border: 8px solid #e2b750; Golden border */
            width: 220px;
            height: 220px;
            /* border-radius: 50%;  */
        }


        .emp-name {
            position: absolute;
            top: 1000px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 80px;
            margin: 0;
            color: #fff;
            font-weight: bold;
            text-align: center;
        }

        .emp-desg {
            position: absolute;
            top: 1090px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 40px;
            margin: 0;
            color: #fff;
            text-align: center;
        }

        .desc {
            position: absolute;
            top: 1170px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 26px;
            margin: 0;
            color: #fed670;
            text-align: center;
        }

        .emp-heading-wrapper {
            position: absolute;
            top: 200px;
            width: 80%;
            /* full width */
            display: flex;
            justify-content: center;
            /* center horizontally */
        }

        .emp-heading {
            font-size: 95px;
            margin: 0;
            color: #fed670;
            text-align: center;
            line-height: 1.2;
        }


        .emp-heading sup {
            font-size: 0.5em;
            vertical-align: super;
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
            <div class="emp-heading-wrapper">
                <h1 class="emp-heading">
                    Happy <?= $ordinalYear ?> Work<br>Anniversary
                </h1>
            </div>

            <img src="<?= htmlspecialchars($emp_image_path) ?>" class="emp-photo" alt="Employee">

            <h2 class="emp-name" id="emp-name"><?= htmlspecialchars($emp_name) ?></h2>
            <h3 class="emp-desg" id="emp-desg"><?= htmlspecialchars($designation) ?></h3>
            <p class="desc">"<?= htmlspecialchars($desc) ?>"</p>
        </div>
    </div>
    <div id="downloadCard" class="greeting-card" style="display: none;">
        <img src="<?= htmlspecialchars($template_path) ?>" class="template-bg" alt="Template Background">

        <div class="content">
            <div class="emp-heading-wrapper">
                <h1 class="emp-heading">
                    Happy <?= $ordinalYear ?> Work<br>Anniversary
                </h1>
            </div>

            <img src="<?= htmlspecialchars($emp_image_path) ?>" class="emp-photo" alt="Employee">
            <h2 class="emp-name" id="emp-name-download"><?= htmlspecialchars($emp_name) ?></h2>
            <h3 class="emp-desg" id="emp-desg-download"><?= htmlspecialchars($designation) ?></h3>
            <p class="desc">"<?= htmlspecialchars($desc) ?>"</p>
        </div>
    </div>




    <!-- html2canvas for screenshot -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <!-- Add FontFaceObserver for reliable font loading -->
<script src="https://cdn.jsdelivr.net/npm/fontfaceobserver@2.3.0/fontfaceobserver.standalone.js"></script>

<script>
document.getElementById('downloadBtn').addEventListener('click', function() {
    const element = document.getElementById('downloadCard');
    element.style.display = 'block';

    // Wait for Lusitana font to load
    const lusitanaFont = new FontFaceObserver('Lusitana', { weight: 400 });
    const lusitanaBold = new FontFaceObserver('Lusitana', { weight: 700 });

    Promise.all([
        lusitanaFont.load(null, 5000),
        lusitanaBold.load(null, 5000)
    ])
    .then(function () {
        // Font loaded — now capture
        return html2canvas(element, {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            logging: false
        });
    })
    .then(function(canvas) {
        const link = document.createElement('a');
        link.download = 'work-anniversary.jpg';
        link.href = canvas.toDataURL('image/jpeg', 0.95);
        link.click();

        element.style.display = 'none';
    })
    .catch(function (error) {
        console.warn('Font loading failed, proceeding anyway:', error);
        // Fallback: capture without waiting (may use wrong font)
        html2canvas(element, {
            scale: 2,
            useCORS: true,
            allowTaint: true
        }).then(function(canvas) {
            const link = document.createElement('a');
            link.download = 'work-anniversary.jpg';
            link.href = canvas.toDataURL('image/jpeg', 0.95);
            link.click();
            element.style.display = 'none';
        });
    });
});
</script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const nameEls = [
                document.getElementById("emp-name"),
                document.getElementById("emp-name-download")
            ];
            const desgEls = [
                document.getElementById("emp-desg"),
                document.getElementById("emp-desg-download")
            ];

            nameEls.forEach(el => {
                if (el) {
                    const name = el.textContent.trim();
                    if (name.length > 18) {
                        el.style.fontSize = "40px";
                    } else if (name.length > 12) {
                        el.style.fontSize = "55px";
                    } else {
                        el.style.fontSize = "80px";
                    }
                }
            });

            desgEls.forEach(el => {
                if (el) {
                    const desg = el.textContent.trim();
                    if (desg.length > 25) {
                        el.style.fontSize = "25px";
                    } else if (desg.length > 15) {
                        el.style.fontSize = "30px";
                    } else {
                        el.style.fontSize = "40px";
                    }
                }
            });
        });
        element.style.display = 'none'; // after download
    </script>






</body>

</html>