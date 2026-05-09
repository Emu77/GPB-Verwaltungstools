<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;
if(!empty($kursid)) {
  //FIXME Kurstitel ändern
  $db->query("update gpb_kurs set modulid=".$modulid." where id=".$kursid);
  echo 'OK';
  exit;
}
echo 'Bitte Kurs auswählen';
exit;
?>