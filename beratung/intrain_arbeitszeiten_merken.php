<?php
require_once 'check_login.php';
require_once 'intrain_teilzeit_wochenue.php';
$wochenue=isset($_POST['wochenue']) ? (int)$_POST['wochenue'] : null;
if($wochenue!==null) {
  $_SESSION['intrain_wochenue']=max($teilzeit_minwochenue,min($teilzeit_maxwochenue,$wochenue));
}
$arbeitszeiten=isset($_POST['arbeitszeiten']) ? $_POST['arbeitszeiten'] : false;
if(empty($arbeitszeiten)) {
  if(isset($_SESSION['intrain_arbeitszeiten'])) unset($_SESSION['intrain_arbeitszeiten']);
} else {
  $_SESSION['intrain_arbeitszeiten']=(array)json_decode($arbeitszeiten);
}
require_once 'intrain_seite.php';
exit;
?>