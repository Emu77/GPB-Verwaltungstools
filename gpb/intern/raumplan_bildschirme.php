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
  $wann=(int)date('w');
  if($wann==0 || $wann>=5) {
    $wann=strtotime('next monday');
  } else {
    $wann=strtotime('today');
  }
}
$von=date('w',$wann)==1 ? $wann : strtotime('last monday',$wann);
$bis=date('w',$wann)==5 ? $wann : strtotime('next friday',$wann);
$suche->von=date('Y-m-d',$von);
$suche->bis=date('Y-m-d',$bis);

function skipFerien() {
  global $db,$suche,$von,$bis;
  $stmt=$db->prepare("select * from gpb_ferien where (art='Feiertag' or art='GPB Ferien' or (art='Institutsferien' and ort=?)) and beginn<=? and ende>=? order by beginn,ende");
  $stmt->bind_param('sss',$suche->ort,$suche->bis,$suche->von);
  $stmt->execute();
  $result=$stmt->get_result();
  $neuvon=$von;
  $neubis=$bis;
  $nurferien=false;
  while($row=$result->fetch_object()) {
    $row->von=strtotime($row->beginn);
    $row->bis=strtotime($row->ende);
    if($row->von<=$neuvon && $row->bis>=$neubis) {
      $nurferien=true;
      break;
    }
    if($row->von<=$neuvon && $row->bis>=$neuvon) {
      $neuvon=strtotime('+1 day',$row->bis);
      $t=date('w',$neuvon);
      if($t==0 || $t==6) $neuvon=strtotime('next monday',$neuvon);
      if($neuvon>$neubis) {
        $nurferien=true;
        break;
      }
    }
    if($row->bis<=$neubis && $row->bis>=$neubis) {
      $neubis=strtotime('-1 day',$row->von);
      $t=date('w',$neubis);
      if($t==0 || $t==6) $neubis=strtotime('last friday',$neubis);
      if($neubis<$neuvon) {
        $nurferien=true;
        break;
      }
    }
  }
  $result->free();
  if($nurferien) return true;
  if($neuvon!=$von) {
    $von=$neuvon;
    $suche->von=date('Y-m-d',$von);
  }
  if($neubis!=$bis) {
    $bis=$neubis;
    $suche->bis=date('Y-m-d',$bis);
  }
  return false;
}
while(skipFerien()) {
  $von=strtotime('+1 week',$von);
  $bis=strtotime('+1 week',$bis);
  $suche->von=date('Y-m-d',$von);
  $suche->bis=date('Y-m-d',$bis);
}

$suche->addKriterium('beginn<=?',$suche->bis,'s');
$suche->addKriterium('ende>=?',$suche->von,'s');
$kurse=new Liste('InternKurs',$suche->prepare("select * from gpb_kurs_view where","order by ort,raum,titel"));
Kurs::refsLaden($kurse);
foreach($kurse->alle as $kurs) {
  $kurs->makeKlassenText();
}
function kurse_vergleichen($k0,$k1) {
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
$seite=new InternSeite('Raumplan '.($suche->ort ? $suche->ort.' ' : '').' KW '.date('W',$von).' ('.date('d.m.Y',$von).' - '.date('d.m.Y',$bis).')');
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
  location.href='../intern/raumplan_bildschirme.php<?= empty($params) ? '' : '?'.implode('&',$params) ?>';
}
setTimeout(neuladen,60*60*1000);
</script>
<style>
th, td {
  text-align: left;
}
tr:nth-child(even) {
  background-color: #eab80f;
}
tr:nth-child(odd) {
  background-color: #dadada;
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
  <tr>
    <th>KW</th>
    <th>Klassen</th>
    <th>Ort</th>
    <th>Raum</th>
    <th>Dozent</th>
    <th>Modul</th>
  </tr>
<?php
foreach($kurse->alle as $kurs) {
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