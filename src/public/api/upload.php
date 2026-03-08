<?php
require_once __DIR__ . '/../../core/Database.php';
header('Content-Type: application/json');

$id = bin2hex(random_bytes(16));
$expires = (int)($_POST['expires'] ?? 3600);
$isFile = isset($_FILES['file']) ? 1 : 0;
$storagePath = "/var/www/html/storage/" . $id;

try {
    $db = Database::getConnection();
    if ($isFile) {
        if ($_FILES['file']['size'] > 50 * 1024 * 1024) throw new Exception("50MB Limit");
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['txt','key','pub','json','yaml','conf','pdf','zip','md'])) throw new Exception("Typ nicht erlaubt");
        move_uploaded_file($_FILES['file']['tmp_name'], $storagePath);
        $name = $_FILES['file']['name'];
    } else {
        file_put_contents($storagePath, $_POST['content']);
        $name = "note.enc";
    }
    $stmt = $db->prepare("INSERT INTO secrets VALUES (?, ?, ?, ?)");
    $stmt->execute([$id, $name, time() + $expires, $isFile]);
    echo json_encode(['id' => $id]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}