<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$sichtbar=isset($_GET['sichtbar']) && $_GET['sichtbar']!='N';
if(!empty($kursid)) {
  $stmt=$db->prepare("update gpb_kurs set sichtbar=? where id=?");
  $stmt->bind_param('ii',$sichtbar,$kursid);
  $stmt->execute();
  echo 'OK';
  exit;
}
echo 'Bitte Kurs auswählen und Titel eingeben';
exit;
?>