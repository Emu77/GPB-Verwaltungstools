<?php
require_once 'check_login.php';
$konfigid=isset($_SESSION['planungkonfigid']) && !empty($_SESSION['planungkonfigid']) ? $_SESSION['planungkonfigid'] : 0;
if($konfigid>0) {
  $db->query("delete from gpb_planungkonfigzeile where planungkonfigid=".$konfigid);
  $db->query("delete from gpb_planungkonfig where id=".$konfigid);
}
if(isset($_SESSION['planungkonfigid'])) {
  unset($_SESSION['planungkonfigid']);
}
header('Location:planung.php');
exit;
?>