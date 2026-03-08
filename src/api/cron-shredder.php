<?php
require_once __DIR__ . '/../core/Database.php';
$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM secrets WHERE expires_at < ?");
$stmt->execute([time()]);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    @unlink("/var/www/html/storage/" . $row['id']);
}
$db->prepare("DELETE FROM secrets WHERE expires_at < ?")->execute([time()]);