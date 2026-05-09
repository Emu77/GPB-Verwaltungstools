<?php
require_once 'check_login.php';
$datum=isset($_POST['datum']) ? $_POST['datum'] : false;
$uhrzeit=isset($_POST['uhrzeit']) ? $_POST['uhrzeit'] : false;
$wann=empty($datum) || empty($uhrzeit) ? false : $datum.' '.$uhrzeit;
$ort=isset($_POST['ort']) ? $_POST['ort'] : '';
if(!empty($wann)) {
  $stmt=$db->prepare("insert into gpb_eignungstest_termin(wann,ort) values(?,?)");
  $stmt->bind_param('ss',$wann,$ort);
  $stmt->execute();
//  $id=$db->insert_id;
}
header('Location:eignungstests.php');
exit;
?>