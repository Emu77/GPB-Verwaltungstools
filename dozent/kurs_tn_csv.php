<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'DozentKurs.php';
require_once 'DozentTn.php';

$windows=strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'win')!==false;
header('Content-Type:text/csv; charset='.($windows ? 'iso-8859-15' : 'utf-8'));

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'DozentKurs');
if(empty($kurs)) {
  header('Content-Disposition:attachment; filename=kursnichtgefunden.csv');
  echo 'Kurs nicht gefunden.';
  exit;
}

header('Content-Disposition:attachment; filename='.str_replace(',','_',str_replace(' ','_',$kurs->titel)).'_Teilnehmer.csv');

$stmt=empty($kurs->klassenids) ? null : $db->prepare("select distinct tn.*,ktn.klasseid
  from gpb_klasse_tn ktn
  join gpb_tn_view tn on tn.id=ktn.tnid
  where ktn.klasseid in(".implode(',',$kurs->klassenids).")
  and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=curdate())
  and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=curdate()) order by ".$ich->tnsortierung);
$tns=new Liste('DozentTn',$stmt);
Tn::refsKlassenLaden($tns);

if(empty($kurs->klassen)) {
  echo 'Keine Klassen.';
} else if(empty($tns->alle)) {
  echo 'Keine TN.';
} else {
  $out=fopen('php://output','w');
  if($windows) {
    fprintf($out,chr(0xEF).chr(0xBB).chr(0xBF));
  }
  DozentTn::makeCSVHeader($out,$windows);
  foreach($tns->alle as $tn) {
    $tn->makeCSV($out,$windows);
  }
  fclose($out);
}
?>