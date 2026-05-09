<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungDozent.php';
require_once 'VerwaltungKurs.php';
require_once '../Ferien.php';

$dozent=Dozent::einenLaden(isset($_GET['dozentid']) ? (int)$_GET['dozentid'] : 0,'VerwaltungDozent');
if(empty($dozent)) {
  header('Location:dozenten.php');
  exit;
}

$suche=isset($_SESSION['dozent_kurse_suche']) ? $_SESSION['dozent_kurse_suche'] : new Suche('von','bis','titel','ort','raum','klassebez','dozentname','moodleid','todo');
if(empty($suche->where)) {
  $suche->addKriterium("beginn<=?",date('Y-m-d',strtotime('+3 months')),'s');
  $suche->addKriterium("ende>=?",date('Y-m-d',strtotime('-2 months')),'s');
}

$kurse=new Liste('VerwaltungKurs',$suche->prepare("select * from gpb_kurs_view 
  where id in(select kursid from gpb_kurs_dozent where dozentid=".$dozent->id.") and ",
  " order by beginn,ende,titel limit 50"));
Kurs::anzahlTNLaden($kurse);
Kurs::refsLaden($kurse);

if(empty($kurse->alle)) {
  $von=strtotime('last monday',strtotime('-2 months'));
  $bis=strtotime('next friday',strtotime('+3 months'));
} else {
  $von=empty($suche->von) ? 0 : strtotime('last monday',strtotime($suche->von));
  $bis=empty($suche->bis) ? 0 : strtotime('next friday',strtotime($suche->bis));
  $kurseByKw=array();
  foreach($kurse->alle as $kurs) {
    $kurs->angezeigt=false;
    if($von==0 || $kurs->von<$von) $von=$kurs->von;
    if($bis==0 || $kurs->bis>$bis) $bis=$kurs->bis;
    foreach($kurs->kw as $kw) {
      if(isset($kurseByKw[$kw])) {
        $kurseByKw[$kw][]=$kurs;
      } else {
        $kurseByKw[$kw]=array($kurs);
      }
    }
  }
}

$stmt=$db->prepare("select * from gpb_ferien where beginn<='".date('Y-m-d',$bis)."' and ende>='".date('Y-m-d',$von)."' order by beginn,ende,art");
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
$seite=new VerwaltungSeite('Kurse von Dozent '.$dozent->vorname.' '.$dozent->nachname);
$seite->anfangGenerieren();
$dozent->makeSehen();
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
VerwaltungKurs::makeHeaderTr(true);
?>
  <form action="dozent_kurse_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="dozentid" value="<?= $dozent->id ?>" />
  <tr>
<?php
$suche->makeVonBisInput(3);
$suche->makeStringInput('titel');
$suche->makeEnumInput('ort',array('-','Mitte','Neukölln'));
$suche->makeStringInput('raum');
$suche->makeStringInput('klassebez',2);
$suche->makeStringInput('dozentname');
$suche->makeStringInput('moodleid',1,150,$moodleisttest ? 'testmoodle' : 'moodle');
?>
    <td>
<?php
$suche->makeBooleanInput('todo','Unvollständig');
$suche->makeSubmit(false);
?>
    </td>
  </tr>
  </form>
<?php
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
    <td><a href="kurs_bearbeiten.php?id=0&dozentenids=<?= $dozent->id ?>&beginn=<?= date('Y-m-d',$wann) ?>&ende=<?= date('Y-m-d',strtotime('+4 days',$wann)) ?>">Neuer Kurs</a></td>
  </tr>
<?php
  }
}
?>
</table>
<?php
$seite->endeGenerieren();
?>