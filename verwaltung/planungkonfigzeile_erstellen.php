<?php
require_once '../leitung/check_login.php';
if(!isset($_SESSION['planungkonfigid']) || empty($_SESSION['planungkonfigid'])) {
  header('Location:planung.php');
  exit;
}

$was=isset($_GET['was']) ? $_GET['was'] : '';
$wasid=isset($_GET['wasid']) ? (int)$_GET['wasid'] : 0;
if(($was=='klasse' || $was=='dozent' || $was=='raum') && $wasid>0) {
  $stmt=$db->prepare("select * from gpb_planungkonfigzeile where planungkonfigid=? and was=? and wasid=? limit 1");
  $stmt->bind_param('isi',$_SESSION['planungkonfigid'],$was,$wasid);
  $stmt->execute();
  $result=$stmt->get_result();
  $schon=$result->fetch_object();
  $result->free();
  if(!$schon) {
    $stmt=$db->prepare("insert into gpb_planungkonfigzeile(planungkonfigid,was,wasid) values(?,?,?)");
    $stmt->bind_param('isi',$_SESSION['planungkonfigid'],$was,$wasid);
    $stmt->execute();
  }
}
header('Location:planung.php');
exit;
?>