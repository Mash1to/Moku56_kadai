<?php
session_start();

$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $err = 'ユーザー名とパスワードを入力してください。';
    } else {
        try {
            $pdo = new PDO(
                'mysql:host=db;dbname=reservation_db;charset=utf8mb4',
                'appuser',
                'apppass',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: calendar.php');
                exit;
            } else {
                $err = 'ユーザー名またはパスワードが正しくありません。';
            }
        } catch (PDOException $e) {
            $err = 'データベースエラーが発生しました。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ログイン - 予約アプリ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="header">
    <div class="title">📅 予約アプリ</div>
  </div>

  <div class="auth-wrapper">
    <h1 class="auth-title">ログイン</h1>

    <?php if ($err): ?>
      <p class="auth-error"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="POST" class="auth-form">
      <div>
        <label>ユーザー名</label>
        <input type="text" name="username" required>
      </div>
      <div>
        <label>パスワード</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit">ログイン</button>
    </form>

    <p style="margin-top:1rem; text-align:center; font-size:0.9rem;">
      アカウントをお持ちでない方は
      <a href="register.php">新規登録はこちら</a>
    </p>
  </div>
</body>
</html>
