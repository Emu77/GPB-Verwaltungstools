<?php
require_once 'check_login.php';
$fragenid=isset($_GET['fragenid']) ? (int)$_GET['fragenid'] : 0;
$maxpunkte=isset($_GET['maxpunkte']) ? (int)$_GET['maxpunkte'] : 0;
if($fragenid>0) {
  $stmt=$db->prepare("update rm_klausur_frage set maxpunkte=? where id=? limit 1");
  $stmt->bind_param('ii',$maxpunkte,$fragenid);
  $stmt->execute();
}
echo 'OK';
exit;
?>