<?php
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($username === '' || $password === '' || $password_confirm === '') {
        $errors[] = 'すべての項目を入力してください。';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'パスワードと確認用パスワードが一致しません。';
    }

    if (mb_strlen($username) > 50) {
        $errors[] = 'ユーザー名が長すぎます。（50文字以内）';
    }

    if (empty($errors)) {
        try {
            $pdo = new PDO(
                'mysql:host=db;dbname=reservation_db;charset=utf8mb4',
                'appuser',
                'apppass',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // 同じユーザー名がすでに存在しないかチェック
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
            $stmt->execute([':username' => $username]);
            if ($stmt->fetch()) {
                $errors[] = 'このユーザー名は既に使われています。';
            } else {
                // 登録
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (username, password) VALUES (:username, :password)'
                );
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $hash,
                ]);

                $newUserId = $pdo->lastInsertId();
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['username'] = $username;

                header('Location: calendar.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'データベースエラーが発生しました。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>新規登録 - 予約アプリ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="header">
    <div class="title">📅 予約アプリ</div>
  </div>

  <div class="auth-wrapper">
    <h1 class="auth-title">新規登録</h1>

    <?php if (!empty($errors)): ?>
      <?php foreach ($errors as $err): ?>
        <p class="auth-error"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endforeach; ?>
    <?php endif; ?>

    <form method="POST" class="auth-form">
      <div>
        <label>ユーザー名</label>
        <input type="text" name="username" required
               value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label>パスワード</label>
        <input type="password" name="password" required>
      </div>
      <div>
        <label>パスワード（確認用）</label>
        <input type="password" name="password_confirm" required>
      </div>
      <button type="submit">登録する</button>
    </form>

    <p style="margin-top:1rem; text-align:center; font-size:0.9rem;">
      すでにアカウントをお持ちの方は
      <a href="login.php">ログインはこちら</a>
    </p>
  </div>
</body>
</html>
