<?php
require_once 'check_login.php';

$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
if($kursid) {
  $db->query("delete from gpb_ihkprojektantrag where kursid=".$kursid);
  $db->query("delete from gpb_ihkprojektantragsformular where kursid=".$kursid);
  $db->query("delete from gpb_pruefungsvorbereitung_termin where kursid=".$kursid);
  $db->query("delete from gpb_pruefungsvorbereitung_klasse where kursid=".$kursid);
  $db->query("delete from gpb_pruefungsvorbereitung where kursid=".$kursid);
  $db->query("delete from gpb_kurs_klasse where kursid=".$kursid);
  $db->query("delete from gpb_kurs_dozent where kursid=".$kursid);
  $db->query("delete from gpb_kurs_tagesbericht where kursid=".$kursid);
  $db->query("delete from gpb_kurs_kurzbericht where kursid=".$kursid);
  $db->query("delete from gpb_kurs where id=".$kursid);
}

header('Location:kurse.php');
exit;
?>