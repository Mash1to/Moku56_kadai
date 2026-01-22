<?php
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $join_code = trim($_POST['join_code'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($join_code === '' || $username === '' || $password === '' || $password_confirm === '') {
        $errors[] = 'すべての項目を入力してください。';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'パスワードと確認用パスワードが一致しません。';
    }

    if (mb_strlen($username) > 50) {
        $errors[] = 'ユーザー名が長すぎます。（50文字以内）';
    }

    if (mb_strlen($join_code) > 32) {
        $errors[] = '組織コードが不正です。';
    }

    if (empty($errors)) {
        try {
            $pdo = new PDO(
                'mysql:host=db;dbname=reservation_db;charset=utf8mb4',
                'appuser',
                'apppass',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // 1) 組織コードから org_id を取得
            $stmt = $pdo->prepare('SELECT id FROM organizations WHERE join_code = :code');
            $stmt->execute([':code' => $join_code]);
            $org = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$org) {
                $errors[] = '組織コードが見つかりません。管理者に確認してください。';
            } else {
                $org_id = (int)$org['id'];

                // 2) 同じユーザー名が存在しないかチェック
                $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
                $stmt->execute([':username' => $username]);
                if ($stmt->fetch()) {
                    $errors[] = 'このユーザー名は既に使われています。';
                } else {
                    // 3) 登録（roleは通常 user）
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare(
                        'INSERT INTO users (org_id, username, password, role) VALUES (:org_id, :username, :password, :role)'
                    );
                    $stmt->execute([
                        ':org_id' => $org_id,
                        ':username' => $username,
                        ':password' => $hash,
                        ':role' => 'user',
                    ]);

                    $_SESSION['user_id'] = (int)$pdo->lastInsertId();
                    $_SESSION['username'] = $username;
                    $_SESSION['org_id'] = $org_id;
                    $_SESSION['role'] = 'user';

                    header('Location: calendar.php');
                    exit;
                }
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
        <label>組織コード</label>
        <input type="text" name="join_code" maxlength="32" required placeholder="例）ORG-1234">
      </div>
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
