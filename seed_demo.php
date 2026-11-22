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
    'mysql:host=' . $c['db_host'] . ';port=' . ($c['db_port'] ?? 3306) . ';dbname=' . $c['db_name'] . ';charset=utf8mb4',
    $c['db_user'], $c['db_password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$samples = [
    ['Космос', 'Изображения космоса', 'Чёрная дыра', 'Изображение предоставлено владельцем коллекции.', 'black-hole.png'],
    ['Архитектура', 'Формы, линии и городское пространство', 'Арки музея', 'Демонстрационное изображение, созданное с помощью ИИ.', 'architecture.png'],
    ['Природа', 'Пейзажи и природные детали', 'Озеро на рассвете', 'Демонстрационное изображение, созданное с помощью ИИ.', 'nature.png'],
];

$insertAlbum = $pdo->prepare('INSERT IGNORE INTO albums (title, description) VALUES (?, ?)');
$findAlbum = $pdo->prepare('SELECT id FROM albums WHERE title = ?');
$findPhoto = $pdo->prepare('SELECT id FROM photos WHERE album_id = ? AND title = ?');
$insertPhoto = $pdo->prepare('INSERT INTO photos (album_id, title, description, filename, original_name, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)');
$added = 0;
foreach ($samples as [$albumTitle, $albumDescription, $title, $description, $asset]) {
    $insertAlbum->execute([$albumTitle, $albumDescription]);
    $findAlbum->execute([$albumTitle]);
    $albumId = (int)$findAlbum->fetchColumn();
    $findPhoto->execute([$albumId, $title]);
    if ($findPhoto->fetchColumn()) continue;

    $source = __DIR__ . '/assets/' . $asset;
    $filename = bin2hex(random_bytes(16)) . '.png';
    $target = __DIR__ . '/uploads/' . $filename;
    if (!copy($source, $target)) throw new RuntimeException('Cannot copy demo photo to uploads.');
    try {
        $insertPhoto->execute([$albumId, $title, $description, $filename, $asset, 'image/png', filesize($target)]);
    } catch (Throwable $error) {
        unlink($target);
        throw $error;
    }
    $added++;
}
echo "Demo photos added: $added.\n";
