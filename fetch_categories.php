<?php
// fetch_categories.php
session_start();
require_once 'db/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['login_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$parent_id = intval($_POST['parent_id'] ?? 0);

if ($parent_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parent ID']);
    exit;
}

$stmt = mysqli_prepare($db, "SELECT category_id, category_name FROM category WHERE parent_category = ? AND status = 1 ORDER BY category_name ASC");
mysqli_stmt_bind_param($stmt, "i", $parent_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$categories = [];
while ($row = mysqli_fetch_assoc($result)) {
    $categories[] = [
        'category_id' => $row['category_id'],
        'category_name' => htmlspecialchars($row['category_name'])
    ];
}

mysqli_stmt_close($stmt);

echo json_encode([
    'success' => true,
    'data' => $categories
]);
?>