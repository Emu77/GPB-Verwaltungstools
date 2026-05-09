<?php
require_once 'check_login.php';
require_once 'TnKurs.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'TnKurs');
if(empty($kurs) || !$kurs->hatProjektantrag) {
  header('Location:kurse.php');
  exit;
}
$kurs->ladeVonMir();
if(!$kurs->vonMir || !$kurs->hatProjektantrag) {
  header('Location:kurs_sehen.php?kursid='.$kurs->id);
  exit;
}

$result=$db->query("select * from gpb_beruf where id=".$ich->berufid." limit 1");
$beruf=$result->fetch_object();
$result->free();

$result=$db->query("select *,greatest(ifnull(bezeichnung_geaendertam,''),ifnull(beschreibung_geaendertam,''),ifnull(zielsetzung_geaendertam,''),ifnull(zeitplan_geaendertam,'')) as daten_geaendertam from gpb_ihkprojektantrag where kursid=".$kurs->id." and tnid=".$ich->id." limit 1");
$antrag=$result->fetch_object();
$result->free();
if(empty($antrag)) {
  $antrag=(object)array(
    'kursid'=>$kurs->id,
    'tnid'=>$ich->id,
    'betreuerid'=>null,
    'ampel'=>'rot',
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
    'hinweise_geaendertvon'=>''
  );
}

if(!empty($antrag->betreuerid)) {
  $result=$db->query("select d.*,concat(d.vorname,' ',d.nachname) as dozentenname from gpb_dozent d where d.id=".$antrag->betreuerid." limit 1");
  $betreuer=$result->fetch_object();
  $result->free();
}

if(!empty($beruf) && $beruf->kuerzel=='FIAN') {
    $hilfe=array(
    'bezeichnung'=>"Nennen Sie hier den Titel / den Namen des Projektes.",
    'beschreibung'=>"Beschreiben Sie hier die zu programmierende Software aus Auftraggebersicht. Was soll der Nutzer mit Ihrer software tun können?",
    'zielsetzung'=>"Beschreiben Sie hier die zu programmierende Software aus Programmierersicht. Was müssen Sie machen, damit der Nutzer das bekommt, was im §2 steht?<br />Nennen Sie auch kurz Technologien, Tools, Ansprechpartner, Zeitziel und Kostenziel.",
    'zeitplan'=>"Detaillierter Zeitplan: 1 Zeile pro Arbeitspaket mit Zeitschätzung (max 5h außer Doku), gruppiert in Phasen, mit Zwischensummen und Gesamtsumme."
  );
} else {
  $hilfe=array(
    'bezeichnung'=>"Nennen Sie hier den Titel / den Namen des Projektes.",
    'beschreibung'=>"Beschreiben Sie hier bitte kurz, was die Inhalte des zu realisierenden Projektes sein sollen (max. zwei bis drei Sätze).",
    'zielsetzung'=>"Erklären Sie hier bitte genauer, was bisher vorhanden ist (Ist-Analyse), was erreicht werden soll (Soll-Konzept), und wo es evtl. Schwierigkeiten geben könnte. Mit wem müssen Sie evtl. zusammenarbeiten? Was wird der Nutzen des Projektes sein? Gehen Sie hierbei neben Sach- und Zeitzielen auch auf die Kosten- und Qualitätsziele ein.",
    'zeitplan'=>"Detaillierter Zeitplan: 1 Zeile pro Arbeitspaket mit Zeitschätzung (max 5h außer Doku), gruppiert in Phasen, mit Zwischensummen und Gesamtsumme."
  );
}

function makeDatenTrs($spalte,$ueberschrift) {
  global $antrag,$hilfe;
?>
  <tr>
    <th align="left"><?= $ueberschrift ?> &nbsp; <button type="button" onclick="hilfe('<?= $spalte ?>')">Hilfe</button></th>
  </tr>
  <tr id="<?= $spalte ?>_hilfe" style="display:none;">
    <td><?= $hilfe[$spalte] ?></td>
  </tr>
  <tr>
    <td>
<?php
  if($spalte=='bezeichnung') {
?>
      <input type="text" id="<?= $spalte ?>_daten" style="width:600px;" value="<?= htmlentities($antrag->$spalte,ENT_COMPAT) ?>" /><br />
<?php
  } else {
?>
      <textarea id="<?= $spalte ?>_daten" style="width:600px;height:200px;"><?= $antrag->$spalte ?></textarea><br />
<?php
  }
?>
      <button type="button" onclick="daten_speichern('<?= $spalte ?>')">Speichern</button> <span id="<?= $spalte ?>_gespeichert" style="color:green;display:none;">Gespeichert!</span>
    </td>
  </tr>
<?php
}

require_once 'TnSeite.php';
$seite=new TnSeite('Projektantrag '.$kurs->titel);
$seite->anfangGenerieren();
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
  <tr>
    <th>Kurs</th>
    <td><?= $kurs->titel ?></td>
<?php
$kurs->makeBearbeitenTd();
?>
  </tr>
  <tr>
    <th>Angemeldeter Beruf</th>
    <td><?= empty($beruf) ? '?' : $beruf->bezeichnung.' ('.$beruf->kuerzel.')' ?></td>
    <td></td>
  </tr>
  <tr>
    <th>Betreuung</th>
<?php
if(empty($betreuer)) {
?>
    <td></td>
<?php
} else {
?>
      <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"><a href="<?= $moodleurl ?>user/profile.php?id=<?= $betreuer->moodleid ?>" target="moodle"><?= $betreuer->dozentenname ?></a></td>
<?php
}
?>
    <td></td>
  </tr>
</table>

<script>
function hilfe(spalte) {
  let tr=document.getElementById(spalte+'_hilfe');
  tr.style.display=tr.style.display=='' ? 'none' : '';
}
var gespeichertSpan=null;
async function daten_speichern(spalte) {
  let area=document.getElementById(spalte+'_daten');
  let form=new FormData();
  form.set('kursid',<?= $kurs->id ?>);
  form.set('spalte',spalte);
  form.set('daten',area.value);
  let response=await fetch('ihkprojektantrag_daten_speichern.php',{
    'method':'POST',
    'body':form
  });
  if (!response.ok) {
    alert('Konnte Daten nicht speichern :-(');
    return;
  }
  let txt=await response.text();
  if(txt!='OK') {
    alert(txt);
    return;
  }
  if(gespeichertSpan) {
    gespeichertSpan.style.display='none';
  }
  gespeichertSpan=document.getElementById(spalte+'_gespeichert')
  gespeichertSpan.style.display='';
}
</script>
<style>
#antrag th {
  font-size:1.25em;
  padding-top:1em;
}
.geaendert,
.geaendert th,
.geaendert td {
  font-weight:bold;
}
.geaendert .hinweise_vonam {
  color:red;
}
</style>
<table border="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;" id="antrag">
<?php
  if(!empty($antrag->hinweise)) {
?>
  <tr align="left" id="hinweise" <?= !empty($antrag->hinweise_geaendertam) && (empty($antrag->daten_geaendertam) || $antrag->hinweise_geaendertam>$antrag->daten_geaendertam) ? 'class="geaendert"' : '' ?>>
    <th class="hinweise_vonam">Hinweise <?= empty($antrag->hinweise_geaendertvon) ? '' : 'von '.$antrag->hinweise_geaendertvon ?> <?= empty($antrag->hinweise_geaendertam) ? '' : ' am '.date('d.m.Y H:i',strtotime($antrag->hinweise_geaendertam)) ?></th>
  </tr>
  <tr>
    <td><?= nl2br($antrag->hinweise) ?></td>
  </tr>
<?php
}
?>
<?php
makeDatenTrs('bezeichnung','1. Projektbezeichnung');
makeDatenTrs('beschreibung','2. Kurzform der Aufgabenstellung');
makeDatenTrs('zielsetzung','3. Zielsetzung entwickeln');
makeDatenTrs('zeitplan','4. Zeitplan');
?>
</table>
<a href="kurs_sehen.php?kursid=<?= $kurs->id ?>">Zurück zum Kurs</a> <a href="kurse.php">Zurück zur Kurssuche</a>
<?php
$seite->endeGenerieren();
?>