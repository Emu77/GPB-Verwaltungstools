<?php
require_once 'login_funktion.php';
if(empty($nutzername) || empty($passwort)) {
  header('Location:index.php');
  exit;
}
$ich=login('gpb_verwalter');
if($ich) {
  $_SESSION['verwaltung_ich']=$ich;
  if($ich->istleiter) {
    $_SESSION['leitung_ich']=$ich;
  }
  if(isset($_SESSION['redirect'])) {
    $url=$_SESSION['redirect'];
    unset($_SESSION['redirect']);
    if(strpos($url,'/verwaltung/')!==false) {
      header('Location:'.$url);
      exit;
    }
    if($ich->istleiter && strpos($url,'/leitung/')!==false) {
      header('Location:'.$url);
      exit;
    }
  }
  if($ich->istleiter) {
    header('Location:leitung/index.php');
    exit;
  }
  header('Location:verwaltung/index.php');
  exit;
}
$ich=login('gpb_dozent');
if($ich) {
  $_SESSION['dozent_ich']=$ich;
  if($ich->instintrain) {
    $_SESSION['intrain_ich']=$ich;
  }
  if(isset($_SESSION['redirect'])) {
    $url=$_SESSION['redirect'];
    unset($_SESSION['redirect']);
    if($ich->instintrain && strpos($url,'/instrain/')!==false) {
      header('Location:'.$url);
      exit;
    }
    if(strpos($url,'/dozent/')!==false) {
      header('Location:'.$url);
      exit;
    }
  }
  header('Location:dozent/index.php');
  exit;
}
$ich=login('gpb_berater');
if($ich) {
  $_SESSION['beratung_ich']=$ich;
  if(isset($_SESSION['redirect'])) {
    $url=$_SESSION['redirect'];
    unset($_SESSION['redirect']);
    if(strpos($url,'/beratung/')!==false) {
      header('Location:'.$url);
      exit;
    }
    exit;
  }
  header('Location:beratung/index.php');
  exit;
}
$ich=login('gpb_tn');
if($ich) {
  $_SESSION['tn_ich']=$ich;
  if(isset($_SESSION['redirect'])) {
    $url=$_SESSION['redirect'];
    unset($_SESSION['redirect']);
    if(strpos($url,'/tn/')!==false) {
      header('Location:'.$url);
      exit;
    }
    exit;
  }
  header('Location:tn/index.php');
  exit;
}
header('Location:index.php');
exit;
?>