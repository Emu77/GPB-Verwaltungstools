<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';
require_once 'DozentTn.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'DozentKurs');
if(empty($kurs) || !$kurs->hatProjektantrag || !in_array($ich->id,$kurs->dozentenids)) {
  header('Location:kurse.php');
  exit;
}

$tn=Tn::einenLaden(isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0,'DozentTn');
if(empty($tn)) {
  header('Location:ihkprojektantrag.php?kursid='.$kurs->id);
  exit;
}
//todo Prüfen, dass der TN in einer Klasse des Kurses ist

$result=$db->query("select *,greatest(ifnull(bezeichnung_geaendertam,''),ifnull(beschreibung_geaendertam,''),ifnull(zielsetzung_geaendertam,''),ifnull(zeitplan_geaendertam,'')) as daten_geaendertam from gpb_ihkprojektantrag where kursid=".$kurs->id." and tnid=".$tn->id." limit 1");
$antrag=$result->fetch_object();
$result->free();
$betreuer=null;
if(empty($antrag)) {
  $antrag=(object)array(
    'kursid'=>$kurs->id,
    'tnid'=>$tn->id,
    'betreuerid'=>null,
    'ampel'=>'red',
    'bezeichnung'=>'',
    'bezeichnung_geaendertam'=>null,
    'beschreibung'=>'',
    'beschreibung_geaendertam'=>null,
    'zielsetzung'=>'',
    'zielsetzung_geaendertam'=>null,
    'zeitplan'=>'',
    'zeitplan_geaendertam'=>null,
    'daten_geaendertam'=>null,
    'hinweise'=>'',
    'hinweise_geaendertam'=>null,
    'hinweise_geaendertvon'=>'',
  );
} else if(!empty($antrag->betreuerid)) {
  foreach($kurs->dozenten as $doz) {
    if($doz->id==$antrag->betreuerid) {
      $betreuer=$doz;
      break;
    }
  }
  if(empty($betreuer)) {
    $result=$db->query("select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent where id=".$antrag->betreuerid." limit 1");
    $betreuer=$result->fetch_object();
    $result->free();
  }
}

function makeDatenTrs($spalte,$ueberschrift) {
  global $antrag;
  $sp=$spalte.'_geaendertam';
?>
  <tr>
    <th align="left"><?= $ueberschrift ?></th>
  </tr>
  <tr>
    <td id="<?= $spalte ?>_daten" <?= !empty($antrag->$sp) && (empty($antrag->hinweise_geaendertam) || $antrag->hinweise_geaendertam<$antrag->$sp) ? 'class="geaendert"' : '' ?>><?= empty($antrag->$spalte) ? '&nbsp;' : nl2br($antrag->$spalte) ?></td>
  </tr>
<?php
}

require_once 'DozentSeite.php';
$seite=new DozentSeite('IHK-Projektantrag '.$tn->vorname.' '.$tn->nachname.' - '.$kurs->titel);
$seite->anfangGenerieren();
?>
<script>
async function ampel_wechseln() {
  let div=document.getElementById('ampel');
  let farbe=div.style.backgroundColor;
  farbe=farbe=='red' ? 'yellow' : farbe=='yellow' ? 'green' : 'red';
  let response=await fetch('ihkprojektantrag_ampel_speichern.php?kursid=<?= $kurs->id ?>&tnid=<?= $tn->id ?>&ampel='+farbe);
  if (!response.ok) {
    alert('Konnte Ampel nicht speichern :-(');
    return;
  }
  let txt=await response.text();
  if(txt!='OK') {
    alert(txt);
    return;
  }
  div.style.backgroundColor=farbe;
}
async function hinweise_speichern() {
  let area=document.getElementById('hinweise');
  let form=new FormData();
  form.set('kursid',<?= $kurs->id ?>);
  form.set('tnid',<?= $tn->id ?>);
  form.set('hinweise',area.value);
  let response=await fetch('ihkprojektantrag_hinweise_speichern.php',{
    'method':'POST',
    'body':form
  });
  if (!response.ok) {
    alert('Konnte Hinweise nicht speichern :-(');
    return;
  }
  let txt=await response.text();
  if(txt!='OK') {
    alert(txt);
    return;
  }
  document.getElementById('hinweise_gespeichert').style.display='';
}
</script>
<style>
#antrag th {
  padding-top:1em;
  font-size:1.25em;
}
.geaendert {
  font-weight:bold;
}
</style>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
<?php
DozentKurs::makeHeaderTr(false);
$kurs->makeTr(false);
?>
</table>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
    $tn->makeNameTrs();
?>
  <tr>
    <th>Beruf</th>
<?php
    $tn->makeBerufTd();
?>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
<?php
    $tn->makeMoodleTd(false);
?>
  </tr>
  <tr>
    <th>Betreuung</th>
    <td><?= empty($betreuer) ? '' : $betreuer->dozentenname ?></td>
  </tr>
  <tr>
    <th>Stand</th>
    <td style="cursor:pointer;" onclick="ampel_wechseln()"><div id="ampel" style="display:inline-block;width:1em;height:1em;border-radius:1em;background-color:<?= $antrag->ampel ?>">&nbsp;</div></td>
  </tr>
</table>
<table border="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;max-width=800px;" id="antrag">
  <tr>
    <th align="left">Hinweise</th>
  </tr>
  <tr>
    <td>
      <textarea id="hinweise" style="width:600px;height:100px;" onkeyup="document.getElementById('hinweise_gespeichert').style.display='none'"><?= $antrag->hinweise ?></textarea><br />
      <button type="button" onclick="hinweise_speichern()">Speichern</button> <span id="hinweise_gespeichert" style="color:green;display:none;">gespeichert!</span>
    </td>
  </tr>
<?php
makeDatenTrs('bezeichnung','1. Projektbezeichnung');
makeDatenTrs('beschreibung','2. Kurzform der Aufgabenstellung');
makeDatenTrs('zielsetzung','3. Zielsetzung entwickeln');
makeDatenTrs('zeitplan','4. Zeitplan');
?>
</table>
<?php
$seite->endeGenerieren();
?>