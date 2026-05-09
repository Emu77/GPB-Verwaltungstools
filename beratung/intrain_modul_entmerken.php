<?php
require_once 'check_login.php';

$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;

if($modulid<=0) {
  require_once 'intrain_seite.php';
  exit;
}

$modulids=isset($_SESSION['intrain_modulids']) ? $_SESSION['intrain_modulids'] : array();
if(isset($modulids[$modulid])) {
  unset($modulids[$modulid]);
  $_SESSION['intrain_modulids']=$modulids;
}

$modulanfaenge=isset($_SESSION['intrain_modulanfaenge']) ? $_SESSION['intrain_modulanfaenge'] : array();
if(isset($modulanfaenge[$modulid])) {
  unset($modulanfaenge[$modulid]);
  $_SESSION['intrain_modulanfaenge']=$modulids;
}

require_once 'intrain_seite.php';
exit;
?>