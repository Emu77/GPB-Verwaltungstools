<?php
require_once 'check_login.php';
require_once '../verwaltung/VerwaltungDozent.php';

$dozent=Dozent::einenLaden(isset($_GET['dozentid']) ? (int)$_GET['dozentid'] : 0,'VerwaltungDozent');
if(empty($dozent)) {
  header('Location:../verwaltung/dozenten.php');
  exit;
}

if(!$ich->istleiter) {
  header('Location:../verwaltung/dozent_sehen.php?dozentid='.$dozent->id);
  exit;
}

$module=array();
$moduleById=array();
$result=$db->query("select m.*
    ,dm.planungnotiz
    ,(select count(*) from gpb_kurs where modulid=m.id and id in(select kursid from gpb_kurs_dozent where dozentid=".$dozent->id.")) as anzahlKurse
    ,(select sum(wert) from gpb_bewertung where frage<>'info' and kursid in(select kursid from gpb_kurs_dozent where dozentid=".$dozent->id.") and kursid in(select id from gpb_kurs where modulid=m.id)) as summeBewertungen
    ,(select count(*) from gpb_bewertung where frage<>'info' and kursid in(select kursid from gpb_kurs_dozent where dozentid=".$dozent->id.") and kursid in(select id from gpb_kurs where modulid=m.id)) as anzahlBewertungen
    ,(select count(distinct tnid) from gpb_bewertung where frage<>'info' and kursid in(select kursid from gpb_kurs_dozent where dozentid=".$dozent->id.") and kursid in(select id from gpb_kurs where modulid=m.id)) as anzahlTN
  from gpb_modul m
  left outer join gpb_dozent_modul dm on dm.dozentid=".$dozent->id." and dm.modulid=m.id
  where m.id in(select modulid from gpb_dozent_modul where dozentid=".$dozent->id."
    union select modulid from gpb_kurs where id in(select kursid from gpb_kurs_dozent where dozentid=".$dozent->id."))");
while($row=$result->fetch_object()) {
  $module[]=$row;
  $moduleById[$row->id]=$row;
}
$result->free();
usort($module,function($m0,$m1) {
  if($m0->anzahlKurse>$m1->anzahlKurse) return -1;
  if($m0->anzahlKurse<$m1->anzahlKurse) return 1;
  if($m0->titel<$m1->titel) return -1;
  if($m0->titel>$m1->titel) return 1;
  return 0;
});

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Skills von Dozent '.$dozent->vorname.' '.$dozent->nachname);
$seite->anfangGenerieren();
$dozent->makeSehen();
?>
<style>
a.aendern {
  text-decoration:none;
  color:black;
}
a.aendern:before {
  content:'✎ ';
}
</style>
<script>
const module=<?= json_encode($moduleById,JSON_INVALID_UTF8_SUBSTITUTE) ?>;
function notiz_aendern(modulid) {
  let vn=module[modulid].planungnotiz || '';
  let n=prompt('Neue Notiz',vn);
  if(n!==null && n!=vn) {
    location.href='dozent_modul_speichern.php?dozentid=<?= $dozent->id ?>&modulid='+modulid+'&notiz='+encodeURIComponent(n);
  }
}
function suche_titel_keyup(ev) {
  if(ev.key=='Enter') {
    module_suchen();
  }
}
function module_suchen() {
  let titel=document.getElementById('suche_titel').value.trim();
  if(titel.length<=0) {
    document.getElementById('suche_titel').focus();
    return;
  }
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    let module=JSON.parse(req.responseText);
    let sel=document.getElementById('gefundene_module');
    for(let i=sel.childNodes.length-1;i>=0;--i) {
      sel.childNodes[i].remove();
    }
    for(let m of module) {
      let o=document.createElement('option');
      o.value=m.id;
      o.innerText=m.titel+' ('+m.kuerzel+')';
      sel.appendChild(o);
    }
  };
  req.open('GET','module_finden.php?titel='+encodeURIComponent(titel));
  req.send();
}
function modul_hinzufuegen() {
  let sel=document.getElementById('gefundene_module');
  if(sel.value) {
    location.href='dozent_modul_speichern.php?dozentid=<?= $dozent->id ?>&modulid='+sel.value;
  }
}
</script>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Modul</th>
    <th>Kürzel</th>
    <th>Anzahl Kurse</th>
    <th>Bewertung</th>
    <th>Notiz</th>
  </tr>
<?php
foreach($module as $m) {
?>
  <tr>
    <td><?= $m->titel ?></td>
    <td><?= $m->kuerzel ?></td>
    <td align="center"><?= $m->anzahlKurse ?></td>
    <td align="center"><?= $m->anzahlBewertungen ? (round(10*$m->summeBewertungen/$m->anzahlBewertungen)/10).' ('.$m->anzahlTN.' TN)' : '' ?></td>
    <td><a class="aendern" href="javascript:notiz_aendern(<?= $m->id ?>)"><?= $m->planungnotiz ?></a></td>
  </tr>
<?php
}
?>
</table>
<br />
<div id="div_module_finden">
  Kürzel oder Titel: <input type="text" id="suche_titel" value="" style="width:150px;" onchange="module_suchen()" onkeyup="suche_titel_keyup(event)" /> <button onclick="module_suchen()">Suchen</button><br />
  <select id="gefundene_module" multiple="true" style="min-width:250px;min-height:200px;" ondblclick="modul_hinzufuegen()"></select><br />
  <button onclick="modul_hinzufuegen()">Markiertes hinzufügen</button> <a href="javascript:module_finden(false)">Abbrechen</a>
</div>
<?php
$seite->endeGenerieren();
?>