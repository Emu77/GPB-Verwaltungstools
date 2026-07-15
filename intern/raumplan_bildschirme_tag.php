<?php
require_once '../db.php';
require_once '../Suche.php';
require_once '../Liste.php';
require_once 'InternKurs.php';

$suche=new Suche('ort','wann');
$suche->ort=isset($_GET['ort']) ? $_GET['ort'] : '';
if(!empty($suche->ort)) {
  $suche->addKriterium('ort=?',$suche->ort,'s');
}
$wann=isset($_GET['wann']) ? $_GET['wann'] : false;
if(!empty($wann)) {
  $wann=strtotime($wann);
  $getwann=$wann;
}
if(empty($wann)) {
  $wann=strtotime('today');
  if(date('w',$wann)==0 || date('w',$wann)>=5) $wann=strtotime('next monday',$wann);

  $suche->wann=date('Y-m-d',$wann);
  $stmt=$db->prepare("select *,date_sub(beginn,interval 1 day) as vorbeginn from gpb_ferien where (art='Feiertag' or art='GPB Ferien' or (art='Institutsferien' and ort=?)) and ende>=? order by beginn,ende");
  $stmt->bind_param('ss',$suche->ort,$suche->wann);
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $row->von=strtotime($row->vorbeginn);
    $row->bis=strtotime($row->ende);
    if($wann>=$row->von && $wann<=$row->bis) {
      $wann=strtotime('+1 day',$row->bis);
      if(date('w',$wann)==0 || date('w',$wann)>=5) $wann=strtotime('next monday',$wann);
    }
  }
}

$suche->wann=date('Y-m-d',$wann);
$suche->addKriterium('beginn<=?',$suche->wann,'s');
$suche->addKriterium('ende>=?',$suche->wann,'s');
$kurse=new Liste('InternKurs',$suche->prepare("select * from gpb_kurs_view where","order by ort,raum,titel"));
Kurs::refsLaden($kurse);
foreach($kurse->alle as $kurs) {
  $kurs->makeKlassenText();
}
function kurse_vergleichen($k0,$k1) {
  $d=$k0->phase-$k1->phase;
  if($d!=0) return $d;
  $d=strcasecmp($k0->klassenText,$k1->klassenText);
  if($d!=0) return $d;
  $d=strcasecmp($k0->ort,$k1->ort);
  if($d!=0) return $d;
  $d=strcasecmp($k0->raum,$k1->raum);
  if($d!=0) return $d;
  $d=strcasecmp($k0->modultitel,$k1->modultitel);
  if($d!=0) return $d;
  return 0;
}
usort($kurse->alle,'kurse_vergleichen');

require_once 'InternSeite.php';
$tagnamen=array('Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag');
$seite=new InternSeite('Raumplan '.($suche->ort ? $suche->ort.' ' : '').$tagnamen[(int)date('w',$wann)].', '.date('d.m.Y',$wann));
$seite->anfangGenerieren();
?>
<script>
function neuladen() {
<?php
  $params=array();
  if(!empty($suche->ort)) {
    $params[]='ort='.$suche->ort;
  }
  if(!empty($getwann) && $getwann>=strtotime('today')) {
    $params[]='wann='.date('Y-m-d',$getwann);
  }
?>
  location.href='../intern/raumplan_bildschirme_tag.php<?= empty($params) ? '' : '?'.implode('&',$params) ?>';
}
setTimeout(neuladen,60*60*1000);
</script>
<script type="module">
if(document.body.scrollHeight>window.innerHeight) {
  let els=document.getElementsByClassName('phasentitel');
  for(let i=els.length-1;i>=0;--i) {
    els[i].style.display='none';
  }
}
</script>
<style>
th, td {
  text-align: left;
}
tr:nth-child(even) {
  background-color: #EFAA23;
}
tr:nth-child(odd) {
  background-color: #DADADA;
}
tr.phasentitel {
  background-color:white;
}
.phasentitel td {
  font-weight:bold;
}
a {
  text-decoration:none;
  color:black;
}
td.kw div {
  white-space:pre;
}
<?php
if(!empty($suche->ort)) { //falls Ort vorgegeben, Spalte verstecken
?>
td:nth-child(3), th:nth-child(3) {
  display:none;
}
<?php
}
?>
</style>
<table border="0" cellspacing="0" cellpadding="6" style="border-collapse:collapse;width:100%;">
  <!-- tr>
    <th>KW</th>
    <th>Klassen</th>
    <th>Ort</th>
    <th>Raum</th>
    <th>Dozent</th>
    <th>Modul</th>
  </tr -->
<?php
$phase=0;
foreach($kurse->alle as $kurs) {
  if($kurs->phase!=$phase) {
    $phase=$kurs->phase;
?>
  <tr class="phasentitel"><td colspan="6"><br /><?= InternKurs::$phasen[$phase] ?></td></tr>
<?php
  }
?>
  <tr>
<?php
  $kurs->makeZeitraumTds();
  $kurs->makeKlassenTd();
  $kurs->makeLocationTds();
  $kurs->makeDozentenTd();
  $kurs->makeTitelTd();
?>
  </tr>
<?php
}
?>
</table>
<?php
$seite->endeGenerieren();
?>