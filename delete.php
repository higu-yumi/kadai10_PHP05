<?php

session_start();

// db_helper.php を読み込む
require_once 'db_helper.php';

// ログイン状態の確認（未ログインはlogin.phpへ）
sessionCheck();

//////////////////////////////
// データベース接続
//////////////////////////////

// db_helper.php を読み込む
require_once 'db_helper.php';

try {
  $dbh = getDbh();

  // 1. 削除するIDを取得

  // filter_input() は GET/POSTデータなどを安全に取得するためのPHP関数
  // FILTER_VALIDATE_INTは取得した値が整数であることを確認する
  $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

  // 許可するページ名のリスト
  $allowed_pages = ['wish', 'read'];
  $from_page = filter_input(INPUT_GET, 'from', FILTER_SANITIZE_FULL_SPECIAL_CHARS); // 安全のためエスケープしておく

  // デフォルトのリダイレクト先を設定（安全策）
  $redirect_to = 'index.php';

  // 取得したページ名が許可リストに含まれているかチェック
  if (in_array($from_page, $allowed_pages, true)) {
    $redirect_to = $from_page . '_list.php'; // 例: 'wish_list.php' を生成
  } else {
    // 不正なページ名の場合はログに記録
    error_log("Warning: Invalid 'from' page received for deletion: " . ($from_page ?? 'not set'));
  }

  // もしIDが取得できない、または数値ではない（不正な値である）場合
  if ($id === false || $id === null) {
    // 開発者向けにエラーログを記録
    error_log("Error: Invalid ID for deletion received. ID: " . ($_GET['id'] ?? 'not set'));
    // ユーザーを元のリストページに戻しエラーメッセージのパラメーターを付ける
    header('Location: widh_list.php?error=invalid_delete_id');
    // リダイレクト後に処理を終了することが重要！
    exit();
  }

  // --- 2. SQL DELETE 文の準備と実行 ---

  // WHERE：指定された条件を満たすレコードのみを抽出するために使用
  $sql = "DELETE FROM kadai_07 WHERE id = :id";
  // SQL文を準備
  $stmt = $dbh->prepare($sql);
  // プレースホルダに実際のIDの値をバインド（割り当て）する
  // PDO::PARAM_INTはこの値が整数であることを明示
  $stmt->bindValue(':id', $id, PDO::PARAM_INT);
  // SQL文を実行。成功すれば true、失敗すれば false が返される
  $status = $stmt->execute();

  // --- 3. 処理後のリダイレクト ---
  if ($status) {
    // 削除が成功した場合
    header('Location: ' . $redirect_to);
    exit();
  } else {
    // データベースの制約違反、SQL構文エラー、またはその他のデータベース側の問題が原因で発生する可能性がある
    // error_log() は、PHPのエラーログファイルにメッセージを書き込む関数
    // ユーザーには表示されないが、開発者が後で問題の原因を調査する際に役立つ
    error_log("Error: Failed to delete record with ID: " . $id);

    // ユーザーを元のリストページにリダイレクトするが、
    // '?error=delete_failed' GETパラメータをURLに追加することで
    // リダイレクト先のページでエラーが発生したことを検知し、適切なエラーメッセージを表示できる
    header('Location: ' . $redirect_to . '?error=delete_failed');
    exit();
  }
} catch (PDOException $e) {
  // DB接続エラー時の処理（これは既存のコードと同じでOKです）
  error_log('DB接続エラー:' . $e->getMessage());
  echo '<script>alert("データベースエラーが発生しました。時間をおいて再度お試しください。"); window.location.href="index.php";</script>';
  exit();
}
