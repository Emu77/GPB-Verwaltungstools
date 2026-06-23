<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungFerien.php';
require_once 'VerwaltungKurs.php';

$suche=isset($_SESSION['raumplan_suche']) ? $_SESSION['raumplan_suche'] : new RaumplanSuche();
if(!isset($suche->am) || empty($suche->am)) {
  $suche->am=date('Y-m-d');
  $suche->addKriterium("beginn<=?",$suche->am,'s');
  $suche->addKriterium("ende>=?",$suche->am,'s');
}
if(!isset($suche->ort)) {
  $suche->ort='';
}

$ferien=new Liste('VerwaltungFerien',$suche->prepareFerien());
Ferien::refsLaden($ferien);

$kurse=new Liste('VerwaltungKurs',$suche->prepare("select * from gpb_kurs_view where "," order by ort,raum"));
Kurs::refsLaden($kurse);
Kurs::anzahlTNLaden($kurse); 

$klassenById=array();
$ferienByKlasseid=array();
foreach($ferien->alle as $f) {
  foreach($f->klassen as $klasse) {
    $klassenById[$klasse->id]=$klasse;
    if(isset($ferienByKlasseid[$klasse->id])) {
      $ferienByKlasseid[$klasse->id][]=$f;
    } else {
      $ferienByKlasseid[$klasse->id]=array($f);
    }
  }
}

$kurseByKlasseid=array();
foreach($kurse->alle as $kurs) {
  foreach($kurs->klassen as $klasse) {
    $klassenById[$klasse->id]=$klasse;
    if(isset($kurseByKlasseid[$klasse->id])) {
      $kurseByKlasseid[$klasse->id][]=$kurs;
    } else {
      $kurseByKlasseid[$klasse->id]=array($kurs);
    }
  }
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Raumplan '.$suche->ort.' '.date('d.m.Y',strtotime($suche->am)));
$seite->anfangGenerieren();
?>
<form action="raumplan_suchen.php" id="suchform" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Tag</th>
    <td><input type="date" name="am" value="<?= $suche->am ?>" onchange="document.getElementById('suchform').submit()" /></td>
  </tr>
  <tr>
    <th>Ort</th>
    <td><select name="ort" onchange="document.getElementById('suchform').submit()">
      <option value="">Alle</option>
      <option value="-" <?= $suche->ort=='-' ? 'selected' : '' ?>>-</option>
      <option value="Mitte" <?= $suche->ort=='Mitte' ? 'selected' : '' ?>>Mitte</option>
      <option value="Neukölln" <?= $suche->ort=='Neukölln' ? 'selected' : '' ?>>Neukölln</option>
    </select></td>
  </tr>
</table>
</form>
<br />
<?php
$feriendivvorhanden=false;
foreach($ferien->alle as $fer) {
  if($fer->art!='Klassenferien') {
?>
<div class="ferien"><a href="ferien_sehen.php?ferienid=<?= $fer->id ?>"><?= $fer->art ?> <?= $fer->anlass ?> <?= $fer->ende==$fer->beginn ? '' : date('d.m.Y',$fer->von).' - '.date('d.m.Y',$fer->bis) ?></a></div>
<?php
    $feriendivvorhanden=true;
  }
}
if($feriendivvorhanden){
?>
<br />
<?php
}
if(!empty($kurseByKlasseid) || !empty($ferienByKlasseid)) {
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Klasse</th>
    <th>Raum</th>
    <th>Kurs</th>
    <th>Dozent</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
    <th></th>
  </tr>
<?php
  foreach($kurseByKlasseid as $klasseid=>$ks) {
    $klasse=$klassenById[$klasseid];
    foreach($ks as $k) {
?>
  <tr>
    <td><a href="klasse_sehen.php?klasseid=<?= $klasse->id ?>"><?= $klasse->bezeichnung ?></a></td>
    <td><a href="raum_sehen.php?raumid=<?= $k->raumid ?>"><?= $k->raum ?> (<?= $k->ort ?><?= $k->anzahlPlaetze<0 ? '' : ', '.$k->anzahlPlaetze.' Plätze' ?>)</a></td>
    <td><?= $k->titel ?> (<?= $k->anzahlTN ?> TN)</td>
<?php
      $k->makeDozentenTd();
      $k->makeMoodleTd();
      $k->makeBearbeitenTd();
?>
  </tr>
<?php
    }
  }
  foreach($ferienByKlasseid as $klasseid=>$fs) {
    $klasse=$klassenById[$klasseid];
    foreach($fs as $f) {
?>
  <tr>
    <td><?= $klasse->bezeichnung ?></td>
    <td colspan="3" class="ferien"><a href="ferien_sehen.php?ferienid=<?= $f->id ?>"><?= $f->anlass ?>  <?= $f->ende==$f->beginn ? '' : date('d.m.Y',$f->von).' - '.date('d.m.Y',$f->bis) ?></a></td>
  </tr>
<?php
    }
  }
?>
</table>
<?php
}
$seite->endeGenerieren();
?>