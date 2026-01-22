<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>予約アプリ - カレンダー</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/style.css">
</head>

<body>
  <div class="header">
    <div class="title">📅 予約アプリ</div>
    <p>
      カレンダーで予約を確認できます<br>
      ログイン中:
      <strong><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></strong>
      ｜ <a href="logout.php">ログアウト</a>
      ｜ <a href="index.php">ホームへ</a>
    </p>
  </div>

  <div class="calendar-container">
    <div id="calendar"></div>
  </div>

  <div id="tooltip" class="tooltip-custom"></div>

  <!-- 予約ダイアログ -->
  <dialog id="reserveDialog" class="reserve-dialog">
    <form method="dialog" id="reserveForm" class="reserve-form">
      <h2 class="reserve-title">予約を作成</h2>
      <div id="reserveError" class="reserve-error" aria-live="polite"></div>


      <div class="reserve-row">
        <label>開始</label>
        <input type="text" id="rStart" readonly>
      </div>

      <div class="reserve-row">
        <label>終了</label>
        <input type="text" id="rEnd" readonly>
      </div>

      <div class="reserve-row">
        <label>予約内容（タイトル）*</label>
        <input type="text" id="rTitle" required maxlength="255" placeholder="例）打ち合わせ">
      </div>

      <div class="reserve-row">
        <label>場所（必須）</label>
        <input type="text" id="rLocation" maxlength="100" placeholder="例）会議室A / オンライン" required>
      </div>

      <div class="reserve-row">
        <label>誰が（担当/利用者）</label>
        <input type="text" id="rWho" maxlength="100" placeholder="例）山田 / 田中ゼミ">
      </div>

      <div class="reserve-row">
        <label>詳細メモ</label>
        <textarea id="rDesc" rows="3" placeholder="補足事項など"></textarea>
      </div>

      <div class="reserve-actions">
        <button type="button" id="reserveCancel" class="btn-sub">キャンセル</button>
        <button type="submit" class="btn-main">登録</button>
      </div>
    </form>
  </dialog>

  <script src="assets/fullcalendar/index.global.min.js"></script>
  <script src="assets/script.js"></script>
</body>
</html>
