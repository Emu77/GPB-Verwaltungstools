<?php
require_once 'check_login.php';

$bezeichnung=isset($_GET['bezeichnung']) ? $_GET['bezeichnung'] : false;
if($bezeichnung && isset($_SESSION['planungkonfigid']) && !empty($_SESSION['planungkonfigid'])) {
  $stmt=$db->prepare("update gpb_planungkonfig set bezeichnung=? where id=? limit 1");
  $stmt->bind_param('si',$bezeichnung,$_SESSION['planungkonfigid']);
  $stmt->execute();
}
header('Location:planung.php');
exit;
?>