<?php
require_once 'Suche.php';
require_once 'db.php';
@session_start();
require_once 'Liste.php';
require_once 'Ferien.php';
require_once 'OeffentlichKurs.php';

$suche=isset($_SESSION['raumplan_suche']) ? $_SESSION['raumplan_suche'] : new RaumplanSuche();
if(!isset($suche->am) || empty($suche->am)) {
  $suche->am=date('Y-m-d');
  $suche->addKriterium("beginn<=?",$suche->am,'s');
  $suche->addKriterium("ende>=?",$suche->am,'s');
}
if(!isset($suche->ort)) {
  $suche->ort='';
}

$ferien=new Liste('Ferien',$suche->prepareFerien());
Ferien::refsLaden($ferien);

$kurse=new Liste('OeffentlichKurs',$suche->prepare("select * from gpb_kurs_view where","order by ort,raum"));
Kurs::refsLaden($kurse);

$klassenById=array();
$ferienByKlasseid=array();
foreach($ferien->alle as $fer) {
  foreach($fer->klassen as $klasse) {
    $klassenById[$klasse->id]=$klasse;
    if(isset($ferienByKlasseid[$klasse->id])) {
      $ferienByKlasseid[$klasse->id][]=$fer;
    } else {
      $ferienByKlasseid[$klasse->id]=array($fer);
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

require_once 'OeffentlichSeite.php';
$seite=new OeffentlichSeite('Startseite');
$seite->anfangGenerieren();
if(isset($_SESSION['verwalter_ich'])) {
?>
<a href="verwaltung/">Zum Verwaltungsbereich</a><br />
<br />
<a class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" href="<?= $moodleurl ?>" target="moodle">Zum Moodle</a>
<?php
} else if(isset($_SESSION['dozent_ich'])) {
?>
<a href="dozent/">Zum Dozentenbereich</a><br />
<br />
<a class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" href="<?= $moodleurl ?>" target="moodle">Zum Moodle</a>
<?php
} else if(isset($_SESSION['tn_ich'])) {
?>
<a href="tn/">Zum Teilnehmerbereich</a><br />
<br />
<a class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" href="<?= $moodleurl ?>" target="moodle">Zum Moodle</a>
<?php
} else {
?>
<form action="login.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Nutzername oder Emailadresse</th>
    <td><input type="text" name="nutzername" style="width:150px;" /></td>
  </tr>
  <tr>
    <th>Moodle-Passwort</th>
    <td><input type="password" name="passwort" style="width:150px;" /></td>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"><a href="<?= $moodleurl ?>" target="moodle">Moodle</a></th>
    <td><input type="submit" value="Einloggen" /></td>
  </tr>
</table>
</form>
<br />
<?php
}
?>
<h1 style="padding-left:0;">Raumplan <?= $suche->ort ?> <?= date('d.m.Y',strtotime($suche->am)) ?></h1>
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
<div class="ferien"><?= $fer->art ?> <?= $fer->anlass ?> <?= $fer->ende==$fer->beginn ? '' : date('d.m.Y',$fer->von).' - '.date('d.m.Y',$fer->bis) ?></div>
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
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Dozenten<br />Kontakt in Moodle</th>
  </tr>
<?php
  foreach($kurseByKlasseid as $klasseid=>$ks) {
    $klasse=$klassenById[$klasseid];
    foreach($ks as $k) {
?>
  <tr>
    <td><?= $klasse->bezeichnung ?></td>
    <td><?= $k->raum ?> (<?= $k->ort ?>)</td>
    <td><?= $k->titel ?></td>
<?php
      $k->makeMoodleMiniTd();
      $k->makeDozentenTd();
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
    <td colspan="3" class="ferien"><?= $f->anlass ?>  <?= $f->ende==$f->beginn ? '' : date('d.m.Y',$f->von).' - '.date('d.m.Y',$f->bis) ?></td>
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