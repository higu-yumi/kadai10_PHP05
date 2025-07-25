<?php

// セッションにこれ重要！
session_start();

// DB接続
require_once 'db_helper.php';

// ログイン状態の確認（未ログインはlogin.phpへ）
sessionCheck();

// 編集対象の本のIDを取得
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// どのリストから来たか(read or wish)を取得
$from = filter_input(INPUT_GET, 'from');
if (!in_array($from, ['read', 'wish'])) {
  $from = 'read'; // デフォルトとして'read'を設定
}

// IDが不正（数値ではない、または存在しない）だった場合の処理
if (!$id) {
  // 例: リストページにリダイレクトしてエラーメッセージを表示
  header('Location: read_list.php?error=invalid_id');
  exit;
}

// 編集対象の本の情報を取得（オプション：フォームに本のタイトルを表示するなど）
$book = bookid($id); // db_helper.php の bookid 関数を使用

if (!$book || $book['user_id'] !== $_SESSION['user_id']) {
  // 本が見つからない、または現在のユーザーのものではない場合
  // セキュリティのため、他人の本のIDを推測してアクセスできないようにする
  header('Location: ' . $from . '_list.php?error=access_denied');
  exit;
}

?>


<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>画像アップロード - <?= h($book['title'] ?? '不明な本') ?></title>
  <link rel="stylesheet" href="./css/reset.css">
  <link rel="stylesheet" href="./css/file_view.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&family=Shippori+Mincho+B1&display=swap"
    rel="stylesheet">
</head>

<body>
  <header>
    <div class="logout_text"><a href="logout.php">ログアウト</a></div>
    <h2>表紙をアップロードする</h2>
  </header>
  <main>
    <div class="upload-container">
      <h2>「<?= h($book['title'] ?? '不明な本') ?>」の表紙をアップロード</h2>

      <?= $uploadMessage ?> <?php if (!empty($book['cover_url'])): ?>
        <div class="current-cover">
          <p>現在の表紙:</p>
          <img src="<?= h($book['cover_url']) ?>" alt="現在の本の表紙">
        </div>
      <?php endif; ?>

      <form action="file_upload_act.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= h($id) ?>">
        <input type="hidden" name="from" value="<?= h($from) ?>">
        <input type="file" name="upfile" accept="image/*"> <input type="submit" value="アップロード">
      </form>
      <p class="back-link">
        <a href="<?= h($from) ?>_list.php">表紙を確定してリストに戻る</a>
      </p>
    </div>
  </main>
  <footer>
    <p>&copy;2025 わたしと本&nbsp;/&nbsp;PHP</p>
  </footer>
</body>

</html>