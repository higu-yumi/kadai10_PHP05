<?php
session_start();
// DB接続
require_once 'db_helper.php';
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>わたしと本</title>
  <link rel="stylesheet" href="./css/reset.css">
  <link rel="stylesheet" href="./css/login_out.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&family=Shippori+Mincho+B1&display=swap"
    rel="stylesheet">
</head>

<body>
  <header>
    <h1>わたしと本</h1>
  </header>
  <main>
    <p class="p_text">ユーザーフラグを選択し、ログインIDとパスワードを入力して登録ボタンをクリックしてください。</p>
    <section class="section1">
      <!-- 登録フォーム -->
      <form name="form1" action="user_insert.php" method="post">
        <!-- ユーザーフラグ -->
        <div class="form_g">
          <label for="flag_user" class="user_label">利用者</label>
          <input type="radio" name="user_flag" value="1" id="flag_user">
          <label for="flag_admin" record_label class="user_label">管理者</label>
          <input type="radio" name="user_flag" value="0" id="flag_admin" checked>
        </div>
        <!-- 名前 -->
        <div class="form_g">
          <label for="name">名前</label>
          <input type="text" name="name" id="name" placeholder="名前を入力してください">
        </div>
        <!-- ログインID -->
        <div class="form_g">
          <label for="login_id">ログインID</label>
          <input type="text" name="lid" id="login_id" placeholder="ログインIDを入力してください">
        </div>
        <!-- パスワード -->
        <div class="form_g">
          <label for="login_pass">パスワード</label>
          <input type="password" name="lpw" id="login_pass" placeholder="パスワードを入力してください">
        </div>
        <input type="submit" value="ユーザ登録する" id="regist">
        <p class="repass">ログインは<span class="re"><a href="./login.php">こちら</a></span>から</p>
      </form>
    </section>
    <div><img src="./img/read_b.png" alt="本のイラスト" class="read_b"></div>
  </main>
  <footer>
    <p>&copy;2025 わたしと本&nbsp;/&nbsp;PHP</p>
  </footer>

</html>