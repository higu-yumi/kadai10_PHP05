<?php

session_start();

// db_helper.php を読み込む
require_once 'db_helper.php';

// ログイン状態の確認（未ログインはlogin.phpへ）
sessionCheck();

//カレンダーを明日以降は選択できないように制御
date_default_timezone_set('Asia/Tokyo');
$today = date('Y-m-d');

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

// 取得したIDを使ってデータベースから既存の書籍情報を取得
// bookid() 関数は db_helper.php に定義済み
$book = bookid($id);

// 指定されたIDの本が見つからなかった場合の処理
// プログラムはユーザーの予期せぬ行動や悪意ある操作にも耐えられるように作る必要があるのでエラー処理必須！
if (!$book) {
  // リストページにリダイレクトしてエラーメッセージを表示
  $redirect_url = ($from === 'wish') ? 'wish_list.php' : 'read_list.php';
  redirect($redirect_url, ['error' => 'book_not_found']);
}

// URLからエラーメッセージを取得し、変数に格納する
$errorMessage = ''; // エラーメッセージを格納する変数を空で初期化
// エラー発生時のリダイレクトは GET を利用
if (isset($_GET['error']) && $_GET['error'] !== '') {
  // URLのクエリパラメーターからエラーメッセージを取得しセミコロンで分割
  $errorsFromUrl = explode(';', $_GET['error']);
  // 各エラーメッセージを念のためh()関数でエスケープし、JavaScriptのアラートで改行されるように \n で連結
  $errorMessage = implode('\n', array_map('h', $errorsFromUrl));
}

?>

<!------------------ HTML ------------------>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>わたしと本</title>
  <link rel="stylesheet" href="./css/reset.css">
  <link rel="stylesheet" href="./css/edit.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&family=Shippori+Mincho+B1&display=swap"
    rel="stylesheet">
</head>

<body>
  <header>
    <h2>〈情報を編集/更新する〉</h2>
  </header>
  <main>
    <div class="book_details">
      <h3 class="book_title">
        <?= h($book['title'] ?? 'タイトル不明') ?>
        <?php if (!empty($book['publisher'])): ?>
          (<?= h($book['publisher']) ?>)
        <?php endif; ?>
      </h3>
      <p class="book_author">
        <?php
        // $book['author'] の値を取得し、カンマとスペース、スラッシュを削除
        $displayAuthor = str_replace(',', '', $book['author'] ?? '著者不明');
        $displayAuthor = str_replace(' ', '', $displayAuthor);
        $displayAuthor = str_replace('／', '', $displayAuthor);
        echo h($displayAuthor);
        ?>
      </p>
    </div>

    <section class="section2">
      <!-- 登録フォーム -->
      <!-- POST推奨！ -->
      <form action="update.php" method="POST">
        <div class="form_g">
          <!-- $book['status'] の値に応じて、該当するラジオボタンに checked 属性を付ける -->
          <label for="record_wish" class="record_label">読みたい本</label>
          <input type="radio" name="record" value="wish" id="record_wish" <?php if (($book['status'] ?? '') === 'wish') echo 'checked'; ?>>
          <label for="record_read" class="record_label">読んだ本</label>
          <input type="radio" name="record" value="read" id="record_read" <?php if (($book['status'] ?? '') === 'read') echo 'checked'; ?>>
        </div>
        <div class="form_g">
          <label for="read_date">読んだ日</label>
          <!-- max=<?= $today ?>：日付入力フィールドで選択できる最大の日付を設定 -->
          <!-- value=：フォームに既存のデータを表示する、h()はXSS対策 -->
          <!-- ?? '' は$book['read_date'] に値が入っていて、 null ではない場合、その値を value に設定、$book['read_date'] が null なら’’ (空の文字列) を value に設定 -->
          <input type="date" name="read_date" id="read_date" max="<?= $today ?>" value="<?= h($book['read_date'] ?? '') ?>">
        </div>
        <div class="form_g">
          <label for="comment" class="comment">コメント</label>
          <textarea name="comment" id="comment"><?= h($book['comment'] ?? '') ?></textarea>
        </div>
        <!-- 更新処理する update.php にどのレコードを更新するか伝えるため編集対象のIDを隠しinputとしてフォーム内に含める -->
        <input type="hidden" name="id" value="<?= h($book['id']) ?>">
        <input type="hidden" name="from" value="<?= h($from) ?>">
        <!-- 登録ボタン -->
        <input type="submit" value="変更する" class="btn">
      </form>
    </section>

  </main>
  <footer>
    <p class="top-btn"><a href="index.php">トップページに戻る</a></p>
    <p>&copy;2025 わたしと本&nbsp;/&nbsp;PHP</p>
  </footer>

  <!------------------ JavaScript ------------------>
  <script>
    // PHPで生成されたエラーメッセージを変数に格納
    // PHPの変数 $errorMessage の内容をJavaScriptの errorMessage 変数に渡します。
    const errorMessage = "<?= $errorMessage ?>";
    // エラーメッセージがあればアラート表示
    // errorMessage が空文字列でない（＝エラーメッセージがある）場合にalert()が実行
    if (errorMessage) {
      alert(errorMessage);
    }
  </script>

</body>

</html>