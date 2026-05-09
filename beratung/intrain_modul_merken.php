<?php
require_once 'check_login.php';

$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;

if($modulid<=0) {
  require_once 'intrain_seite.php';
  exit;
}

$modulids=isset($_SESSION['intrain_modulids']) ? $_SESSION['intrain_modulids'] : array();
$modulids[$modulid]=true;
$_SESSION['intrain_modulids']=$modulids;

require_once 'intrain_seite.php';
exit;
?>