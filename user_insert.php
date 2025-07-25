<?php

session_start();

// db_helper.php を読み込む
require_once 'db_helper.php';

try {



  // filter_input（XSS対策）でPOST値を取得
  // FILTER_UNSAFE_RAW はデフォルトで適用されてる！
  $name      = filter_input(INPUT_POST, "name"); // 名前
  $user_flag = filter_input(INPUT_POST, "user_flag");  // user または admin
  $lid       = filter_input(INPUT_POST, "lid"); // ログインID
  $lpw       = filter_input(INPUT_POST, "lpw"); // パスワード

  // エラーチェック
  if ($name === null || $name === false || $lid === null || $lid === false || $lpw === null || $lpw === false || $user_flag === null || $user_flag === false) {
    redirect('login.php', ['alert' => '全ての必須項目を入力してください。']);
  }

  // エラーチェック後にパスワードハッシュ化！！！！
  $lpw       = password_hash($lpw, PASSWORD_DEFAULT);

  // getDbh() を呼び出してデータベース接続
  $dbh = getDbh();

  //////////
  // SELECT COUNT(*) ログインIDの重複チェック！！！！
  //////////
  $sql_check_lid = "SELECT COUNT(*) FROM kadai_09_user_table WHERE lid = :lid";
  $stmt_check_lid = $dbh->prepare($sql_check_lid);
  // :lid に $lid の値を安全にバインド
  $stmt_check_lid->bindValue(':lid', $lid, PDO::PARAM_STR);
  $status_check_lid = $stmt_check_lid->execute(); // 実行

  // SQL実行エラーの確認
  if ($status_check_lid === false) {
    // エラーハンドリング
    error_log("ログインID重複チェックSQLエラー: " . print_r($stmt_check_lid->errorInfo(), true));
    redirect('user.php', ['alert' => 'エラーが発生しました。時間をおいて再度お試しください。']);
  }

  // 抽出データ数を取得 (COUNT(*) の結果は fetchColumn() で取得するのが最適)
  $count = $stmt_check_lid->fetchColumn();

  // 重複チェックの判定
  if ($count > 0) {
    // 重複が見つかった場合
    redirect('user.php', ['alert' => 'すでにログインIDは登録済です']);
  }

  //////////
  // データ取得SQL作成
  //////////
  $sql = "INSERT INTO kadai_09_user_table(name,lid,lpw,user_flag,life_flag)VALUES(:name,:lid,:lpw,:user_flag,:life_flag)";
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':name', $name, PDO::PARAM_STR);
  $stmt->bindValue(':user_flag', $user_flag, PDO::PARAM_INT);
  $stmt->bindValue(':lid', $lid, PDO::PARAM_STR);
  $stmt->bindValue(':lpw', $lpw, PDO::PARAM_STR);
  $stmt->bindValue(':life_flag', 0, PDO::PARAM_INT); // 0をアクティブとする）
  $status = $stmt->execute();

  //////////
  // INSERT実行結果の確認
  //////////
  if ($status === false) {
    error_log("ユーザー登録INSERTエラー: " . print_r($stmt->errorInfo(), true));
    redirect('user.php', ['alert' => 'ユーザー登録に失敗しました。']);
  } else {
    // 登録成功
    redirect('login.php', ['alert' => 'ユーザー登録が完了しました！ログインしてください。']);
  }
  // tryブロック内のPDO関連のエラーをキャッチ
} catch (PDOException $e) {
  error_log("データベース接続/クエリ実行エラー (ユーザー登録): " . $e->getMessage());
  // ユーザーには一般的なエラーメッセージを表示
  redirect('user.php', ['alert' => 'システムエラーが発生しました。']);
}
