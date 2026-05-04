<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0;
$nachnote=isset($_GET['nachnote']) ? $_GET['nachnote'] : null;
if(empty($nachnote)) {
  $nachnote=null;
} else {
  $nachnote=(int)$nachnote;
}

$stmt=$db->prepare("update gpb_note set nachnote=? where kursid=? and tnid=? limit 1");
$stmt->bind_param('iii',$nachnote,$kursid,$tnid);
$stmt->execute();

echo 'OK';
exit;
?>