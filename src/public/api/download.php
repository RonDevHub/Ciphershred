<?php
require_once __DIR__ . '/../../core/Database.php';
$id = $_GET['id'] ?? '';
$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM secrets WHERE id = ?");
$stmt->execute([$id]);
$secret = $stmt->fetch(PDO::FETCH_ASSOC);

if ($secret) {
    $path = "/var/www/html/storage/" . $id;
    if (file_exists($path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$secret['filename'].'"');
        readfile($path);
        unlink($path);
        $db->prepare("DELETE FROM secrets WHERE id = ?")->execute([$id]);
        exit;
    }
}
http_response_code(404);