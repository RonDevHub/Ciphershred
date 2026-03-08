<?php
require_once __DIR__ . '/../core/Database.php';
$id = $_GET['id'] ?? '';
$db = Database::getConnection();
$stmt = $db->prepare("SELECT * FROM secrets WHERE id = ?");
$stmt->execute([$id]);
$secret = $stmt->fetch(PDO::FETCH_ASSOC);

if ($secret) {
    $path = "/var/www/html/storage/" . $id;
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$secret['filename'].'"');
    readfile($path);
    
    // PHYSISCHES LÖSCHEN NACH DEM SENDEN
    unlink($path);
    $db->prepare("DELETE FROM secrets WHERE id = ?")->execute([$id]);
} else {
    http_response_code(404);
}