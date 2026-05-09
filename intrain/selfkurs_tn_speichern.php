<?php
require_once 'check_login.php';
$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0;
$selfkursid=isset($_GET['selfkursid']) ? (int)$_GET['selfkursid'] : 0;
$was=isset($_GET['was']) ? $_GET['was'] : false;
$neu=isset($_GET['neu']) && !empty($_GET['neu']) ? $_GET['neu'] : null;
if($tnid>0 && $selfkursid>0 && ($was=='einstieg' || $was=='ausstieg')) {
  $stmt=$db->prepare("update gpb_selfkurs_tn set ".$was."=? where selfkursid=? and tnid=?");
  $stmt->bind_param('sii',$neu,$selfkursid,$tnid);
  $stmt->execute();
}

$redirect=isset($_GET['redirect']) ? $_GET['redirect'] : 'tn_selfkurse.php?tnid='.$tnid;
header('Location:'.$redirect);
exit;
?>