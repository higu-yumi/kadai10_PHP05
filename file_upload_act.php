<?php
session_start();

require_once 'db_helper.php'; // fileUpload() 関数が定義されたdb_helper.phpを読み込む

// ログイン状態の確認（未ログインはlogin.phpへ）
sessionCheck();

// POSTされた本のIDと元のリスト情報を取得
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$from = filter_input(INPUT_POST, 'from');

// IDまたはfromが不正な場合はリダイレクト
if (!$id || !in_array($from, ['read', 'wish'])) {
  redirect('login.php', ['error' => 'invalid_request']); // 不正なリクエスト
}

// ユーザーIDを取得
$user_id = $_SESSION['user_id'];

// 対象の本が現在のユーザーのものであるか確認
// fileUpload()関数を呼び出す前に必要!
// 他のユーザーの本に画像をアップロードできないようにする
try {
  $dbh = getDbh();
  $stmt = $dbh->prepare('SELECT user_id FROM kadai_07 WHERE id = :id');
  $stmt->bindValue(':id', $id, PDO::PARAM_INT);
  $stmt->execute();
  $book_owner_id = $stmt->fetchColumn(); // user_id カラムの値のみ取得

  if ($book_owner_id === false || $book_owner_id !== $user_id) {
    // 本が見つからない、または他のユーザーの本の場合
    redirect($from . '_list.php', ['upload_error' => 'アクセス権がありません。']);
  }
} catch (PDOException $e) {
  error_log("DB Error (owner check): " . $e->getMessage());
  redirect('file_view.php', ['id' => $id, 'from' => $from, 'upload_error' => 'データベースエラーが発生しました。']);
}

// 画像の保存先ディレクトリ
$upload_dir = './booksPhoto/';

// fileUpload関数を呼び出し、結果を受け取る
$upload_result = fileUpload("upfile", $upload_dir);

// $upload_result の値に応じて処理を分岐
if (is_string($upload_result)) {
  // 成功時：$upload_result にはユニークなファイル名（文字列）が入っている
  $unique_file_name = $upload_result;
  $cover_url = $upload_dir . $unique_file_name;

  // データベースの更新
  try {
    $update_sql = "UPDATE kadai_07 SET cover_url = :cover_url WHERE id = :id AND user_id = :user_id";
    $update_stmt = $dbh->prepare($update_sql);
    $update_stmt->bindValue(':cover_url', $cover_url, PDO::PARAM_STR);
    $update_stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $update_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT); // ここでもユーザーIDでフィルタリング
    $update_status = $update_stmt->execute();

    if ($update_status === false) {
      error_log("DB Update Error (Image Upload): " . print_r($update_stmt->errorInfo(), true));
      redirect('file_view.php', ['id' => $id, 'from' => $from, 'upload_error' => 'データベースの更新に失敗しました。']);
    } else {
      // 成功メッセージと共に元のページにリダイレクト
      redirect('file_view.php', ['id' => $id, 'from' => $from, 'upload_success' => 1]);
    }
  } catch (PDOException $e) {
    error_log("DB Update Error (PDO): " . $e->getMessage());
    redirect('file_view.php', ['id' => $id, 'from' => $from, 'upload_error' => 'データベースエラーが発生しました。']);
  }
} else {
  // 失敗時：$upload_result にはエラーコード（数値）が入っている
  $error_message = '';
  switch ($upload_result) {
    case 1:
      $error_message = 'ファイルの移動に失敗しました。';
      break;
    case 2:
      $error_message = 'ファイルが選択されていないか、アップロードエラーが発生しました。（PHP設定による上限超過など）';
      break;
    case 3:
      $error_message = '不正なファイル形式です。JPEG, PNG, GIF画像のみ対応しています。';
      break;
    case 4:
      $error_message = 'ファイルサイズが大きすぎます。5MB以下にしてください。';
      break;
    case 5:
      $error_message = '不正なファイルアップロードです。';
      break;
    default:
      $error_message = '予期せぬエラーが発生しました。';
      break;
  }
  // エラーメッセージと共に元のページにリダイレクト
  redirect('file_view.php', ['id' => $id, 'from' => $from, 'upload_error' => $error_message]);
}
