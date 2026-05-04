<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungMassnahme.php';
require_once 'VerwaltungKurs.php';

$massnahme=VerwaltungMassnahme::eineLaden(isset($_GET['massnahmeid']) ? (int)$_GET['massnahmeid'] : 0,'VerwaltungMassnahme');
if(empty($massnahme)) {
  header('Location:massnahmen.php');
  exit;
}
$massnahme->ferienLaden();
$ferienByKw=array();
foreach($massnahme->ferien as $fer) {
  for($d=max($fer->von,$massnahme->von);$d<=min($fer->bis,$massnahme->bis);$d=strtotime('+1week',$d)) {
    $kw=date('Y-W',$d);
    if(isset($ferienByKw[$kw])){
      $ferienByKw[$kw][]=$fer;
    } else {
      $ferienByKw[$kw]=array($fer);
    }
  }
}

$stmt=$db->prepare("
select distinct k.*
  from gpb_klasse_tn ktn 
  join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
  join gpb_kurs_view k on k.id=kk.kursid
  where ktn.tnid in(select tnid from gpb_massnahme_tn where massnahmeid=".$massnahme->id.")
   and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
   and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)
  order by beginn,ende,titel");
$kurse=new Liste('VerwaltungKurs',$stmt);
Kurs::refsLaden($kurse);

$von=strtotime('last monday',strtotime('-2 months'));
$bis=strtotime('next friday',strtotime('+3 months'));
$kurseByKw=array();
foreach($kurse->alle as $kurs) {
  $kurs->angezeigt=false;
  if($kurs->von<$von) $von=$kurs->von;
  if($kurs->bis>$bis) $bis=$kurs->bis;
  foreach($kurs->kw as $kw) {
    if(isset($kurseByKw[$kw])) {
      $kurseByKw[$kw][]=$kurs;
    } else {
      $kurseByKw[$kw]=array($kurs);
    }
  }
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Kurse der TN der Maßnahme '.$massnahme->kuerzel);
$seite->anfangGenerieren();
$massnahme->makeSehen();
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
VerwaltungKurs::makeHeaderTr(false);
for($wann=$von;$wann<=$bis;$wann=strtotime('+1 week',$wann)) {
  $kw=date('Y-W',$wann);
  $todo=true;
  if(isset($ferienByKw[$kw])) {
?>
  <tr>
    <td>KW <?= substr($kw,5) ?></td>
    <td><?= date('d.m.Y',$wann) ?></td>
    <td><?= date('d.m.Y',strtotime('+4 days',$wann)) ?></td>
    <td colspan="8">
<?php
    foreach($ferienByKw[$kw] as $fer) {
?>
      <a href="ferien_sehen.php?ferienid=<?= $fer->id ?>"><?= date('d.m.Y',$fer->von) ?> <?= $fer->bis==$fer->von ? '' : ' - '.date('d.m.Y',$fer->bis) ?> <?= $fer->anlass ?></a><br />
<?php
    }
?>
    </td>
  </tr>
<?php
    $todo=false;
  }
  if(isset($kurseByKw[$kw])) {
    foreach($kurseByKw[$kw] as $kurs) {
      if(!$kurs->angezeigt) {
        $kurs->makeTr(false);
        $kurs->angezeigt=true;
        $todo=false;
      }
    }
  } 
  if($todo) {
?>
  <tr>
    <td>KW <?= substr($kw,5) ?></td>
    <td><?= date('d.m.Y',$wann) ?></td>
    <td><?= date('d.m.Y',strtotime('+4 days',$wann)) ?></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
  </tr>
<?php
  }
}
?>
</table>
<?php
$seite->endeGenerieren();
?>