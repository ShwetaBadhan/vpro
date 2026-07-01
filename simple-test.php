<?php
// Test 1: Can PHP output anything?
echo "✅ PHP is working!<br>";

// Test 2: Can we load composer?
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "✅ Composer autoload loaded<br>";
} else {
    die("❌ Composer not found. Run: composer install");
}

// Test 3: Can we instantiate TCPDF?
try {
    $tcpdf = new TCPDF();
    echo "✅ TCPDF class instantiated<br>";
} catch (Exception $e) {
    die("❌ TCPDF Error: " . $e->getMessage());
}

// Test 4: Can we instantiate FPDI?
try {
    $fpdi = new setasign\Fpdi\TcpdfFpdi();
    echo "✅ FPDI class instantiated<br>";
} catch (Exception $e) {
    die("❌ FPDI Error: " . $e->getMessage());
}

echo "<br><b>All tests passed! The issue is in your PDF generation logic.</b>";
echo "<br>Check your XAMPP error log: C:\\xampp\\apache\\logs\\error.log";
?>