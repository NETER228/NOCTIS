<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script runs only from the command line.');
}

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    exit("Create config.php first.\n");
}
$c = require $configFile;
$pdo = new PDO(
    'mysql:host=' . $c['db_host'] . ';dbname=' . $c['db_name'] . ';charset=utf8mb4',
    $c['db_user'], $c['db_password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->prepare('INSERT IGNORE INTO albums (title, description) VALUES (?, ?)')
    ->execute(['Космос', 'Изображения космоса']);
$album = $pdo->query("SELECT id FROM albums WHERE title = 'Космос'")->fetchColumn();
$check = $pdo->prepare('SELECT id FROM photos WHERE album_id = ? AND title = ?');
$check->execute([$album, 'Чёрная дыра']);
if ($check->fetchColumn()) {
    exit("Demo photo already exists.\n");
}

$source = __DIR__ . '/assets/black-hole.png';
$filename = bin2hex(random_bytes(16)) . '.png';
$target = __DIR__ . '/uploads/' . $filename;
if (!copy($source, $target)) {
    throw new RuntimeException('Cannot copy demo photo to uploads.');
}
try {
    $pdo->prepare('INSERT INTO photos (album_id, title, description, filename, original_name, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$album, 'Чёрная дыра', 'Изображение предоставлено владельцем коллекции.', $filename, 'black-hole.png', 'image/png', filesize($target)]);
} catch (Throwable $error) {
    unlink($target);
    throw $error;
}
echo "Demo photo added.\n";
