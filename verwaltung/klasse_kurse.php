<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungKlasse.php';
require_once '../Ferien.php';
require_once 'VerwaltungKurs.php';

$klasse=Klasse::eineLaden(isset($_GET['klasseid']) ? (int)$_GET['klasseid'] : 0,'VerwaltungKlasse');
if(empty($klasse)) {
  header('Location:klassen.php');
  exit;
}

$stmt=$db->prepare("select * from gpb_kurs_view where id in(select kursid from gpb_kurs_klasse where klasseid=".$klasse->id.") order by beginn,ende,titel");
$kurse=new Liste('VerwaltungKurs',$stmt);
Kurs::refsLaden($kurse);

$von=empty($klasse->von) ? strtotime(date('w')==1 ? 'last monday' : 'today') : $klasse->von;
$bis=empty($klasse->bis) ? strtotime('next friday',$von) : $klasse->bis;
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

//Institut- und Klassenferien sind schon in der Klasse geladen
$stmt=$db->prepare("select * from gpb_ferien where art<>'Klassenferien' and art<>'Institutsferien' and beginn<='".date('Y-m-d',$bis)."' and ende>='".date('Y-m-d',$von)."' order by beginn,ende");
$ferien=new Liste('Ferien',$stmt);

$ferienByKw=array();
foreach($klasse->ferien as $f) {
  foreach($f->kw as $w) {
    if(isset($ferienByKw[$w])) {
      $ferienByKw[$w][]=$f;
    } else {
      $ferienByKw[$w]=array($f);
    }
  }
}
foreach($ferien->alle as $f) {
  foreach($f->kw as $w) {
    if(isset($ferienByKw[$w])) {
      $ferienByKw[$w][]=$f;
    } else {
      $ferienByKw[$w]=array($f);
    }
  }
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Kurse der Klasse '.$klasse->bezeichnung);
$seite->anfangGenerieren();
$klasse->makeSehen();
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
VerwaltungKurs::makeHeaderTr(false);
for($wann=$von;$wann<=$bis;$wann=strtotime('+1 week',$wann)) {
  $kw=date('Y-W',$wann);
  $done=false;
  if(isset($ferienByKw[$kw])) {
    $arbeitstage=array();
    for($t=0;$t<5;++$t) {
      $ww=strtotime('+'.$t.' days',$wann);
      $arbeitstage[date('Y-m-d',$ww)]=$ww;
    }
?>
  <tr>
    <td>KW <?= substr($kw,5) ?></td>
    <td><?= date('d.m.Y',$wann) ?></td>
    <td><?= date('d.m.Y',strtotime('+4 days',$wann)) ?></td>
    <td colspan="8">
<?php
    foreach($ferienByKw[$kw] as $f) {
      foreach($arbeitstage as $t=>$ww) {
        if($ww>=$f->von && $ww<=$f->bis) {
          unset($arbeitstage[$t]);
        }
      }
?>
      <div><a href="ferien_sehen.php?ferienid=<?= $f->id ?>"><?= date('d.m.Y',$f->von) ?> <?= $f->bis==$f->von ? '' : date('d.m.Y',$f->bis) ?> <?= $f->anlass ?></a></div>
<?php
    }
?>
    </td>
  </tr>
<?php
    $done|=empty($arbeitstage);
  }
  if(isset($kurseByKw[$kw])) {
    foreach($kurseByKw[$kw] as $kurs) {
      if(!$kurs->angezeigt) {
        $kurs->makeTr(false);
        $kurs->angezeigt=true;
      }
      $done=true;
    }
  }
  if(!$done) {
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
    <td><a href="kurs_bearbeiten.php?id=0&klassenids=<?= $klasse->id ?>&beginn=<?= date('Y-m-d',$wann) ?>&ende=<?= date('Y-m-d',strtotime('+4 days',$wann)) ?>">Neuer Kurs</a></td>
  </tr>
<?php
  }
}
?>
</table>
<?php
$seite->endeGenerieren();
?>