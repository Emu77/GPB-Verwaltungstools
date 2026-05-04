<?php
require_once 'check_login.php';
require_once 'spez_teilzeit_wochenue.php';
$wochenue=isset($_POST['wochenue']) ? (int)$_POST['wochenue'] : null;
if($wochenue!==null) {
  $_SESSION['spez_wochenue']=max($teilzeit_minwochenue,min($teilzeit_maxwochenue,$wochenue));
}
header('Location:spezialisten.php');
exit;
?>