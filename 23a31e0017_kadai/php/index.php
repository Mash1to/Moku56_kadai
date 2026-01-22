<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>予約アプリ - ホーム</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="header">
    <div class="title">📅 予約アプリ</div>
  </div>

  <div class="home-wrapper">
    <h1 class="home-title">ようこそ！</h1>
    <p class="home-sub">
      このアプリでは、カレンダー画面から<br>
      予約の確認・登録ができます。
    </p>

    <div class="home-buttons">
      <a href="calendar.php" class="btn-main">カレンダーを開く</a>
      <a href="login.php" class="btn-sub">ログイン</a>
      <a href="register.php" class="btn-sub">新規登録</a>
    </div>

    <div class="home-foot">
      <?php if (isset($_SESSION['username'])): ?>
        現在ログイン中:
        <strong><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></strong>
        ｜ <a href="logout.php">ログアウト</a>
      <?php else: ?>
        ログインしてから予約を操作できます。
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
