<?php
session_start();
error_reporting(E_ALL);
include("db/config.php");

echo "<h2>Template Debug</h2>";

// Check templates
$query = "SELECT * FROM offer_letter_content";
$result = mysqli_query($db, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<div style='border:2px solid blue;padding:15px;margin:10px;'>";
        echo "<b>Letter Type:</b> " . $row['letter_type'] . "<br>";
        echo "<b>Content Length:</b> " . strlen($row['description']) . " characters<br>";
        echo "<b>Has [designation]?</b> " . (strpos($row['description'], '[designation]') !== false ? '✅ YES' : '❌ NO') . "<br>";
        echo "<b>Has [salary]?</b> " . (strpos($row['description'], '[salary]') !== false ? '✅ YES' : '❌ NO') . "<br>";
        echo "<b>Has [doj]?</b> " . (strpos($row['description'], '[doj]') !== false ? '✅ YES' : '❌ NO') . "<br>";
        echo "<hr>";
        echo "<b>Full Content:</b><br>";
        echo "<pre style='background:#f0f0f0;padding:10px;'>" . htmlspecialchars($row['description']) . "</pre>";
        echo "</div>";
    }
} else {
    echo "<div style='background:red;color:white;padding:20px;'>❌ NO TEMPLATES FOUND IN DATABASE!</div>";
    echo "<p>You need to save templates first from 'Add Offer Letter Content' page.</p>";
}
?>