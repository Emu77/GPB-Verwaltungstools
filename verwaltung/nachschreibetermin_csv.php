<?php
require_once 'check_login.php';
require_once 'VerwaltungTn.php';
require_once '../Liste.php';

$termin=isset($_GET['termin']) ? $_GET['termin'] : '';

$nachschreibungen=array();
$kursids=array();
$stmt=$db->prepare("select n.kursid,concat(tn.vorname,' ',tn.nachname) as tnname,k.titel
  from gpb_note n
  join gpb_tn_view tn on tn.id=n.tnid
  join gpb_kurs_view k on k.id=n.kursid
  where n.nachschreibestatus='genehmigt' and n.nachschreibetermin=?
  order by nachname,vorname");
$stmt->bind_param('s',$termin);
$stmt->execute();
$result=$stmt->get_result();
while($row=$result->fetch_object()) {
  $nachschreibungen[]=$row;
  $kursids[$row->kursid]=true;
}
$result->free();

$dozentenByKursid=array();
if(!empty($kursids)) {
  $result=$db->query("select kd.kursid,concat(d.vorname,' ',d.nachname) as dozentname from gpb_kurs_dozent kd join gpb_dozent d on d.id=kd.dozentid where kd.kursid in(".implode(',',array_keys($kursids)).")");
  while($row=$result->fetch_object()) {
    if(isset($dozentenByKursid[$row->kursid])) {
      $dozentenByKursid[$row->kursid][]=$row->dozentname;
    } else {
      $dozentenByKursid[$row->kursid]=array($row->dozentname);
    }
  }
  $result->free();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Nachschreiber_'.str_replace(' ','_',$termin).'.csv');
echo "Modul;Name/TN;Name/Doz.\n";
foreach($nachschreibungen as $nachschreibung) {
  $dozenten=$dozentenByKursid[$nachschreibung->kursid];
  echo '"'.str_replace('"','_',$nachschreibung->titel).'";"'.str_replace('"','_',$nachschreibung->tnname).'";"'.str_replace('"','_',implode(',',$dozenten))."\"\n";
}
exit;
?>