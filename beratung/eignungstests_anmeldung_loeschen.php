<?php
require_once 'check_login.php';
require_once 'eignungstests_funktionen.php';

$anmeldungid=isset($_GET['anmeldungid']) ? (int)$_GET['anmeldungid'] : 0;
if($anmeldungid>0){
  eignungstestAnmeldungLoeschen($anmeldungid);
}
header('Location:eignungstests.php');
exit;
?>