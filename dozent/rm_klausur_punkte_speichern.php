<?php
require_once 'check_login.php';
$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0;
$fragenid=isset($_GET['fragenid']) ? (int)$_GET['fragenid'] : 0;
$punkte=isset($_GET['punkte']) ? (float)$_GET['punkte'] : 0;
if($tnid>0 && $fragenid>0) {
  $stmt=$db->prepare("update rm_klausur set punkte=? where tnid=? and fragenid=? limit 1");
  $stmt->bind_param('dii',$punkte,$tnid,$fragenid);
  $stmt->execute();
}
echo 'OK';
exit;
?>