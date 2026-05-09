<?php
require_once 'check_login.php';
require_once 'spez_teilzeit_wochenue.php';
$wochenh=isset($_POST['wochenh']) ? (float)$_POST['wochenh'] : null;
if($wochenh!==null) {
  $_SESSION['spez_wochenue']=max($teilzeit_minwochenue,min($teilzeit_maxwochenue,floor($wochenh/0.75)));
}
header('Location:spezialisten.php');
exit;
?>