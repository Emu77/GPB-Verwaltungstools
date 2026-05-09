<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once '../Liste.php';
require_once '../Ferien.php';
require_once 'VerwaltungRaum.php';
require_once 'VerwaltungKurs.php';

$raum=VerwaltungRaum::einenLaden(isset($_GET['raumid']) ? (int)$_GET['raumid'] : 0,'VerwaltungRaum');
if(empty($raum)) {
  header('Location:raeume.php');
  exit;
}

$suche=isset($_SESSION['raum_kurse_suche']) ? $_SESSION['raum_kurse_suche'] : new Suche('von','bis');
if(empty($suche->von)) {
  $suche->von=date('Y-m-d',strtotime('last monday',strtotime('-2 months')));
  $suche->addKriterium('ende>=?',$suche->von,'s');
}
if(empty($suche->bis)) {
  $suche->bis=date('Y-m-d',min(strtotime('+6 months',strtotime($suche->von)),strtotime('next friday',strtotime('+3 months'))));
  $suche->addKriterium('beginn<=?',$suche->bis,'s');
}

$stmt=$suche->prepare("select k.* from gpb_kurs_view k where k.raumid=".$raum->id." and ","order by k.beginn,k.ende,k.titel");
$kurse=new Liste('VerwaltungKurs',$stmt);
Kurs::anzahlTNLaden($kurse);
Kurs::refsLaden($kurse);

$von=strtotime($suche->von);
$bis=strtotime($suche->bis);
$kurseByKw=array();
foreach($kurse->alle as $kurs) {
  $kurs->angezeigt=false;
  foreach($kurs->kw as $kw) {
    if(isset($kurseByKw[$kw])) {
      $kurseByKw[$kw][]=$kurs;
    } else {
      $kurseByKw[$kw]=array($kurs);
    }
  }
}

$stmt=$db->prepare("select * from gpb_ferien where beginn<='".date('Y-m-d',$bis)."' and ende>='".date('Y-m-d',$von)."' and (art='Feiertag' or art='GPB Ferien' or ort='".addslashes($raum->ort)."') order by beginn,ende,art");
$ferien=new Liste('Ferien',$stmt);
$ferienByKw=array();
foreach($ferien->alle as $fer) {
  foreach($fer->kw as $kw) {
    if(isset($ferienByKw[$kw])) {
      $ferienByKw[$kw][]=$fer;
    } else {
      $ferienByKw[$kw]=array($fer);
    }
  }
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Kurse im Raum '.$raum->tuer.' ('.$raum->ort.')');
$seite->anfangGenerieren();
$raum->makeSehen();
?>
<form action="raum_kurse_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="raumid" value="<?= $raum->id ?>" />
  Zwischen <input type="date" name="von" value="<?= $suche->von ?>" />
  und <input type="date" name="bis" value="<?= $suche->bis ?>" />
  <input type="submit" value="Suchen" />
</form>
<br />
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
VerwaltungKurs::makeHeaderTr(true);
for($wann=$von;$wann<=$bis;$wann=strtotime('+1 week',$wann)) {
  $kw=date('Y-W',$wann);
  $todo=true;
  if(isset($ferienByKw[$kw])) {
?>
  <tr>
    <td>KW <?= substr($kw,5) ?></td>
    <td><?= date('d.m.Y',$wann) ?></td>
    <td><?= date('d.m.Y',strtotime('+4 days',$wann)) ?></td>
    <td colspan="8" class="ferien">
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
        $kurs->makeTr(true);
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