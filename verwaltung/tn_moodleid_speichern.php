<?php
require_once 'check_login.php';

$tnid=isset($_POST['tnid']) ? (int)$_POST['tnid'] : 0;
$moodleid=isset($_POST['moodleid']) ? (int)$_POST['moodleid'] : 0;

if($tnid<=0) {
  header('Location:tn.php');
  exit;
}

$stmt=$db->prepare("update gpb_tn set moodleid=? where id=? limit 1");
$stmt->bind_param('ii',$moodleid,$tnid);
$stmt->execute();

header('Location:tn_sehen.php?tnid='.$tnid);
exit;
?>