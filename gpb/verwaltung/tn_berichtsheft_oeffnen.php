<?php
require_once 'check_login.php';

$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0;
$offen=isset($_GET['offen']) && $_GET['offen']!='N';
$zurueck=isset($_GET['zurueck']) ? $_GET['zurueck'] : false;
if($tnid>0) {
  $db->query("update gpb_tn set berichtsheft_offen=".($offen ? 'true' : 'false')." where id=".$tnid." limit 1");
}
if(empty($zurueck)) {
  header('Location:tn_sehen.php?tnid='.$tnid);
  exit;
}
header('Location:'.$zurueck);
exit;
?>