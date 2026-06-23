<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$raumid=isset($_GET['raumid']) ? (int)$_GET['raumid'] : 0;
if(!empty($kursid)) {
  $db->query("update gpb_kurs set raumid=".(empty($raumid) ? 'null' : $raumid)." where id=".$kursid);
  echo 'OK';
  exit;
}
echo 'Bitte Kurs auswählen';
exit;
?>