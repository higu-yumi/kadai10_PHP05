<?php

session_start();

//////////////////////////////
// データベース接続
//////////////////////////////

// db_helper.php を読み込む
require_once 'db_helper.php';

// ログイン状態の確認（未ログインはlogin.phpへ）
sessionCheck();

// ログイン中のユーザーIDを取得 (kadai_07テーブルに保存)
$user_id = $_SESSION['user_id'];

try {
  $dbh = getDbh();

  //////////////////////////////
  // POSTデータを受け取る
  //////////////////////////////

  // ?? はPHP7以降で使える「Null合体演算子」
  // $_POST['isbn']が存在しない場合は空文字''を代入
  $isbn = $_POST['isbn'] ?? '';
  $record_type = $_POST['record'] ?? '';
  // 日付データはnullを許容する 
  $read_date = $_POST['read_date'] ?? null;
  $comment = $_POST['comment'] ?? '';

  /////////// API連携のコード ///////////

  // 1.OpenBD APIのエンドポイントURLを構築する
  $openbd_api_url = 'https://api.openbd.jp/v1/get?isbn=' . $isbn;

  // 2. APIにリクエストを送信し、JSONデータを取得する
  // file_get_contents() ：ファイルの内容を文字列に読み込む
  // @：PHPのエラー制御演算子、PHPはエラーメッセージをブラウザに表示するがユーザーにとって技術的なエラーメッセージは意味不明で、サイトが壊れているように見えるかも。技術的なエラーメッセージをユーザーに見せない。下の条件分岐で適切に対処する「エラーハンドリング」が可能。デバック中は一時的に外すのも吉！
  $json_data = @file_get_contents($openbd_api_url);

  if ($json_data === FALSE) {
    // APIリクエストが失敗した場合の処理
    // 例: エラーメッセージを表示して処理を中断、またはindex.phpに戻す
    exit('ISBN情報取得に失敗しました。');
  }

  // 3. 取得したJSONデータをPHPのデータ構造に変換する
  // json_decode() デコード：簡単に説明するとエンコードされたデータを元に戻すこと
  $book_data = json_decode($json_data, true);



  // 4. 変換したデータから必要な情報を抽出する

  // 変数の初期化、もしAPIから情報が取れなくても、この変数は存在していて空の状態になっているので、後でエラーにならずに使えるようにしておく、おまじない！
  $title = null;
  $author = null;
  $publisher = null;
  $cover_url = null;

  // OpenBD APIから返されるJSONデータのsummaryセクションを使用。シンプルで扱いやすい
  if (!empty($book_data) && isset($book_data[0]['summary'])) {
    $summary = $book_data[0]['summary'];
    $title = $summary['title'] ?? null;
    $author = $summary['author'] ?? null;
    $publisher = $summary['publisher'] ?? null;
    $cover_url = $summary['cover'] ?? null; // 表紙画像のURL

  } else {
    // ISBNに一致する書籍情報が見つからなかった場合の処理
    error_log("OpenBD APIでISBN: " . $isbn . " の書籍情報が見つかりませんでした。");
  }

  //////////////////////////////
  // SQLインジェクション対策として処理
  //////////////////////////////

  // idはMySQL自動的に連番を割り振るためPHP側からidの値を渡す必要なし
  // created_at：MySQLが自動的に日時挿入
  $sql = "INSERT INTO kadai_07(user_id, isbn, title, author, publisher, cover_url, read_date, comment, status)VALUES(:user_id, :isbn, :title, :author, :publisher, :cover_url, :read_date, :comment, :status)";

  //単なる文字列として扱われる（サイバー攻撃対策）
  $stmt = $dbh->prepare($sql);

  $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
  $stmt->bindValue('isbn', $isbn, PDO::PARAM_STR);
  $stmt->bindValue('title', $title, PDO::PARAM_STR);
  $stmt->bindValue('author', $author, PDO::PARAM_STR);
  $stmt->bindValue('publisher', $publisher, PDO::PARAM_STR);
  $stmt->bindValue('cover_url', $cover_url, PDO::PARAM_STR);

  // 読んだ日は登録しないこともあるので
  if ($read_date === '' || $read_date === null) {
    // $read_dateが空文字列またはnullの場合、PDO::PARAM_NULLとしてバインドする
    $stmt->bindValue('read_date', null, PDO::PARAM_NULL);
  } else {
    // $read_dateに値がある場合は、文字列としてバインドする
    $stmt->bindValue('read_date', $read_date, PDO::PARAM_STR);
  }
  $stmt->bindValue('comment', $comment, PDO::PARAM_STR);
  $stmt->bindValue('status', $record_type, PDO::PARAM_STR);
  // SQLの実行
  $status = $stmt->execute(); // true or false

  // 登録タイプに応じてリダイレクト先を決定
  if ($record_type === 'wish') { // 'record'が読みたい本'wish'の場合
    redirect('wish_list.php');
  } elseif ($record_type === 'read') { // 'record'が読んだ本'read'の場合
    redirect('read_list.php');
  } else {
    // どちらでもない場合のデフォルトのリダイレクト先
    redirect('index.php', ['error' => 'invalid_record_type']);
  }
} catch (PDOException $e) {
  error_log('DB接続エラー (insert.php):' . $e->getMessage());
  redirect('index.php', ['error' => 'db_error']); // ユーザー向けにリダイレクト
}
