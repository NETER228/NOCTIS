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
    ['Архитектура', 'Формы, линии и городское пространство', 'Башни Axel в Копенгагене', 'Фото: Joakim Nådell / Unsplash. Источник: https://unsplash.com/photos/K67sBVqLLuw', 'pinterest-axel-towers.jpg'],
    ['Люди', 'Портреты и моменты жизни', 'Отдых в траве', 'Фото: Sonia Dauer / Unsplash. Источник: https://unsplash.com/photos/SBZ0bK2gkAs', 'pinterest-field.jpg'],
    ['Природа', 'Пейзажи и природные детали', 'Снежный горный хребет', 'Фото: Will Turner / Unsplash. Источник: https://unsplash.com/photos/tNt7fOOOgKA', 'pinterest-snow-range.jpg'],
    ['Архитектура', 'Формы, линии и городское пространство', 'Спиральное здание', 'Фото: Claudio Schwarz / Unsplash. Источник: https://unsplash.com/photos/XYnRUulQnF8', 'pinterest-spiral-building.jpg'],
    ['Природа', 'Пейзажи и природные детали', 'Горные вершины', 'Фото: Kerensa Pickett / Unsplash. Источник: https://unsplash.com/photos/GjpUV4k76F8', 'pinterest-mountain-peaks.jpg'],
    ['Архитектура', 'Формы, линии и городское пространство', 'Современное здание', 'Фото: Julien Moreau / Unsplash. Источник: https://unsplash.com/photos/688Fna1pwOQ', 'pinterest-modern-building.jpg'],
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
    $extension = strtolower(pathinfo($asset, PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $mimeType = $extension === 'png' ? 'image/png' : 'image/jpeg';
    $target = __DIR__ . '/uploads/' . $filename;
    if (!copy($source, $target)) throw new RuntimeException('Cannot copy demo photo to uploads.');
    try {
        $insertPhoto->execute([$albumId, $title, $description, $filename, $asset, $mimeType, filesize($target)]);
    } catch (Throwable $error) {
        unlink($target);
        throw $error;
    }
    $added++;
}
echo "Demo photos added: $added.\n";
