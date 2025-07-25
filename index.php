<?php

session_start();

// db_helper.php を読み込む
require_once 'db_helper.php';

// ログイン状態の確認（未ログインはlogin.phpへ）
sessionCheck();

//カレンダーの日付制御
$today = time_z();
// 読んだ本リストを取得
$read_books = bookStatus('read');
// 読みたい本リストを取得
$wish_books = bookStatus('wish');
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>わたしと本</title>
  <link rel="stylesheet" href="./css/reset.css">
  <link rel="stylesheet" href="./css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&family=Shippori+Mincho+B1&display=swap"
    rel="stylesheet">
</head>

<body>
  <header>
    <div class="logout_text"><a href="logout.php">ログアウト</a></div>
    <h1>わたしと本</h1>
    <p class="ai_text"><?= $_SESSION['name'] ?>さん、ようこそ</p>
  </header>
  <main>
    <section class="section1">
      <div class="col">
        <div class="box">
          <a href="./wish_list.php"><img src="./img/wish.png" alt="読みたい本のアイコン"></a>
        </div>
        <h2>読みたい本</h2>
      </div>
      <div class="col">
        <div class="box">
          <a href="./read_list.php"><img src="./img/read.png" alt="読んだ本のアイコン"></a>
        </div>
        <h2>読んだ本</h2>
      </div>
    </section>
    <section class="section2">
      <!-- 登録フォーム -->
      <form action="insert.php" method="post">
        <div class="form_g">
          <label for="record_wish" class="record_label">読みたい本</label>
          <input type="radio" name="record" value="wish" id="record_wish">
          <label for="record_read" record_label class="record_label">読んだ本</label>
          <input type="radio" name="record" value="read" id="record_read">
        </div>
        <div class="form_g">
          <label for="isbn_input" class="form_title">ISBNコード</label>
          <input type="text" name="isbn" id="isbn_input">
        </div>

        <div class="form_g">
          <label for="read_date">読んだ日</label>
          <!-- max=<?= $today ?>：明日以降を制御 -->
          <input type="date" name="read_date" id="read_date" max=<?= $today ?>>
        </div>
        <div class="form_g">
          <label for="comment" class="comment">コメント</label>
          <textarea name="comment" id="comment"></textarea>
        </div>
        <!-- 登録ボタン -->
        <input type="submit" value="本を登録する" class="btn">
      </form>
    </section>

    <section class="section3">
      <!-- 管理者のみ表示するボタン -->
      <?php
      if (isset($_SESSION['user_flag']) && $_SESSION['user_flag'] == 0) { ?>
        <a href="user_list.php" class="admin_btn">管理者ページへすすむ</a>
      <?php } ?>
    </section>

  </main>

  <footer>
    <p>&copy;2025 わたしと本&nbsp;/&nbsp;PHP</p>
  </footer>

</body>

</html>