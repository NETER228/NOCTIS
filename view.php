<?php
$hero = $photos[0]['filename'] ?? null;
$count = count($photos);
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#08090d">
  <title>NOCTIS — личный фотоархив</title>
  <meta name="description" content="Личный каталог цифровых фотографий: альбомы, поиск и любимые кадры.">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="site-shell">
    <header class="topbar wrap">
      <a class="wordmark" href="index.php" aria-label="NOCTIS — на главную"><span class="wordmark-symbol">✳</span> NOCTIS<span class="wordmark-dot">.</span></a>
      <nav aria-label="Основная навигация">
        <a href="#gallery">Коллекция</a>
        <a href="#about">О проекте</a>
        <a class="nav-manage" href="#manage"><?= admin() ? 'Управление ↗' : 'Вход ↗' ?></a>
      </nav>
    </header>

    <main>
      <section class="hero" <?= $hero ? 'style="--hero-image:url(\'uploads/' . h($hero) . '\')"' : '' ?>>
        <div class="hero-image" role="img" aria-label="Избранная фотография коллекции"></div>
        <div class="hero-noise"></div>
        <div class="hero-content wrap">
          <div class="hero-index"><span class="line"></span> ПЕРСОНАЛЬНЫЙ ФОТОАРХИВ <span class="year">/ 2026</span></div>
          <h1>Сохранять<br><em>мгновения.</em></h1>
          <p>Коллекция изображений, историй и мест.<br>Всё важное — в одном пространстве.</p>
          <a class="round-link" href="#gallery"><span class="round-icon">↘</span><span>Исследовать коллекцию</span></a>
        </div>
        <div class="hero-bottom wrap"><span>01 / АРХИВ</span><span>ЛИСТАЙТЕ ВНИЗ ↓</span></div>
      </section>

      <?php if ($message): ?><div class="notice wrap" role="status"><?= h($message) ?></div><?php endif; ?>

      <section id="gallery" class="gallery-section wrap">
        <div class="section-kicker"><span>01</span><span>КОЛЛЕКЦИЯ</span><span class="section-rule"></span><span><?= $count ?> <?= $count === 1 ? 'СНИМОК' : 'СНИМКОВ' ?></span></div>
        <div class="gallery-intro"><div><h2>Галерея<span class="accent">.</span></h2><p>Кадры, которые хочется сохранить.</p></div><span class="decorative-star" aria-hidden="true">✳</span></div>

        <div class="album-pills" aria-label="Выбор альбома">
          <a class="pill <?= !$album ? 'active' : '' ?>" href="index.php#gallery">Все работы</a>
          <?php foreach ($albums as $a): ?><a class="pill <?= $album === (int)$a['id'] ? 'active' : '' ?>" href="?album=<?= (int)$a['id'] ?>#gallery"><?= h($a['title']) ?></a><?php endforeach; ?>
        </div>

        <form class="searchbar" method="get" aria-label="Поиск по фотографиям">
          <span class="search-icon" aria-hidden="true">⌕</span>
          <label class="sr-only" for="photo-search">Поиск по названию и описанию</label>
          <input id="photo-search" name="q" value="<?= h($q) ?>" placeholder="Найти фотографию или историю…">
          <?php if ($album): ?><input type="hidden" name="album" value="<?= $album ?>"><?php endif; ?>
          <button type="submit">Найти <span aria-hidden="true">↗</span></button>
          <?php if ($q !== ''): ?><a class="clear-search" href="index.php#gallery">Сбросить</a><?php endif; ?>
        </form>

        <?php if (!$photos): ?><div class="empty"><span>✳</span><h3>Здесь пока пусто</h3><p>Попробуйте другой запрос или добавьте фотографию.</p></div><?php endif; ?>
        <div class="photo-grid">
          <?php foreach ($photos as $i => $p): ?>
            <article class="photo-card">
              <button class="photo-open" type="button" data-src="uploads/<?= h($p['filename']) ?>" data-title="<?= h($p['title']) ?>" data-description="<?= h($p['description']) ?>" aria-label="Открыть: <?= h($p['title']) ?>">
                <img loading="<?= $i === 0 ? 'eager' : 'lazy' ?>" src="uploads/<?= h($p['filename']) ?>" alt="<?= h($p['title']) ?>">
                <span class="image-corner" aria-hidden="true">↗</span>
              </button>
              <div class="photo-meta"><span class="photo-number"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?> / <?= h($p['album_title'] ?: 'Без альбома') ?></span><span><?= h($p['taken_at'] ?: substr($p['created_at'], 0, 10)) ?></span></div>
              <div class="photo-heading"><h3><?= h($p['title']) ?></h3><span aria-hidden="true">↗</span></div>
              <?php if ($p['description']): ?><p class="photo-description"><?= h($p['description']) ?></p><?php endif; ?>
              <?php if (admin()): ?><details class="edit-details"><summary>Изменить данные</summary><form method="post" class="edit-form"><input type="hidden" name="csrf" value="<?= h(token()) ?>"><input type="hidden" name="action" value="photo_update"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><label>Название<input name="title" maxlength="160" required value="<?= h($p['title']) ?>"></label><label>Описание<textarea name="description"><?= h($p['description']) ?></textarea></label><label>Альбом<select name="album_id"><option value="">Без альбома</option><?php foreach ($albums as $a): ?><option value="<?= (int)$a['id'] ?>" <?= (int)$p['album_id'] === (int)$a['id'] ? 'selected' : '' ?>><?= h($a['title']) ?></option><?php endforeach; ?></select></label><label>Дата съёмки<input type="date" name="taken_at" value="<?= h($p['taken_at']) ?>"></label><button class="solid-button" type="submit">Сохранить</button></form><form method="post" onsubmit="return confirm('Удалить фотографию?')"><input type="hidden" name="csrf" value="<?= h(token()) ?>"><input type="hidden" name="action" value="photo_delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="delete-button" type="submit">Удалить фотографию</button></form></details><?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <section id="about" class="about-section"><div class="wrap about-layout"><div class="about-label">02 / О ПРОЕКТЕ</div><div><h2>У каждого кадра<br><em>своя история.</em></h2><p>NOCTIS помогает собирать фотографии в альбомы, находить нужные снимки и возвращаться к тому, что вдохновляет. Личный архив, в котором легко ориентироваться.</p><a href="#gallery" class="text-link">Вернуться к коллекции <span>↗</span></a></div><span class="about-orbit" aria-hidden="true">✳</span></div></section>

      <section id="manage" class="manage-section wrap"><div class="section-kicker"><span>03</span><span>ЛИЧНЫЙ КАБИНЕТ</span><span class="section-rule"></span></div><h2><?= admin() ? 'Управление архивом' : 'Вход в архив' ?><span class="accent">.</span></h2>
        <?php if (admin()): ?><div class="manage-grid"><form method="post" class="panel"><h3>Новый альбом <span>01</span></h3><input type="hidden" name="csrf" value="<?= h(token()) ?>"><input type="hidden" name="action" value="album_add"><label>Название<input name="title" maxlength="120" required placeholder="Например, Путешествия"></label><label>Описание<textarea name="description" placeholder="О чём этот альбом?"></textarea></label><button class="solid-button" type="submit">Создать альбом ↗</button></form><form method="post" enctype="multipart/form-data" class="panel"><h3>Новая фотография <span>02</span></h3><input type="hidden" name="csrf" value="<?= h(token()) ?>"><input type="hidden" name="action" value="upload"><label>Файл JPEG, PNG или WebP до 10 МБ<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required></label><label>Название<input name="title" maxlength="160" required placeholder="Название снимка"></label><label>Описание<textarea name="description" placeholder="Несколько слов о фотографии"></textarea></label><label>Альбом<select name="album_id"><option value="">Без альбома</option><?php foreach ($albums as $a): ?><option value="<?= (int)$a['id'] ?>"><?= h($a['title']) ?></option><?php endforeach; ?></select></label><label>Дата съёмки<input type="date" name="taken_at"></label><button class="solid-button" type="submit">Загрузить фото ↗</button></form></div><form method="post" class="logout"><input type="hidden" name="csrf" value="<?= h(token()) ?>"><input type="hidden" name="action" value="logout"><button type="submit">Выйти из кабинета ↗</button></form>
        <?php else: ?><form method="post" class="panel login"><p>Управлять фотографиями может только владелец архива.</p><input type="hidden" name="csrf" value="<?= h(token()) ?>"><input type="hidden" name="action" value="login"><label>Пароль администратора<input type="password" name="password" required autocomplete="current-password" placeholder="Введите пароль"></label><button class="solid-button" type="submit">Войти ↗</button></form><?php endif; ?>
      </section>
    </main>
    <footer class="footer"><div class="wrap footer-inner"><a class="wordmark" href="index.php">✳ NOCTIS<span class="wordmark-dot">.</span></a><span>Личный фотоархив / 2026</span><a href="#" aria-label="Наверх">Наверх ↑</a></div></footer>
  </div>

  <dialog id="lightbox" class="lightbox" aria-label="Просмотр фотографии"><form method="dialog"><button class="lightbox-close" aria-label="Закрыть">×</button></form><img alt=""><div class="lightbox-caption"><h3></h3><p></p></div></dialog>
  <script src="app.js" defer></script>
</body>
</html>
