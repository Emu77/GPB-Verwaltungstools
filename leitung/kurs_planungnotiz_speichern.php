<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$notiz=isset($_GET['notiz']) ? $_GET['notiz'] : '';
$farbe=isset($_GET['farbe']) ? $_GET['farbe'] : '';
if(strtolower($farbe)=='#ffffff') {
  $farbe='';
}
if(!empty($kursid)) {
  $stmt=$db->prepare("update gpb_kurs set planungnotiz=?,planungfarbe=? where id=?");
  $stmt->bind_param('ssi',$notiz,$farbe,$kursid);
  $stmt->execute();
  echo 'OK';
  exit;
}
echo 'Bitte Kurs auswählen';
exit;
?>