<?php

// セッションにこれ重要！
session_start();

// filter_input（XSS対策）でPOST値を取得
// FILTER_UNSAFE_RAW はデフォルトで適用されてる！
$lid = filter_input(INPUT_POST, "lid"); // ログインID
$lpw = filter_input(INPUT_POST, "lpw"); // パスワード

// エラーチェック
if ($lid === null || $lid === false || $lpw === null || $lpw === false) {
  redirect('login.php', ['error' => 'invalid_input']);
}

// DB接続
require_once 'db_helper.php';

try {
  // getDbh() を呼び出してデータベース接続
  $dbh = getDbh();

  // データ取得SQL作成
  // Passwordはハッシュ化されているので、ここではlidでユーザーを特定
  // パスワードハッシュは後から取得して比較
  $sql = 'SELECT * FROM kadai_09_user_table WHERE lid = :lid AND life_flag = 0';
  $stmt = $dbh->prepare($sql);
  $stmt->bindValue(':lid', $lid, PDO::PARAM_STR);
  $status = $stmt->execute(); // 実行

  // SQL実行時にエラーがある場合はストップ
  if ($status === false) {
    error_log("SQL Error: " . print_r($stmt->errorInfo(), true));
    redirect('login.php', ['error' => 'db_error']);
  }

  //抽出データ数を取得
  // $stmt->fetch();はPDO::FETCH_BOTH がデフォルトの挙動、1つでも使おう
  $val = $stmt->fetch(PDO::FETCH_ASSOC); // 1レコードだけ取得

  // 1レコードがあればSESSIONに値を代入
  // パスワードの検証は、ユーザーが入力した生パスワード($lpw)とDBから取得したハッシュ化パスワード ($val["lpw"]) を比較
  if ($val && password_verify($lpw, $val["lpw"])) {
    // ログイン成功時
    session_regenerate_id(true); // セッション固定攻撃対策
    $_SESSION['user_id'] = $val['id']; // マイページになるようにログインしたユーザーIDも保存！
    $_SESSION['user_flag'] = $val['user_flag'];
    $_SESSION['chk_ssid'] = session_id();
    $_SESSION['name'] = $val['name'];
    redirect('index.php');
  } else {
    // ログイン失敗時（ID、パスワード、または権限が不一致）
    // 具体的なエラーメッセージは出さない方がセキュリティ上安全
    redirect('login.php?error=login_failed');
  }
} catch (PDOException $e) {
  //DB接続エラーやPDO関連のエラー
  error_log("接続エラー:" . $e->getMessage());
  echo '<script>alert("データベースエラーが発生しました。時間をおいて再度お試しください。"); window.location.href="login.php?error=db_connect";</script>';
  exit;
}
