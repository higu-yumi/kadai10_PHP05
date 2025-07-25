<?php

session_start();

// db_helper.php を読み込む
require_once 'db_helper.php';

// SESSION初期化（$_SESSION変数をすべて空にする）
$_SESSION = array();

//  Cookieに保存してたSessionIDの保存期間を過去にして破棄
if (isset($_COOKIE[session_name()])) {
  setcookie(session_name(), '', time() - 42000, '/');
}

// SESSION削除
session_destroy();
redirect('login.php');