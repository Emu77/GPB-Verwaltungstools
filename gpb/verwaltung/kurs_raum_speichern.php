<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$raumid=isset($_GET['raumid']) ? (int)$_GET['raumid'] : 0;
if($kursid>0) {
  $db->query("update gpb_kurs set raumid=".($raumid>0 ? $raumid : 'null')." where id=".$kursid." limit 1");
}
echo 'OK';
exit;
?>