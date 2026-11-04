<?php
declare(strict_types=1);
session_start();

function config(): array {
    $file = __DIR__ . '/config.php';
    if (!is_file($file)) {
        http_response_code(503);
        exit('Скопируйте config.example.php в config.php и настройте базу данных.');
    }
    return require $file;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $c = config();
        $pdo = new PDO(
            'mysql:host=' . $c['db_host'] . ';dbname=' . $c['db_name'] . ';charset=utf8mb4',
            $c['db_user'], $c['db_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

function h(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function admin(): bool { return !empty($_SESSION['admin']); }
function token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verifyPost(): void {
    if (!hash_equals(token(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403); exit('Ошибка проверки формы. Обновите страницу.');
    }
}
function redirect(string $url = 'index.php'): never { header('Location: ' . $url); exit; }
function flash(string $message): void { $_SESSION['flash'] = $message; }
function requireAdmin(): void {
    if (!admin()) { http_response_code(403); exit('Требуется вход администратора.'); }
}

$action = (string)($_POST['action'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyPost();
    try {
        if ($action === 'login') {
            $expected = (string)(config()['admin_password'] ?? '');
            if ($expected === '' || $expected === 'change-this-password') throw new RuntimeException('Сначала задайте пароль в config.php.');
            if (!hash_equals($expected, (string)($_POST['password'] ?? ''))) throw new RuntimeException('Неверный пароль.');
            session_regenerate_id(true); $_SESSION['admin'] = true; flash('Вы вошли как администратор.');
        } elseif ($action === 'logout') {
            requireAdmin(); $_SESSION = []; session_regenerate_id(true); flash('Вы вышли.');
        } elseif ($action === 'album_add') {
            requireAdmin();
            $title = trim((string)($_POST['title'] ?? ''));
            if ($title === '' || mb_strlen($title) > 120) throw new RuntimeException('Название альбома: от 1 до 120 символов.');
            $stmt = db()->prepare('INSERT INTO albums (title, description) VALUES (?, ?)');
            $stmt->execute([$title, trim((string)($_POST['description'] ?? ''))]);
            flash('Альбом создан.');
        } elseif ($action === 'upload') {
            requireAdmin();
            $file = $_FILES['photo'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 10 * 1024 * 1024) throw new RuntimeException('Выберите файл до 10 МБ.');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($types[$mime])) throw new RuntimeException('Поддерживаются JPEG, PNG и WebP.');
            $img = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($file['tmp_name']),
                'image/png' => @imagecreatefrompng($file['tmp_name']),
                default => @imagecreatefromwebp($file['tmp_name']),
            };
            if (!$img) throw new RuntimeException('Изображение повреждено.');
            if (imagesx($img) * imagesy($img) > 40000000) { imagedestroy($img); throw new RuntimeException('Слишком большое разрешение.'); }
            $title = trim((string)($_POST['title'] ?? ''));
            if ($title === '' || mb_strlen($title) > 160) { imagedestroy($img); throw new RuntimeException('Название фотографии: от 1 до 160 символов.'); }
            $albumId = filter_var($_POST['album_id'] ?? '', FILTER_VALIDATE_INT) ?: null;
            if ($albumId) {
                $check = db()->prepare('SELECT id FROM albums WHERE id = ?');
                $check->execute([$albumId]);
                if (!$check->fetch()) throw new RuntimeException('Выбранный альбом не найден.');
            }
            $name = bin2hex(random_bytes(16)) . '.' . $types[$mime];
            $path = __DIR__ . '/uploads/' . $name;
            $saved = match ($mime) {
                'image/jpeg' => imagejpeg($img, $path, 88),
                'image/png' => imagepng($img, $path, 6),
                default => imagewebp($img, $path, 85),
            };
            imagedestroy($img);
            if (!$saved) throw new RuntimeException('Не удалось сохранить изображение.');
            try {
                $stmt = db()->prepare('INSERT INTO photos (album_id,title,description,filename,original_name,mime_type,file_size,taken_at) VALUES (?,?,?,?,?,?,?,?)');
                $date = trim((string)($_POST['taken_at'] ?? '')) ?: null;
                $stmt->execute([$albumId,$title,trim((string)($_POST['description'] ?? '')),$name,basename((string)$file['name']),$mime,filesize($path),$date]);
            } catch (Throwable $e) { unlink($path); throw $e; }
            flash('Фотография добавлена.');
        } elseif ($action === 'photo_update') {
            requireAdmin();
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
            $title = trim((string)($_POST['title'] ?? ''));
            if (!$id || $title === '' || mb_strlen($title) > 160) throw new RuntimeException('Проверьте название фотографии.');
            $albumId = filter_var($_POST['album_id'] ?? '', FILTER_VALIDATE_INT) ?: null;
            if ($albumId) {
                $check = db()->prepare('SELECT id FROM albums WHERE id = ?');
                $check->execute([$albumId]);
                if (!$check->fetch()) throw new RuntimeException('Выбранный альбом не найден.');
            }
            $stmt = db()->prepare('UPDATE photos SET title=?,description=?,album_id=?,taken_at=? WHERE id=?');
            $stmt->execute([$title,trim((string)($_POST['description'] ?? '')),$albumId,trim((string)($_POST['taken_at'] ?? '')) ?: null,$id]);
            flash('Данные обновлены.');
        } elseif ($action === 'photo_delete') {
            requireAdmin();
            $stmt = db()->prepare('SELECT filename FROM photos WHERE id=?');
            $stmt->execute([(int)($_POST['id'] ?? 0)]); $row = $stmt->fetch();
            if ($row) {
                db()->prepare('DELETE FROM photos WHERE id=?')->execute([(int)$_POST['id']]);
                $path = __DIR__ . '/uploads/' . $row['filename'];
                if (is_file($path)) unlink($path);
            }
            flash('Фотография удалена.');
        }
    } catch (Throwable $e) { flash('Ошибка: ' . $e->getMessage()); }
    redirect('index.php' . (in_array($action, ['upload','album_add'], true) ? '#manage' : ''));
}

$albums = db()->query('SELECT id,title,description FROM albums ORDER BY title')->fetchAll();
$album = filter_var($_GET['album'] ?? '', FILTER_VALIDATE_INT) ?: null;
$q = trim((string)($_GET['q'] ?? ''));
$where = []; $params = [];
if ($album) { $where[] = 'p.album_id = ?'; $params[] = $album; }
if ($q !== '') { $where[] = '(p.title LIKE ? OR p.description LIKE ?)'; $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%'; }
$sql = 'SELECT p.*,a.title AS album_title FROM photos p LEFT JOIN albums a ON a.id=p.album_id';
if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
$sql .= ' ORDER BY p.created_at DESC, p.id DESC';
$stmt = db()->prepare($sql); $stmt->execute($params); $photos = $stmt->fetchAll();
$message = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
require __DIR__ . '/view.php';
