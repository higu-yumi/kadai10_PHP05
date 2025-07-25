<?php

session_start();

// db_helper.php を読み込む
require_once 'db_helper.php';

// エラーメッセージの表示ロジック
$errorMessage = '';
if (isset($_GET['error'])) {
  switch ($_GET['error']) {
    case 'not_logged_in':
      $errorMessage = 'ログインが必要です。';
      break;
    case 'db_connection_failed':
      $errorMessage = '現在、システムエラーが発生しています。しばらく時間をおいてから再度お試しください。';
      break;
    case 'unexpected_error':
      $errorMessage = '予期せぬエラーが発生しました。システム管理者にお問い合わせください。';
      break;
    case 'invalid_record_type': // insert.php などから無効なタイプが来た場合
      $errorMessage = '無効な操作が行われました。'; // login.phpにリダイレクトされるなら汎用的なメッセージ
      break;
    case 'logged_out': // logout.php からのリダイレクト用
      $errorMessage = 'ログアウトしました。';
      break;
    default:
      $errorMessage = '正しい情報をご入力ください';
      break;
  }
}
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

    <!-- エラーメッセージ表示 -->
    <?php if (!empty($errorMessage)): ?>
      <p style="color: red; font-weight: bold; text-align: center; margin-top: 20px;">
        <?= h($errorMessage) ?>
      </p>
    <?php endif; ?>
    <p class="p_text">ログインIDとパスワードを入力して「ログインする」をクリックしてください。</p>
    <section class="section1">

      <!-- ログインフォーム -->
      <form name="form1" action="login_act.php" method="post">
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
        <input type="submit" value="ログインする" id="log_in">

        <p class="repass">パスワードを忘れた方は<span class="re"><a href="#">こちら</a></span>から&ensp;/&ensp;新規ユーザ登録は<span class="re"><a href="./user.php">こちら</a></span>から</p>

      </form>
    </section>
    <div><img src="./img/wish_b.png" alt="本のイラスト" class="wish_b"></div>
  </main>
  <footer>
    <p>&copy;2025 わたしと本&nbsp;/&nbsp;PHP</p>
  </footer>

</body>

</html>