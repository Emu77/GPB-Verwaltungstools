<?php
require_once 'check_login.php';
$konfigid=isset($_GET['konfigid']) ? (int)$_GET['konfigid'] : 0;
if(empty($konfigid)) {
  if(isset($_SESSION['planungkonfigid'])) {
    unset($_SESSION['planungkonfigid']);
  }
} else {
  $_SESSION['planungkonfigid']=$konfigid;
}
header('Location:planung.php');
exit;
?>