<?php
require_once __DIR__ . '/../../core/Database.php';
header('Content-Type: application/json');

try {
    $db = Database::getConnection();
    $id = bin2hex(random_bytes(16));
    $expires = (int)($_POST['expires'] ?? 3600);
    $content = $_POST['content'] ?? null;
    $isFile = (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) ? 1 : 0;
    $storagePath = "/var/www/html/storage/" . $id;

    if (!$content && !$isFile) throw new Exception("Keine Daten empfangen.");

    if ($isFile) {
        $file = $_FILES['file'];
        if ($file['size'] > 50 * 1024 * 1024) throw new Exception("Datei zu groß (50MB Limit).");
        move_uploaded_file($file['tmp_name'], $storagePath);
        $name = $file['name'];
    } else {
        file_put_contents($storagePath, $content);
        $name = "note.enc";
    }

    $stmt = $db->prepare("INSERT INTO secrets (id, filename, expires_at, is_file) VALUES (?, ?, ?, ?)");
    $stmt->execute([$id, $name, time() + $expires, $isFile]);

    echo json_encode(['success' => true, 'id' => $id]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}