<?php
// includes/device_check.php

function isDesktop() {
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
    
    // Mobile keywords
    $mobileKeywords = [
        'android', 'iphone', 'ipad', 'ipod', 'blackberry', 'iemobile',
        'opera mini', 'opera mobi', 'mobile', 'tablet', 'kindle', 'silk',
        'webos', 'windows phone', 'palm', 'symbian'
    ];
    
    foreach ($mobileKeywords as $keyword) {
        if (strpos($userAgent, $keyword) !== false) {
            return false;
        }
    }
    
    // Additional check: screen width via HTTP headers (if available)
    if (isset($_SERVER['HTTP_X_UA_Device'])) {
        $device = strtolower($_SERVER['HTTP_X_UA_Device']);
        if (in_array($device, ['mobile', 'tablet'])) {
            return false;
        }
    }
    
    return true;
}

function getDeviceType() {
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
    
    if (strpos($userAgent, 'ipad') !== false || 
        (strpos($userAgent, 'android') !== false && strpos($userAgent, 'mobile') === false)) {
        return 'Tablet';
    }
    
    if (strpos($userAgent, 'mobile') !== false || 
        strpos($userAgent, 'iphone') !== false ||
        strpos($userAgent, 'android') !== false) {
        return 'Mobile';
    }
    
    return 'Desktop';
}
?>