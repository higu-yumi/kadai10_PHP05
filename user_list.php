<?php

session_start();

// db_helper.php を読み込む
require_once 'db_helper.php';

// 管理者権限の確認
// user_flag が '0' (管理者) でなければ、ログインページへリダイレクト
if (!isset($_SESSION['user_flag']) || $_SESSION['user_flag'] != 0) {
  redirect('login.php', ['error' => '管理者権限が必要です。']);
}

// ユーザーリストの取得
try {
  $dbh = getDbh(); // データベース接続を取得

  // sql文：kadai_09_user_table から全てのユーザー情報を取得
  // created_at が新しい順で並べ替え
  $sql = "SELECT id, name, lid, user_flag, life_flag, created_at FROM kadai_09_user_table ORDER BY created_at DESC";
  $stmt = $dbh->prepare($sql);
  $stmt->execute();
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  // データベース関連のエラーが発生した場合の処理
  error_log('ユーザーリスト取得データベースエラー: ' . $e->getMessage());
  exit('データベースエラーが発生しました。しばらくお待ちください。');
} catch (Exception $e) {
  // その他の予期せぬエラーが発生した場合の処理
  error_log('ユーザーリスト取得予期せぬエラー: ' . $e->getMessage());
  exit('予期せぬエラーが発生しました。しばらくお待ちください。');
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>わたしと本</title>
  <link rel="stylesheet" href="./css/reset.css">
  <link rel="stylesheet" href="./css/user_list.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&family=Shippori+Mincho+B1&display=swap"
    rel="stylesheet">
</head>

<body>



  <header>
    <div class="logout_text"><a href="logout.php">ログアウト</a></div>
    <h1>登録者リスト</h1>
  </header>
  <main>
    <div class="user_list_container">
      <?php if (empty($users)): ?>
        <p class="no_users_message">登録されているユーザーはいません。</p>
      <?php else: ?>
        <table class="user_list_table">
          <thead>
            <tr>
              <th>ID</th>
              <th>ユーザー名</th>
              <th>ログインID</th>
              <th>権限</th>
              <th>登録日</th>
              <th>使用フラグ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <tr>
                <td><?= h($user['id']) ?></td>
                <td><?= h($user['name']) ?></td>
                <td><?= h($user['lid']) ?></td>
                <td>
                  <?php if ($user['user_flag'] == 0): ?>
                    <span class="status_admin">管理者</span>
                  <?php else: ?>
                    <span class="status_general">利用者</span>
                  <?php endif; ?>
                </td>



                <td>
                  <?php
                  if (!empty($user['created_at'])) {
                    $timestamp = strtotime($user['created_at']);
                    if ($timestamp !== false) {
                      echo h(date('Y/m/d H:i', $timestamp)); // 年月日 時分まで表示
                    } else {
                      echo '日付形式エラー';
                    }
                  } else {
                    echo '未登録';
                  }
                  ?>
                </td>

                <td>
                  <?php if ($user['life_flag'] == 0): ?>
                    <span class="life_active">使用中</span>
                  <?php else: ?>
                    <span class="life_inactive">不使用</span>
                  <?php endif; ?>
                </td>

              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    <p class="top-btn"><a href="index.php">トップページに戻る</a></p>
  </main>
  <footer>
    <p>&copy;2025 わたしと本&nbsp;/&nbsp;PHP</p>
  </footer>

</body>

</html>