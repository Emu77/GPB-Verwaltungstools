<?php
require_once 'check_login.php';
$kopie=isset($_GET['kopie']) && $_GET['kopie']!='N';
$bezeichnung=isset($_GET['bezeichnung']) ? $_GET['bezeichnung'] : '';
$stmt=$db->prepare("insert into gpb_planungkonfig(bezeichnung) values(?)");
$stmt->bind_param('s',$bezeichnung);
$stmt->execute();
$id=$db->insert_id;
if($kopie && isset($_SESSION['planungkonfigid']) && !empty($_SESSION['planungkonfigid'])) {
  $db->query("insert into gpb_planungkonfigzeile select z.* from gpb_planungkonfigzeile z where planungkonfigid=".$_SESSION['planungkonfigid']);
}
$_SESSION['planungkonfigid']=$id;
header('Location:planung.php');
exit;
?>