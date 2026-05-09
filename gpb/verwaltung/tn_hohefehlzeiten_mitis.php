<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungTn.php';

$ort=isset($_GET['ort']) ? $_GET['ort'] : $ich->ort;

$zahlen=array();
$zahlenByTNId=array();
$result=$db->query("select distinct zahlen.*
  ,zahlen.anwesend/(zahlen.anwesend+zahlen.entschuldigt+zahlen.unentschuldigt) as anwquote
  ,case when anzahlnoten<=0 then null else zahlen.punktesumme/zahlen.anzahlnoten end as mittelwert
from (select tn.id as tnid
  ,(select count(*) from gpb_anwesenheit a where a.tnid=tn.id and a.mitis in('A','a','V','O')) as anwesend
  ,(select count(*) from gpb_anwesenheit a where a.tnid=tn.id and a.mitis in('F','f','K','E')) as entschuldigt
  ,(select count(*) from gpb_anwesenheit a where a.tnid=tn.id and a.mitis in('X','x','?')) as unentschuldigt
  ,(select count(*) from gpb_note n where n.tnid=tn.id and n.note is null and n.nachnote is null) as offeneklausuren
  ,(select sum(ifnull(nachnote,note)) from gpb_note n where n.tnid=tn.id and (n.note is not null or n.nachnote is not null)) as punktesumme
  ,(select count(*) from gpb_note n where n.tnid=tn.id and (n.note is not null or n.nachnote is not null)) as anzahlnoten
from gpb_tn tn
join gpb_massnahme_tn mtn on mtn.tnid=tn.id 
  and (mtn.einstieg is not null and mtn.einstieg<>'0000-00-00' and mtn.einstieg<=date_sub(current_date(),interval 1 month))
  and (mtn.ausstieg is null or mtn.ausstieg='0000-00-00' or mtn.ausstieg>current_date())"
.(empty($ort) || $ort=='-' ? "" : "  join gpb_klasse_tn ktn on ktn.tnid=tn.id
    join gpb_klasse k on k.id=ktn.klasseid and k.ort='".addslashes($ort)."'")
.") zahlen
where zahlen.anwesend+zahlen.entschuldigt+zahlen.unentschuldigt>0
  and zahlen.anwesend/(zahlen.anwesend+zahlen.entschuldigt+zahlen.unentschuldigt)<0.85
order by zahlen.anwesend/(zahlen.anwesend+zahlen.entschuldigt+zahlen.unentschuldigt)");
while($row=$result->fetch_object()) {
  $zahlen[]=$row;
  $zahlenByTNId[$row->tnid]=$row;
}
$result->free();

$tns=new Liste('VerwaltungTn',empty($zahlenByTNId) ? null : $db->prepare("select tn.*
  from gpb_tn_view tn
  where tn.id in(".implode(',',array_keys($zahlenByTNId)).")"));
VerwaltungTn::fehlzeitenNotizenLaden($tns);
VerwaltungTn::refsLaden($tns);

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('TN mit hohen Fehlzeiten nach MITIS-Daten');
$seite->anfangGenerieren();
?>
<div style="margin-bottom:1em;">
  <div>Es werden nur Teilnehmer angezeigt, die mindestens 1 Monat in einer Maßnahme sind, die nicht aus der Maßnahme ausgestiegen sind, und deren Anwesenheit unter 85% liegt.<br />
    Notenschätzungen ohne Gewichtung.<br />
    Die Bearbeitungsnotiz wird nicht automatisch zu MITIS übertragen.</div>
  Ort: <select id="ort_select" onchange="location.href='tn_hohefehlzeiten_mitis.php?ort='+encodeURIComponent(this.value);">
    <option value="">(alle)</option>
    <option value="Mitte" <?= $ort=='Mitte' ? 'selected' : '' ?>>Mitte</option>
    <option value="Neukölln" <?= $ort=='Neukölln' ? 'selected' : '' ?>>Neukölln</option>
  </select>
</div>
<script>
var inbearbeitung=null;
function notiz_bearbeiten_abbrechen() {
  if(inbearbeitung) {
    inbearbeitung.textarea.remove();
    inbearbeitung.speichernbutton.remove();
    inbearbeitung.abbrechenbutton.remove();
    inbearbeitung.span.style.display='';
    inbearbeitung.editbutton.style.display='';
    inbearbeitung=null;
  }
}
function notiz_bearbeiten(tnid) {
  notiz_bearbeiten_abbrechen();
  inbearbeitung={};
  inbearbeitung.tnid=tnid;
  inbearbeitung.editbutton=document.getElementById('notiz_editbutton_'+tnid);
  inbearbeitung.span=document.getElementById('notiz_span_'+tnid);
  inbearbeitung.textarea=document.createElement('textarea');
  inbearbeitung.textarea.value=inbearbeitung.span.innerText;
  inbearbeitung.textarea.style='display:block;width:200px;height:5em;';
  inbearbeitung.span.parentNode.insertBefore(inbearbeitung.textarea,inbearbeitung.span);
  inbearbeitung.speichernbutton=document.createElement('button');
  inbearbeitung.speichernbutton.type='button';
  inbearbeitung.speichernbutton.innerText='Speichern';
  inbearbeitung.speichernbutton.onclick=notiz_speichern;
  inbearbeitung.span.parentNode.insertBefore(inbearbeitung.speichernbutton,inbearbeitung.span);
  inbearbeitung.abbrechenbutton=document.createElement('button');
  inbearbeitung.abbrechenbutton.type='button';
  inbearbeitung.abbrechenbutton.innerText='Abbrechen';
  inbearbeitung.abbrechenbutton.onclick=notiz_bearbeiten_abbrechen;
  inbearbeitung.span.parentNode.insertBefore(inbearbeitung.abbrechenbutton,inbearbeitung.span);
  inbearbeitung.editbutton.style.display='none';
  inbearbeitung.span.style.display='none';
}
function notiz_speichern() {
  if(!inbearbeitung) return;
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText=='OK' || req.responseText=='"OK"') {
      inbearbeitung.span.innerText=inbearbeitung.textarea.value;
      notiz_bearbeiten_abbrechen();
    } else {
      alert(req.responseText);
    }
  };
  let url='tn_hohefehlzeiten_notiz_speichern.php?tnid='+inbearbeitung.tnid+'&notiz='+encodeURIComponent(inbearbeitung.textarea.value);
  req.open('GET',url);
  req.send();
}
</script>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>MITIS-ID</th>
    <th>Anrede</th>
    <th>Vorname</th>
    <th>Nachname</th>
    <th>Beruf</th>
<?php
if($ich->massnahmenanzeigen) {
?>
    <th>Maßnahmen</th>
<?php
}
?>
    <th>Klassen</th>
    <th>Anw. Quote</th>
    <th>Anwesend</th>
    <th>Entschuldigt</th>
    <th>Unentschuldigt</th>
    <th>Offene<br />Klausuren</th>
    <th>Notenschätzung</th>
    <th>Bearbeitungsnotiz</th>
    <th></th>
  </tr>
<?php
foreach($zahlen as $z) {
  $tn=$tns->byId[$z->tnid];
?>
  <tr>
<?php
  $tn->makeMitisTd();
  $tn->makeNameTds();
  $tn->makeBerufTd();
  if($ich->massnahmenanzeigen) $tn->makeMassnahmenTd();
  $tn->makeKlassenTd();
?>
    <td align="center"><?= round(100*$z->anwquote) ?> %</td>
    <td><?= $z->anwesend ?></td>
    <td><?= $z->entschuldigt ?></td>
    <td><?= $z->unentschuldigt ?></td>
    <td><?= $z->offeneklausuren ?></td>
    <td><?= empty($z->mittelwert) ? '' : round($z->mittelwert) ?></td>
    <td><a class="editbutton" id="notiz_editbutton_<?= $tn->id ?>" href="javascript:notiz_bearbeiten(<?= $tn->id ?>)">✎</a> <span id="notiz_span_<?= $tn->id ?>"><?= isset($tn->notiz) ? nl2br($tn->notiz) : '' ?></span></td>
<?php
  $tn->makeBearbeitenTd();
?>
  </tr>
<?php
}
?>
</table>
<?php
$seite->endeGenerieren();
?>