<?php

session_start();

// db_helper.php を読み込む
require_once 'db_helper.php';

// ログイン状態の確認（未ログインはlogin.phpへ）
sessionCheck();

////////////////////////////////////////////////
// フォームデータの取得
////////////////////////////////////////////////

// POSTデータを取得、XSS対策のh()はここでは不要、表示時に h() する
// hiddenで送られてくるIDを取得
// $status = $_POST['record'] ?? ''; でもいいけど…
// filter_inputだと、$_POSTから値の取得だけでなく、フィルタリングやバリデーション（検証）の機能も持つ
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = filter_input(INPUT_POST, 'record'); // FILTER_VALIDATE_INT付けると全falseになっちゃう
$read_date = filter_input(INPUT_POST, 'read_date'); // read_date (読んだ日)
$comment = filter_input(INPUT_POST, 'comment'); // comment (コメント)

// どのリストから来たか (read or wish) を取得
$from = filter_input(INPUT_POST, 'from');
if (!in_array($from, ['read', 'wish'])) {
  $from = 'read'; // 不正な値の場合はデフォルトで 'read' に設定
}

////////////////////////////////////////////////
// バリデーション（検証）
////////////////////////////////////////////////

// db_helper.php で定義した validateBookData 関数を呼び出す
$errors = validationBookData($id, $status, $read_date, $comment);

// バリデーション結果の判定
if (!empty($errors)) {
  // エラーメッセージをクエリパラメータとして渡すための連想配列を作成
  $params = [
    'id' => $id, // 編集対象のID
    'error' => implode(';', $errors), // エラーメッセージを連結
    'from' => $from // どこから来たかの情報を追加
  ];
  // db_helper.php で定義した redirect 関数を呼び出す
  redirect('edit.php', $params);
  // redirect 関数の中で exit; が呼ばれるので、ここでは exit; は不要
}

////////////////////////////////////////////////
// データ整形（null変換など）
////////////////////////////////////////////////

// read_dateが空の場合はnullに変換
if (empty($read_date)) {
  $read_date = null;
}

// commentが空の場合はnullに変換
if (empty($comment)) {
  $comment = null;
}

////////////////////////////////////////////////
// データベース更新処理 (UPDATE文の実行)
////////////////////////////////////////////////

try {
  // db_helper.php で定義した getDbh 関数を呼び出してCBへの接続を確立
  $dbh = getDbh();
  $sql = "UPDATE kadai_07 SET status = :status, read_date = :read_date, comment = :comment WHERE id = :id";
  // prepare()でSQL文を事前に準備、bindValue()で後から値を埋め込むことで効率が良くなる
  $stmt = $dbh->prepare($sql);
  // プレースホルダーに値をバインド（SQLインジェクション対策）
  $stmt->bindValue(':status', $status, PDO::PARAM_STR);
  $stmt->bindValue(':read_date', $read_date, PDO::PARAM_STR);
  $stmt->bindValue(':comment', $comment, PDO::PARAM_STR);
  $stmt->bindValue(':id', $id, PDO::PARAM_INT);
  $stmt->execute(); // SQLを実行
  // 更新成功時の処理として、適切なリストページへリダイレクト
  $params = ['message' => 'updated'];
  if ($status === 'read') {
    redirect('read_list.php', $params);  // 読んだ本リストへリダイレクト
  } else {
    redirect('wish_list.php', $params);
  }
} catch (PDOException $e) {
  // 開発者向け：エラーメッセージとサーバーログに記録
  error_log('データベース更新エラー:' . $e->getMessage());
  // ユーザー向け：エラーメッセージと元のページリダイレクト
  $params = [
    'id' => $id,
    'error' => 'データベース更新に失敗しました。',
    'from' => $from // どこから来たかの情報を追加
  ];
  redirect('edit.php', $params);
} catch (Exception $e) {
  // PDOException以外の予期せぬエラー対策
  error_log('予期せぬエラー:' . $e->getMessage());
  // ユーザー向け：エラーメッセージとedit.phpにリダイレクト
  $params = [
    'id' => $id,
    'error' => '予期せぬエラーが発生しました。',
    'from' => $from // どこから来たかの情報を追加
  ];
  redirect('edit.php', $params);
}
