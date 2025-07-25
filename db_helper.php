<?php


////////////////////////////////////////////////
//
// データベース接続
//
////////////////////////////////////////////////

// 一度だけ読み込めばいい「require_once」でデータベースに接続
// 関数定義の「外側」で環境ごとの設定ファイルを一度だけ読み込む
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
  // サーバー名が'localhost'またはIPアドが'127.0.0.1'ならXAMPPと判断
  // require_once ：PHPで指定されたファイルを一度だけ読み込む構文
  // __DIR__ ：絶対パスを返すマジカル定数(ファイル名の前に / 必要)
  require_once __DIR__ . '/config_local.php'; //XAMPPを読み込む
} else {
  // さくらサーバーのパスを読み込む
  require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/config_files_private/kadai07_config/config_server.php';
}

// データベース接続を返す関数
function getDbh()
{
  try {
    // 定数を直接使ってDSN文字列を生成し、PDOオブジェクトを作成
    $dsn = 'mysql:dbname=' . DB_NAME . ';charset=' . DB_CHARSET . ';host=' . DB_HOST;
    $dbh = new PDO($dsn, DB_USER, DB_PASS);
    // エラーモードの設定(エラー発生したらエラー投げる)必須！
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // プリペアドステートメントのエミュレーション設定
    //SQLインジェクション対策の機能（プリペアドステートメント）はPHPで模倣せずにDBに直接やらせて！」という必須の設定
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    // return $dbh; ：関数が処理結果（DB接続オブジェクト）を呼び出し元に「渡す」ために必須
    return $dbh;
  } catch (PDOException $e) {
    exit('接続エラー:' . $e->getMessage());
  }
}

////////////////////////////////////////////////
//
// セキュリティー XSS対策
//
////////////////////////////////////////////////

//  HTML特殊文字をエンティティに変換する簡易関数
//  @param string|null $string 変換対象の文字列
//  @param int $flags htmlspecialcharsに渡すフラグ (例: ENT_QUOTES)
//  @param string $encoding エンコーディング
//  @return string 変換後の文字列
// 引数と戻り値の型宣言、デフォルト引数はPHPの最近のバージョンで導入された、コードの信頼性と可読性を高めるための重要な書き方
// ?string $string： ?はNull許容型というPHP 7.1〜の機能。この引数は指定された型（ここではstring）の他に、nullも受け入れることができる
// int $flags = ENT_QUOTES：この引数には整数型の値が渡されることを期待と宣言。htmlspecialchars()の第2引数はフラグなので、整数のビットマスクを使う
// $flags: 引数の変数名
// :string（カッコの外）：PHP7.0以降導入した戻り値の型宣言の記号
function h(?string $string, int $flags = ENT_QUOTES, string $encoding = 'UTF-8'): string
{
  // nullが渡された場合は空文字列を返す（null coalescing operator ?? の挙動に合わせる）
  if ($string === null) {
    return '';
  }
  return htmlspecialchars($string, $flags, $encoding);
}

////////////////////////////////////////////////
//
// カレンダーを明日以降は選択できないように制御
//
////////////////////////////////////////////////

//: string を付けるとコードの堅牢性、保守性、可読性を高めるための現代的な書き方。機能動作は変わらないが、品質が向上！
function time_z(): string
{
  // タイムゾーンを設定し、date()関数を使って今日の日付を取得
  date_default_timezone_set('Asia/Tokyo');
  // 今日の日付を YYYY-MM-DD 形式の文字列で取得
  $today = date('Y-m-d');
  return $today;
}

////////////////////////////////////////////////
//
// 指定されたステータスの本のリストを取得する('read' or 'wish')
//
////////////////////////////////////////////////

// @param ：関数やメソッドがどのような引数を受け取るのかを説明するために使われる
// @param string $status_value 取得したい本のステータス ('read', 'wish')
// @return array 取得した本のデータの連想配列
// array： ?が付いていないので、必ずarray型（配列）を返すと宣言。nullを返すことはない
function bookStatus(string $status_value): array
{
  try {
    // データベース接続を取得
    $dbh = getDbh();
    // テーブルから全てのカラム（*）のデータを取得する
    // WHERE status = :status_value は条件句
    // ORDER BY created_at DESC: 取得したデータを created_at カラムの降順（新しい順）で並べ替える
    $sql = "SELECT * FROM kadai_07 WHERE status = :status_value ORDER BY created_at DESC";
    // SQLインジェクション攻撃を防ぐための重要な第一歩
    // この時点でDBは$sql文の構造（例：テーブル存在するか、statusやcreated_atというカラムがあるか）を解析し最適化する
    $stmt = $dbh->prepare($sql);
    // DO::PARAM_STR: $status_value が文字列であることを明示的に指定。重要！
    $stmt->bindValue(':status_value', $status_value, PDO::PARAM_STR);
    // DB上で実行
    $stmt->execute();
    // DBから取得した生データを、PHPで扱いやすい配列の形式に変換し、この関数を呼び出した元のコードに返す
    // FETCH_ASSOC 指定で各行がカラム名をキーとする連想配列として取得される
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) { // データベース関連のエラー
    // 簡略化してエラーメッセージ、エラーをサーバーのログに記録
    error_log('データベースエラー: ' . $e->getMessage());
    // ユーザーには一般的なメッセージ
    exit('データベースエラーが発生しました。しばらくお待ちください。');
  } catch (Exception $e) { // データベース関連以外のエラー
    error_log('予期せぬエラー:' . $e->getMessage());
    exit('予期せぬエラーが発生しました。しばらくお待ちください。');
  }
}

////////////////////////////////////////////////
//
// 特定のIDの本を取得する
//
////////////////////////////////////////////////

// 指定されたIDの本の情報を取得する
// @param int $id 取得したい本のID
// @return array|null 取得した本のデータ（連想配列）、見つからない場合はnull
// ?array：?が付いてるのは、この関数はarray型（配列）を返すか null を返す可能性があると宣言
function bookid(int $id): ?array
{
  try {
    $dbh = getDbh();
    $sql = "SELECT * FROM kadai_07 WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $book = $stmt->fetch(PDO::FETCH_ASSOC);
    return $book ?: null; // 本が見つからない場合はnullを返す
  } catch (PDOException $e) {
    error_log('データベースエラー:' . $e->getMessage());
    exit('データベースエラーが発生しました。しばらくお待ちください。');
  } catch (Exception $e) {
    error_log('予期せぬエラー: ' . $e->getMessage());
    exit('予期せぬエラーが発生しました。しばらくお待ちください。');
  }
}

////////////////////////////////////////////////
//
// バリデーション（検証）
//
////////////////////////////////////////////////

function validationBookData($id, $status, $read_date, $comment)
{
  $errors = []; //エラーメッセージを格納する配列を初期化

  //ID：不正な状態を広くカバー
  if ($id === false || $id <= 0) {
    $errors[] = '本が特定できませんでした。';
  }

  // ステータス
  if (!in_array($status, ['read', 'wish'], true)) {
    $errors[] = 'ステータスが特定できませんでした。'; // true推奨！値だけでなく型も完全一致かチェック
  }

  // 本を読んだ日
  // read_dateは必須ではないが、もし値があれば形式と日付の妥当性をチェック
  if (!empty($read_date)) { // $read_date が空ではない場合のみ、以下のチェックを実行
    // 日付形式のチェック (YYYY-MM-DD 形式)
    // preg_match は正規表現で文字列がパターンに一致するかチェックする関数
    // !preg_match はパターンに一致しない場合を検出
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $read_date)) {
      $errors[] = '読んだ日の日付形式が不正です。';
    }
    // 読んだ日が今日の日付より未来ではないかを確認
    if ($read_date > date('Y-m-d')) {
      $errors[] = '読んだ日は今日以前の日付を入力してください。';
    }
  }

  // コメント
  // mb_strlen：マルチバイト文字列（日本語など）の文字数を正確に数えるためのPHP関数
  if (mb_strlen($comment) > 500) {
    $errors[] = 'コメントは500字以内で入力してください。';
  }
  return $errors;  // errorsを返す
}

////////////////////////////////////////////////
//
// リダイレクト処理（実用的で、安全性を考慮）
//
////////////////////////////////////////////////

// 指定されたURLへリダイレクトし、スクリプトの実行を停止する
// string $url = 引数 $url は文字列でなければならない、という型ヒント
// array $params = []：URLに追加したいクエリパラメータを連想配列で受け取る
// void：何も値を返さない（ただ処理を実行するだけ）ことを示す
function redirect(string $url, array $params = []): void
{
  $query = '';
  // クエリパラメーターがあればURLに整形する
  if (!empty($params)) {
    $query = '?' . http_build_query($params);
  }

  // 'alert' キーがパラメータに含まれているかチェック
  if (isset($params['alert'])) {
    // JavaScriptのアラートを表示してからリダイレクト
    echo '<script>';
    // alert() のメッセージをHTMLエンティティに変換してXSS対策
    echo 'alert("' . htmlspecialchars($params['alert'], ENT_QUOTES, 'UTF-8') . '");';
    echo 'window.location.href = "' . htmlspecialchars($url . $query, ENT_QUOTES, 'UTF-8') . '";';
    echo '</script>';
    exit; // スクリプトの実行を終了
  } else {
    // 通常のリダイレクト（'alert' パラメータがない場合）
    header('Location:' . $url . $query);
    exit; // スクリプトの実行終了
  }
}

////////////////////////////////////////////////
//
// ログインチェック
//
////////////////////////////////////////////////

// isset：存在するかどうか確認
function sessionCheck()
{
  // $_SESSION['user_id'] が存在しない場合も未ログインと判断
  if (!isset($_SESSION['chk_ssid']) || $_SESSION['chk_ssid'] !== session_id() || !isset($_SESSION['user_id'])) {
    redirect('login.php', ['alert' => 'このページにアクセスするにはログインが必要です。']);
  } else {
    // ログイン済みの場合にセッションIDを再生成。これはセキュリティ上非常に重要です。
    session_regenerate_id(true);
    $_SESSION["chk_ssid"] = session_id();
  }
}

////////////////////////////////////////////////
//
// fileUpload("送信名","アップロード先フォルダ");
//
////////////////////////////////////////////////

function fileUpload($fname, $path)
{
  // 1. ファイルがアップロードされているか、エラーがないかを確認
  // UPLOAD_ERR_OK はエラーがないことを示す定数
  if (!isset($_FILES[$fname]) || $_FILES[$fname]["error"] !== UPLOAD_ERR_OK) {
    return 2; // 失敗時：ファイル取得エラー（またはアップロードエラー）
  }

  // ファイル名、一時保存場所、サイズ、タイプを取得
  $file_name = $_FILES[$fname]["name"];
  $tmp_path  = $_FILES[$fname]["tmp_name"];
  $file_size = $_FILES[$fname]["size"];
  $file_type = $_FILES[$fname]["type"]; // ブラウザが申告するMIMEタイプ

  // --- ここからバリデーション（チェック） ---

  // 2. ファイルサイズ制限のチェック
  $max_size = 5 * 1024 * 1024; // 5MB
  if ($file_size > $max_size) {
    return 4; // 失敗時：ファイルサイズが大きすぎる
  }

  // 3. MIMEタイプ（ファイル形式）の厳格なチェック
  $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

  // finfo_open と finfo_file を使って、ファイルの中身からMIMEタイプを正確に判断 エフインフォ
  //悪意を持ってファイルの種類を偽装する可能性がある、finfo (Fileinfo) はファイルの中身を解析！
  $finfo = finfo_open(FILEINFO_MIME_TYPE); //ファイルの情報を調べるための準備をする
  $mime_type = finfo_file($finfo, $tmp_path); //アップされた一時ファイルの中身を調べ、MIMEタイプを取得
  finfo_close($finfo); //調べ終わったので、準備のために使ったリソースを解放

  if (!in_array($mime_type, $allowed_types)) {
    return 3; // 失敗時：不正なMIMEタイプ
  }

  // --- バリデーションここまで ---

  // 拡張子取得
  $extension = pathinfo($file_name, PATHINFO_EXTENSION);

  // ユニークファイル名作成
  //ファイル名重複対策、データの整合性を保ち、予期せぬ上書きを防ぐための必須のステップ
  // date() と md5(session_id()) を組み合わせて一意性を高める
  $unique_file_name = date("YmdHis") . md5(session_id()) . "." . $extension;

  // 最終的な保存パス
  $file_dir_path = $path . $unique_file_name; // 変数名を unique_file_name に修正

  // 4. アップロードされたファイルかどうかの最終チェックと移動
  if (is_uploaded_file($tmp_path)) {
    if (move_uploaded_file($tmp_path, $file_dir_path)) {
      // 成功時：パーミッションを設定し、ユニークなファイル名を返す
      chmod($file_dir_path, 0644); // 読み取り権限
      return $unique_file_name;
    } else {
      return 1; // 失敗時：ファイルの移動に失敗
    }
  } else {
    // is_uploaded_file() が false を返した場合（不正なアップロードの可能性）
    return 5; // 失敗時：不正なファイルアップロード
  }
}
