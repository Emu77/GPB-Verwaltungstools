<?php
require_once '../Suche.php';
@session_start();
if(!isset($_SESSION['leitung_ich'])) {
  if($_SERVER['REQUEST_METHOD']=='GET') {
    $_SESSION['redirect']=(empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
  }
  header('Location:login.php');
  exit;
}
$ich=$_SESSION['leitung_ich'];
require_once '../db.php';
?>