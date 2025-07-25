<?php

session_start();

// db_helper.php を読み込む
require_once 'db_helper.php';

// ログイン状態の確認（未ログインはlogin.phpへ）
sessionCheck();

// ログイン中のユーザーIDを取得
$user_id = $_SESSION['user_id'];

//カレンダーの日付制御
$today = time_z();

// 読んだ本リストを【ログイン中のユーザー】に限定して取得
try {
  $dbh = getDbh(); // データベース接続を取得

  // SQL文: kadai_07から、ログイン中のuser_idに紐づく、statusが'read'の本だけを取得
  $sql = "SELECT * FROM kadai_07 WHERE user_id = :user_id AND status = 'read' ORDER BY created_at DESC";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT); // ユーザーIDをバインド
  $stmt->execute();
  $read_books = $stmt->fetchAll(PDO::FETCH_ASSOC); // データを取得

} catch (PDOException $e) {
  // エラー処理（エラーログに出力し、ユーザーには一般的なメッセージを表示）
  error_log('読んだ本リスト取得データベースエラー: ' . $e->getMessage());
  exit('データベースエラーが発生しました。しばらくお待ちください。');
} catch (Exception $e) {
  error_log('読んだ本リスト取得予期せぬエラー: ' . $e->getMessage());
  exit('予期せぬエラーが発生しました。しばらくお待ちください。');
}

?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>読んだ本</title>
  <link rel="stylesheet" href="./css/reset.css">
  <link rel="stylesheet" href="./css/read_wish.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&family=Shippori+Mincho+B1&display=swap"
    rel="stylesheet">
</head>

<body>
  <header>
    <div class="logout_text"><a href="logout.php">ログアウト</a></div>
    <h1>読んだ本</h1>
  </header>
  <main>
    <div class="book_list container">
      <?php if (empty($read_books)): ?>
        <p>まだ読んだ本は登録されていません</p>
      <?php else: ?>
        <?php foreach ($read_books as $book): ?>
          <div class="book_card">
            <div class="book_img">
              <?php if (!empty($book['cover_url'])): ?>
                <img src="<?= h($book['cover_url']) ?>" alt="本の表紙">
              <?php else: ?>
                <span>画像なし</span>
              <?php endif; ?>
            </div>
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
              </p>
              <div class="book_comment_box">
                <?= nl2br(h($book['comment'] ?? 'コメントなし')) ?>
              </div>
              <div class="read_date_info">
                <?php
                // $book['read_date'] が存在し、かつ空でない場合のみ処理
                if (!empty($book['read_date'])):
                ?>
                  読んだ日：
                  <?php
                  // strtotime() で日付文字列をタイムスタンプに変換
                  $timestamp = strtotime($book['read_date']);

                  // タイムスタンプが有効な場合のみフォーマットして表示
                  if ($timestamp !== false) {
                    echo h(date('y/m/d', $timestamp)); // 'yy/mm/dd' 形式に整形してエスケープ
                  } else {
                    // 不正な日付形式の場合のフォールバック
                    echo h($book['read_date']); // エスケープして元の値をそのまま表示するか、
                    // echo '日付形式エラー'; // あるいは「日付形式エラー」と表示
                  }
                  ?>
                <?php endif; ?>
              </div>
              <div class="registration_actions_container">
                <div class="actions">
                  <!-- &：複数のクエリパラメータを区切るための記号 -->
                  <a href="edit.php?id=<?= h($book['id']); ?>&from=read" class="edit-btn">編集する</a>
                  <a href="delete.php?id=<?= h($book['id']); ?>&from=read" class="delete-btn" onclick="return confirm('この本を削除しますか？');">削除する</a>
                  <a href="file_view.php?id=<?= h($book['id']); ?>&from=read" class="upload-btn">画像アップロード</a>
                </div>
                <p class="registration_date">登録日:
                  <?php
                  // $book['created_at']が空でないか、有効な日付文字列か確認
                  if (!empty($book['created_at'])) {
                    // strtotime() で日付文字列をタイムスタンプに変換
                    $timestamp = strtotime($book['created_at']);
                    // timestampが有効な場合のみdate()でフォーマット、h()でエスケープ表示
                    if ($timestamp !== false) {
                      echo h(date('y/m/d', $timestamp));
                    } else {
                      // strtotime() が失敗した場合（不正な日付形式など）の処理
                      echo '日付形式エラー';
                    }
                  } else {
                    // $book['created_at'] が空の場合の処理
                    echo '未登録';
                  }
                  ?>
                </p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <p class="top-btn"><a href="index.php">トップページに戻る</a></p>
  </main>
  <footer>
    <p>&copy;2025 わたしと本&nbsp;/&nbsp;PHP</p>
  </footer>
</body>

</html>