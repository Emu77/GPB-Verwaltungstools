<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once '../Ferien.php';
require_once 'TnKurs.php';

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

$kurse=new Liste('TnKurs',$suche->prepare("select * from gpb_kurs_view where (moodleid>0 or sichtbar) and","order by ort,raum"));
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

require_once 'TnSeite.php';
$seite=new TnSeite('Raumplan '.$suche->ort.' '.date('d.m.Y',strtotime($suche->am)));
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
    <th>Dozent</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
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
      $k->makeDozentenTd();
      $k->makeMoodleMiniTd();
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