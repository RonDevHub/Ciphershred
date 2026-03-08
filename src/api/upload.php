<?php
require_once __DIR__ . '/../core/Database.php';
header('Content-Type: application/json');

$id = bin2hex(random_bytes(16));
$expires = (int)($_POST['expires'] ?? 3600);
$isFile = isset($_FILES['file']) ? 1 : 0;
$storagePath = "/var/www/html/storage/" . $id;

try {
    $db = Database::getConnection();
    if ($isFile) {
        $file = $_FILES['file'];
        if ($file['size'] > 50 * 1024 * 1024) throw new Exception("Limit 50MB exceeded");
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), ['txt','key','pub','json','yaml','conf','pdf','zip','md'])) throw new Exception("Invalid Ext");
        move_uploaded_file($file['tmp_name'], $storagePath);
        $filename = $file['name'];
    } else {
        file_put_contents($storagePath, $_POST['content']);
        $filename = "note.enc";
    }

    $stmt = $db->prepare("INSERT INTO secrets VALUES (?, ?, ?, ?)");
    $stmt->execute([$id, $filename, time() + $expires, $isFile]);
    echo json_encode(['id' => $id]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}