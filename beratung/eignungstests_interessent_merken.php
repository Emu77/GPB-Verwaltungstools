<?php
require_once 'check_login.php';

$mitisid=isset($_GET['mitisid']) ? (int)$_GET['mitisid'] : 0;
if($mitisid>0){
  $result=$db->query("select id,mitisid,anrede,vorname,nachname from gpb_basket b where mitisid=".$mitisid." limit 1");
  $basket=$result->fetch_object();
  $result->free();
} else {
  $basket=false;
}

if(!empty($basket)) {
  $_SESSION['eignung_interessent']=$basket;
}
header('Location:eignungstests.php');
exit;
?>