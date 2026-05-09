<?php
require_once 'check_login.php';
require_once 'VerwaltungKurs.php';

if(isset($_GET['kursid']) && $_GET['kursid']!='0') {
  $kurs=Kurs::einenLaden((int)$_GET['kursid'],'VerwaltungKurs');
  if(empty($kurs)) {
    header('Location:kurse.php');
    exit;
  }
} else {
  $titel=isset($_GET['titel']) ? $_GET['titel'] : '';
  $beginn=isset($_GET['beginn']) ? $_GET['beginn'] : '';
  $ende=isset($_GET['ende']) ? $_GET['ende'] : '';
  $einzeltage=!empty($beginn) && !empty($ende) && strtotime($ende)-strtotime($beginn)<2*24*60*60;
  $istPV=isset($_GET['istPV']) && $_GET['istPV']!='N';
  $hatProjektantrag=isset($_GET['hatProjektantrag']) && $_GET['hatProjektantrag']!='N';
  $ort=isset($_GET['ort']) ? $_GET['ort'] : $ich->ort;
  $raumid=isset($_GET['raumid']) ? (int)$_GET['raumid'] : 0;
  $klassenids=isset($_GET['klassenids']) ? $_GET['klassenids'] : array();
  $klassen=array();
  if(!empty($klassenids)) {
    $result=$db->query("select k.* from gpb_klasse_view k where k.id in(".addslashes($klassenids).") order by k.bezeichnung");
    $klassenids=array();
    while($row=$result->fetch_object()) {
      $klassen[]=$row;
      $klassenids[]=$row->id;
    }
    $result->free();
  }
  $dozentenids=isset($_GET['dozentenids']) ? $_GET['dozentenids'] : array();
  $dozenten=array();
  if(!empty($dozentenids)) {
    $result=$db->query("select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent where id in(".addslashes($dozentenids).")");
    $dozentenids=array();
    while($row=$result->fetch_object()) {
      $dozenten[]=$row;
      $dozentenids[]=$row->id;
    }
    $result->free();
  }
  $moodleid=isset($_GET['moodleid']) ? (int)$_GET['moodleid'] : 0;
  $modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;
  if($modulid>0) {
    $result=$db->query("select * from gpb_modul where id=".$modulid);
    $modul=$result->fetch_object();
    $result->free();
  }
  $kurs=(object)array(
    'id'=>0,
    'titel'=>$titel,
    'beginn'=>$beginn,
    'ende'=>$ende,
    'einzeltage'=>$einzeltage,
    'raumid'=>$raumid,
    'ort'=>$ort,
    'raum'=>'',
    'klassen'=>$klassen,
    'klassenids'=>$klassenids,
    'anzahlTN'=>0,
    'anzahlAnmeldungen'=>0,
    'dozenten'=>$dozenten,
    'dozentenids'=>$dozentenids,
    'moodleid'=>$moodleid,
    'sichtbar'=>true,
    'modulid'=>$modulid,
    'modulkuerzel'=>(isset($modul) ? $modul->kuerzel : ''),
    'modultitel'=>(isset($modul) ? $modul->titel : ''),
    'moduldauer'=>(isset($modul) ? $modul->dauer : ''),
    'zeugnisrelevant'=>true,
    'zeugnisgewichtung'=>-1,
    'notenstatus'=>'',
    'planungnotiz'=>'',
    'planungfarbe'=>'',
    'istPV'=>$istPV,
    'hatProjektantrag'=>$hatProjektantrag,
    'hatProjektantragDaten'=>false
  );
}
if(isset($_SESSION['fehler']['kurs'])) {
  $fehler=$_SESSION['fehler'];
  unset($_SESSION['fehler']);
  foreach($fehler['kurs'] as $k=>$v) {
    $kurs->$k=$v;
  }
  $kurs->klassen=array();
  $kurs->dozenten=array();
  if(!empty($kurs->klassenids)) {
    $result=$db->query("select k.* from gpb_klasse_view k where k.id in(".addslashes(implode(',',$kurs->klassenids)).") order by k.bezeichnung");
    $kurs->klassenids=array();
    while($row=$result->fetch_object()) {
      $kurs->klassen[]=$row;
      $kurs->klassenids[]=$row->id;
    }
    $result->free();
  }
  if(!empty($kurs->dozentenids)) {
    $result=$db->query("select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent where id in(".addslashes(implode(',',$kurs->dozentenids)).")");
    $kurs->dozentenids=array();
    while($row=$result->fetch_object()) {
      $kurs->dozenten[]=$row;
      $kurs->dozentenids[]=$row->id;
    }
    $result->free();
  }
}

$raeume=array('-'=>array(),'Mitte'=>array(),'Neukölln'=>array());
$result=$db->query("select * from gpb_raum order by ort,tuer");
while($row=$result->fetch_object()) {
  if($row->id==$kurs->raumid) {
    $kurs->ort=$row->ort;
    $kurs->raum=$row->tuer;
  }
  if(isset($raeume[$row->ort])) {
    $raeume[$row->ort][]=$row;
  } else {
    $raeume[$row->ort]=array($row);
  }
}
$result->free();

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Kurs '.$kurs->titel.' bearbeiten');
$seite->anfangGenerieren();
?>
<script>
var raeume=<?= json_encode($raeume) ?>;
function ort_ausgewaehlt() {
  let ort=document.getElementById('ort').value;
  let sel=document.getElementById('raumid');
  for(let i=sel.options.length-1;i>=0;--i) {
    sel.options[i].remove();
  }
  for(let r of raeume[ort]) {
    let o=document.createElement('option');
    o.value=r.id;
    o.innerText=r.tuer+(r.anzahlPlaetze<0 ? '' : '('+r.anzahlPlaetze+' Plätze)');
    sel.appendChild(o);
  }
}

var beschaeftigteklassen={};
function zeitraum_geaendert() {
  let beginn=document.getElementById('beginn').value;
  let ende=document.getElementById('ende').value;
  if(beginn && ende) {  
    const req=new XMLHttpRequest();
    req.onreadystatechange = function() {
      if (req.readyState != 4) return;
      if (req.responseText.startsWith('OK')) {
        beschaeftigteklassen=JSON.parse(req.responseText.substring(2));
      } else {
        alert(req.responseText);
      }
    };
    let url='klassen_beschaeftigte_finden.php?kursid=<?= $kurs->id ?>&beginn='+encodeURIComponent(beginn)+'&ende='+encodeURIComponent(ende);
    req.open('GET',url);
    req.send();
  } else {
    beschaeftigteklassen={};
  }
  check_klassen();
}

function klassen_suche_onkey(ev) {
  if(ev.key=='Enter') {
    ev.preventDefault();
    ev.stopPropagation();
    klassen_finden();
  }
}
function klassen_finden() {
  let bez=document.getElementById('klassen_suche').value.trim();
  if(!bez) {
    return;
  }
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText.startsWith('OK')) {
      let klass=JSON.parse(req.responseText.substring(2));
      let sel=document.getElementById('gefundene_klassen');
      for(let i=sel.childNodes.length-1;i>=0;--i) {
        sel.childNodes[i].remove();
      }
      for(let k of klass) {
        if(klassenids.indexOf(''+k.id)>=0) continue;
        let o=document.createElement('option');
        o.value=k.id;
        o.innerText=k.bezeichnung+' ('+k.anzahlTN+'/'+k.anzahlAnmeldungen+' TN)';
        o.setAttribute('anzahlTN',''+k.anzahlTN);
        o.setAttribute('anzahlAnmeldungen',''+k.anzahlAnmeldungen);
        if(beschaeftigteklassen[''+k.id]) {
          o.classList.add('beschaeftigt');
        }
        sel.appendChild(o);
      }
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','klassen_finden.php?bezeichnung='+encodeURIComponent(bez));
  req.send();
}
var klassenids=<?= json_encode($kurs->klassenids) ?>;
var anzahlTN=<?= (int)$kurs->anzahlTN ?>;
var anzahlAnmeldungen=<?= (int)$kurs->anzahlAnmeldungen ?>;
function klasse_hinzufuegen() {
  let select=document.getElementById('gefundene_klassen');
  let idx=select.selectedIndex;
  if(idx<0 || idx>=select.options.length) {
    return;
  }
  let o=select.options[idx];
  if(klassenids.indexOf(o.value)>=0) return;
  o.remove();
  document.getElementById('klassen').appendChild(o);
  klassenids.push(o.value);
  document.getElementById('klassenids').value=klassenids.join(',');
  anzahlTN+=parseInt(o.getAttribute('anzahlTN'));
  anzahlAnmeldungen+=parseInt(o.getAttribute('anzahlAnmeldungen'));
  document.getElementById('td_anzahlTN').innerText='Eingestiegen: '+anzahlTN+' / angemeldet: '+anzahlAnmeldungen;
  check_klassen();
}
function klassen_hinzufuegen() {
  let select=document.getElementById('gefundene_klassen');
  for(let idx=0;idx<select.options.length;++idx){
    let o=select.options[idx];
    if(!o.selected) continue;
    if(klassenids.indexOf(o.value)>=0) continue;
    o.remove();
    --idx;
    document.getElementById('klassen').appendChild(o);
    klassenids.push(o.value);
    document.getElementById('klassenids').value=klassenids.join(',');
    anzahlTN+=parseInt(o.getAttribute('anzahlTN'));
    anzahlAnmeldungen+=parseInt(o.getAttribute('anzahlAnmeldungen'));
  }
  document.getElementById('td_anzahlTN').innerText='Eingestiegen: '+anzahlTN+' / angemeldet: '+anzahlAnmeldungen;
  check_klassen();
}
function klasse_entfernen() {
  let select=document.getElementById('klassen');
  let idx=select.selectedIndex;
  if(idx<0 || idx>=select.options.length) {
    return;
  }
  let o=select.options[idx];
  o.remove();
  document.getElementById('gefundene_klassen').appendChild(o);
  klassenids.splice(klassenids.indexOf(o.value),1);
  document.getElementById('klassenids').value=klassenids.join(',');
  anzahlTN-=parseInt(o.getAttribute('anzahlTN'));
  anzahlAnmeldungen-=parseInt(o.getAttribute('anzahlAnmeldungen'));
  document.getElementById('td_anzahlTN').innerText='Eingestiegen: '+anzahlTN+' / angemeldet: '+anzahlAnmeldungen;
  check_klassen();
}
function klassen_entfernen() {
  let select=document.getElementById('klassen');
  for(let idx=0;idx<select.options.length;++idx) {
    let o=select.options[idx];
    if(!o.selected) continue;
    o.remove();
    --idx;
    document.getElementById('gefundene_klassen').appendChild(o);
    klassenids.splice(klassenids.indexOf(o.value),1);
    document.getElementById('klassenids').value=klassenids.join(',');
    anzahlTN-=parseInt(o.getAttribute('anzahlTN'));
    anzahlAnmeldungen-=parseInt(o.getAttribute('anzahlAnmeldungen'));
  }
  document.getElementById('td_anzahlTN').innerText='Eingestiegen: '+anzahlTN+' / angemeldet: '+anzahlAnmeldungen;
  check_klassen();
}
function check_klassen() {
  let fehler='';
  for(let kid of klassenids) {
    if(beschaeftigteklassen[kid]) {
      let besch=beschaeftigteklassen[kid];
      if(fehler.length>0) fehler+='<br />';
      fehler+='Klasse '+besch.bezeichnung+' schon im Kurs '+besch.titel;
    }
  }
  document.getElementById('fehler_klassen').innerHTML=fehler;
  let sel=document.getElementById('gefundene_klassen');
  for(let i=sel.options.length-1;i>=0;--i) {
    let o=sel.options[i];
    if(beschaeftigteklassen[o.value]) {
      o.classList.add('beschaeftigt');
    } else {
      o.classList.remove('beschaeftigt');
    }
  }
  sel=document.getElementById('klassen');
  for(let i=sel.options.length-1;i>=0;--i) {
    let o=sel.options[i];
    if(beschaeftigteklassen[o.value]) {
      o.classList.add('beschaeftigt');
    } else {
      o.classList.remove('beschaeftigt');
    }
  }
}

function dozenten_suche_onkey(ev) {
  if(ev.key=='Enter') {
    ev.preventDefault();
    ev.stopPropagation();
    dozenten_finden();
  }
}
function dozenten_finden() {
  let name=document.getElementById('dozenten_suche').value.trim();
  if(!name) {
    return;
  }
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText.startsWith('OK')) {
      let dozs=JSON.parse(req.responseText.substring(2));
      let sel=document.getElementById('gefundene_dozenten');
      for(let i=sel.childNodes.length-1;i>=0;--i) {
        sel.childNodes[i].remove();
      }
      for(let d of dozs) {
        let o=document.createElement('option');
        o.value=d.id;
        o.innerText=d.dozentenname;
        sel.appendChild(o);
      }
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','dozenten_finden.php?name='+encodeURIComponent(name));
  req.send();
}
var dozentenids=<?= json_encode($kurs->dozentenids) ?>;
function dozent_hinzufuegen() {
  let select=document.getElementById('gefundene_dozenten');
  let idx=select.selectedIndex;
  if(idx<0 || idx>=select.options.length) {
    return;
  }
  let o=select.options[idx];
  o.remove();
  document.getElementById('dozenten').appendChild(o);
  dozentenids.push(o.value);
  document.getElementById('dozentenids').value=dozentenids.join(',');
}
function dozenten_hinzufuegen() {
  let select=document.getElementById('gefundene_dozenten');
  for(let idx=0;idx<select.options.length;++idx) {
    let o=select.options[idx];
    if(!o.selected) continue;
    o.remove();
    --idx;
    document.getElementById('dozenten').appendChild(o);
    dozentenids.push(o.value);
    document.getElementById('dozentenids').value=dozentenids.join(',');
  }
}
function dozent_entfernen() {
  let select=document.getElementById('dozenten');
  let idx=select.selectedIndex;
  if(idx<0 || idx>=select.options.length) {
    return;
  }
  let o=select.options[idx];
  o.remove();
  document.getElementById('gefundene_dozenten').appendChild(o);
  dozentenids.splice(dozentenids.indexOf(o.value),1);
  document.getElementById('dozentenids').value=dozentenids.join(',');
}
function dozenten_entfernen() {
  let select=document.getElementById('dozenten');
  for(let idx=0;idx<select.options.length;++idx) {
    let o=select.options[idx];
    if(!o.selected) continue;
    o.remove();
    --idx;
    document.getElementById('gefundene_dozenten').appendChild(o);
    dozentenids.splice(dozentenids.indexOf(o.value),1);
    document.getElementById('dozentenids').value=dozentenids.join(',');
  }
}

function modulkuerzel_bearbeiten() {
  let m=document.getElementById('modulid').modul;
  if(!m) return;
  let neu=prompt('Neues Kürzel:',m.kuerzel);
  if(neu===null || neu==m.kuerzel) return;
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText.startsWith('OK')) {
      m.kuerzel=neu;
      document.getElementById('modultitel').innerText=m.kuerzel+' - '+m.titel;
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','modulkuerzel_speichern.php?modulid='+m.id+'&kuerzel='+encodeURIComponent(neu));
  req.send();
}
function module_suche_onkey(ev) {
  if(ev.key=='Enter') {
    ev.preventDefault();
    ev.stopPropagation();
    module_finden();
  }
}
function module_finden() {
  let titel=document.getElementById('module_suche').value.trim();
  if(!titel) {
    return;
  }
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText.startsWith('OK')) {
      let module=JSON.parse(req.responseText.substring(2));
      let sel=document.getElementById('gefundene_module');
      for(let i=sel.childNodes.length-1;i>0;--i) {
        sel.childNodes[i].remove();
      }
      for(let m of module) {
        let o=document.createElement('option');
        o.modul=m;
        o.value=m.id;
        o.innerText=(m.kuerzel || m.titel)+' ('+m.dauer+' Wo)';
        sel.appendChild(o);
      }
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','module_finden.php?titel='+encodeURIComponent(titel)+'&klassenids='+encodeURIComponent(klassenids.join(',')));
  req.send();
}
function modul_ausgewaehlt() {
  let select=document.getElementById('gefundene_module');
  let idx=select.selectedIndex;
  if(idx<0 || idx>=select.options.length) {
    return;
  }
  let o=select.options[idx];
  document.getElementById('modulid').value=o.value;
  if(o.modul) {
    document.getElementById('modultitel').innerText=o.modul.kuerzel+' - '+o.modul.titel;
    document.getElementById('modulid').modul=o.modul;
    document.getElementById('modulkuerzel_aendern_btn').style.display='';
  } else {
    document.getElementById('modultitel').innerText='(keines)';
    document.getElementById('modulid').modul=null;
    document.getElementById('modulkuerzel_aendern_btn').style.display='none';
  }
}
function titelGenerieren() {
  let t=document.getElementById('beginn').value;
  let selklas=document.getElementById('klassen');
  //TODO kürzen
  for(let i=0;i<selklas.options.length;++i) {
    let kt=selklas.options[i].innerText;
    let p=kt.lastIndexOf('(');
    t+=' '+kt.substring(0,p);
  }
  if(document.getElementById('modulid').value>0) {
    let mt=document.getElementById('modulid').modul.titel;
    t+=' '+mt;
  }
  document.getElementById('titel').value=t;
}
<?php
if($kurs->id>0) {
?>
function kurs_loeschen() {
  if(!confirm('Kurs löschen, sicher?\nNoten und Berichte werden gelöscht.\nAnwesenheiten werden nicht gelöscht.\nMoodle-Kurs wird nicht gelöscht.')) {
    return;
  }
  location.href='kurs_loeschen.php?kursid=<?= $kurs->id ?>';
}
<?php
}
?>
document.body.onload=zeitraum_geaendert;
</script>
<style>
.beschaeftigt {
  color:grey;
}
</style>
<form action="kurs_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="id" value="<?= $kurs->id ?>" />
  <input type="hidden" name="notenstatus_alt" value="<?= $kurs->notenstatus ?>" />
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="bearbeiten">
  <tr>
    <th>Beginn</th>
    <td><input type="date" name="beginn" id="beginn" value="<?= $kurs->beginn ?>" onchange="zeitraum_geaendert()" /></td>
    <td class="fehler"><?= isset($fehler['beginn']) ? $fehler['beginn'] : '' ?></td>
  </tr>
  <tr>
    <th>Ende</th>
    <td><input type="date" name="ende" id="ende" value="<?= $kurs->ende ?>" onchange="zeitraum_geaendert()" /></td>
    <td class="fehler"><?= isset($fehler['ende']) ? $fehler['ende'] : '' ?></td>
  </tr>
  <tr>
    <th>Einzeltag einer Reihe</th>
    <td><input type="checkbox" name="einzeltage" value="J" <?= $kurs->einzeltage ? 'checked' : '' ?> /></td>
    <td class="fehler"><?= isset($fehler['einzeltage']) ? $fehler['einzeltage'] : '' ?></td>
  </tr>
  <tr>
    <th>Klassen</th>
    <td valign="bottom">
      <input type="hidden" name="klassenids" id="klassenids" value="<?= implode(',',$kurs->klassenids) ?>" />
      <div style="display:inline-block;">
        <select id="klassen" multiple ondblclick="klasse_entfernen()" style="width:200px;height:200px;">
<?php
foreach($kurs->klassen as $k) {
?>
          <option value="<?= $k->id ?>" anzahlTN="<?= $k->anzahlTN ?>" anzahlAnmeldungen="<?= $k->anzahlAnmeldungen ?>"><?= $k->bezeichnung ?> (<?= $k->anzahlTN ?>/<?= $k->anzahlAnmeldungen ?> TN)</option>
<?php
}
?>
        </select><br />
        <button type="button" onclick="klassen_entfernen()">Markierte entfernen</button>
      </div>
      <div style="display:inline-block;" class="suche">
        <input type="text" id="klassen_suche" value="" style="width:230px;" onchange="klassen_finden()" onkeydown="klassen_suche_onkey(event)" /><input type="button" value="&#x1F50D;" class="suchbutton" /><br />
        <select id="gefundene_klassen" multiple ondblclick="klasse_hinzufuegen()" style="width:250px;height:200px;overflow-x:auto;"></select><br />
        <button type="button" onclick="klassen_hinzufuegen()">Markierte hinzufügen</button>
      </div>
    </td>
    <td id="fehler_klassen" class="fehler"><?= isset($fehler['klassen']) ? $fehler['klassen'] : '' ?></td>
  </tr>
  <tr>
    <th>Anzahl TN</th>
    <td id="td_anzahlTN">Eingestiegen: <?= $kurs->anzahlTN ?> / angemeldet: <?= $kurs->anzahlAnmeldungen ?></td>
    <td class="fehler"><?= isset($fehler['anzahlTN']) ? $fehler['anzahlTN'] : '' ?></td>
  </tr>
  <tr>
    <th>Dozenten</th>
    <td valign="bottom">
      <input type="hidden" name="dozentenids" id="dozentenids" value="<?= implode(',',$kurs->dozentenids) ?>" />
      <div style="display:inline-block;">
        <select id="dozenten" multiple ondblclick="dozent_entfernen()" style="width:200px;">
<?php
foreach($kurs->dozenten as $d) {
?>
          <option value="<?= $d->id ?>"><?= $d->dozentenname ?></option>
<?php
}
?>
        </select><br />
        <button type="button" onclick="dozenten_entfernen()">Markierte entfernen</button>
      </div>
      <div style="display:inline-block;" class="suche">
        <input type="text" id="dozenten_suche" value="" style="width:230px;" onchange="dozenten_finden()" onkeydown="dozenten_suche_onkey(event)" /><input type="button" value="&#x1F50D;" class="suchbutton" /><br />
        <select id="gefundene_dozenten" multiple ondblclick="dozent_hinzufuegen()" style="width:250px;overflow-x:auto;"></select><br />
        <button type="button" onclick="dozenten_hinzufuegen()">Markierte hinzufügen</button>
      </div>
    </td>
    <td class="fehler"><?= isset($fehler['dozenten']) ? $fehler['dozenten'] : '' ?></td>
  </tr>
  <tr>
    <th>
      Modul<br />
      <button type="button" id="modulkuerzel_aendern_btn" onclick="modulkuerzel_bearbeiten()" title="Modulkürzel ändern" <?= $kurs->modulid>0 ? '' : 'style="display:none;font-weight:normal;"' ?>>Kürzel ändern</button>
    </th>
    <td><div style="display:flex;justify-content:space-between;align-items:center;">
      <input type="hidden" name="modulid" id="modulid" value="<?= $kurs->modulid ?>" />
      <div id="modultitel" style="display:inline-block;width:200px;vertical-align:center;"><?= $kurs->modulid>0 ? $kurs->modulkuerzel.' - '.$kurs->modultitel.' ('.$kurs->moduldauer.' Wo)' : '(keines)' ?></div>
      <div style="display:inline-block;" class="suche">
        <input type="text" id="module_suche" value="" style="width:230px;" onchange="module_finden()" onkeydown="module_suche_onkey(event)" /><input type="button" value="&#x1F50D;" class="suchbutton" /><br />
        <select id="gefundene_module" multiple ondblclick="modul_ausgewaehlt()" style="width:250px;overflow-x:auto;"><option value="0">(keines)</option></select><br />
        <button type="button" onclick="modul_ausgewaehlt()">Markiertes übernehmen</button>
      </div>
    </div></td>
    <td class="fehler"><?= isset($fehler['modulid']) ? $fehler['modulid'] : '' ?></td>
  </tr>
  <tr>
    <th>Zeugnis-relevant</th>
    <td><input type="checkbox" name="zeugnisrelevant" value="J" <?= $kurs->zeugnisrelevant ? 'checked' : '' ?> /></td>
    <td class="fehler"><?= isset($fehler['zeugnisrelevant']) ? $fehler['zeugnisrelevant'] : '' ?></td>
  </tr>
  <tr>
    <th>Gewichtung im Zeugnis</th>
    <td><input type="number" min="0" step="1" name="zeugnisgewichtung" value="<?= $kurs->zeugnisgewichtung<0 ? '' : $kurs->zeugnisgewichtung ?>" style="width:50px;" />&nbsp;Wochen (leer = automatisch nach Kursdauer)</td>
    <td class="fehler"><?= isset($fehler['zeugnisgewichtung']) ? $fehler['zeugnisgewichtung'] : '' ?></td>
  </tr>
  <tr>
    <th>Noten</th>
    <td>
      <input type="checkbox" name="keinenoten" value="J" <?= $kurs->notenstatus=='keine' || (isset($kurs->keinenoten) && $kurs->keinenoten!='N') ? 'checked' : '' ?> />&nbsp;keine Noten &nbsp; &nbsp;
      Aktueller Stand: <?= $kurs->notenstatus=='keine' ? 'Keine Noten' : 
                          ($kurs->notenstatus=='ok' ? 'vollständig' :
                          ($kurs->notenstatus=='todo' ? 'unvollständig' : '(neuer Kurs)')) ?>
    </td>
  </tr>
  <tr>
    <th>Ort</th>
    <td><select name="ort" id="ort" onchange="ort_ausgewaehlt()">
<?php
foreach($raeume as $ort=>$rs) {
?>
      <option value="<?= htmlentities($ort,ENT_COMPAT) ?>" <?= $ort==$kurs->ort || (empty($kurs->ort) && $ort==$ich->ort) ? 'selected' : '' ?>><?= $ort ?></option>
<?php
}
?>
    </select></td>
    <td class="fehler"><?= isset($fehler['ort']) ? $fehler['ort'] : '' ?></td>
  </tr>
  <tr>
    <th>Raum</th>
    <td><select name="raumid" id="raumid">
      <option value="0">-</option>
<?php
foreach($raeume[empty($kurs->ort) ? $ich->ort : $kurs->ort] as $r) {
?>
      <option value="<?= $r->id ?>" <?= $r->id==$kurs->raumid ? 'selected' : '' ?>><?= $r->tuer ?> <?= $r->anzahlPlaetze<0 ? '' : '('.$r->anzahlPlaetze.' Plätze)' ?></option>
<?php
}
?>
    </select></td>
    <td class="fehler"><?= isset($fehler['raum']) ? $fehler['raum'] : '' ?></td>
  </tr>
  <tr>
    <th>Titel</th>
    <td>
      <button type="button" onclick="titelGenerieren()">Aus Beginn, Klassen und Modul generieren</button><br />
      <input type="text" name="titel" id="titel" value="<?= htmlentities($kurs->titel,ENT_COMPAT) ?>" style="width:400px;" />
    </td>
    <td class="fehler"><?= isset($fehler['titel']) ? $fehler['titel'] : '' ?></td>
  </tr>
  <tr>
    <th>Prüfungsvorbereitung</th>
    <td>
      <input type="hidden" name="warPV" value="<?= $kurs->istPV ? 'J' : 'N' ?>" />
<?php
if($kurs->istPV) {
?>
    <a href="kurs_pv.php?kursid=<?= $kurs->id ?>" style="float:right;">PV-Einstellungen bearbeiten</a>
<?php
}
?>
      <input type="checkbox" name="istPV" value="J" <?= $kurs->istPV ? 'checked' : '' ?> />
    </td>
    <td class="fehler"><?= isset($fehler['istPV']) ? $fehler['istPV'] : '' ?></td>
  </tr>
  <tr>
    <th>IHK Projektantrag</th>
    <td>
<?php
if($kurs->hatProjektantragDaten) {
?>
    <span style="float:right;">TN-Daten vorhanden!</span>
<?php
}
?>
      <input type="hidden" name="hatteProjektantrag" value="<?= $kurs->hatProjektantrag ? 'J' : 'N' ?>" />
      <input type="checkbox" name="hatProjektantrag" value="J" <?= $kurs->hatProjektantrag ? 'checked' : '' ?> />
    </td>
    <td class="fehler"><?= isset($fehler['hatProjektantrag']) ? $fehler['hatProjektantrag'] : '' ?></td>
  </tr>
  <tr <?= empty($kurs->planungfarbe) ? '' : 'style="background-color:'.htmlentities($kurs->planungfarbe,ENT_COMPAT).';"' ?>>
    <th>Planungsnotiz</th>
    <td>
      <datalist id="planungfarben">
        <option value="#ffffff"/>
        <option value="#ffff00"/>
        <option value="#ff0000"/>
        <option value="#ff8800"/>
        <option value="#00ff00"/>
      </datalist>
      <input type="color" name="planungfarbe" id="planungfarbe" list="planungfarben" value="<?= empty($kurs->planungfarbe) ? '#FFFFFF' : htmlentities($kurs->planungfarbe,ENT_COMPAT) ?>" />
      <input type="text" name="planungnotiz" id="planungnotiz" value="<?= htmlentities($kurs->planungnotiz,ENT_COMPAT) ?>" style="width:400px;" />
    </td>
    <td class="fehler"><?= isset($fehler['planungnotiz']) ? $fehler['planungnotiz'] : '' ?></td>
  </tr>
<?php
if($kurs->moodleid<=0) {
?>
  <tr>
    <th>Planungsstatus</th>
    <td><input type="checkbox" name="sichtbar" value="J" <?= $kurs->sichtbar ? 'checked' : '' ?> />für Dozenten und TN sichtbar</td>
    <td class="fehler"><?= isset($fehler['sichtbar']) ? $fehler['sichtbar'] : '' ?></td>
  </tr>
<?php
} else {
?>
  <input type="hidden" name="sichtbar" value="<?= $kurs->sichtbar ? 'J' : 'N' ?>" />
<?php
}
?>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">
<?php
if($kurs->moodleid>0) {
?>
      <a href="<?= $moodleurl ?>course/view.php?id=<?= $kurs->moodleid ?>" target="moodle">Moodle-ID=<?= $kurs->moodleid ?></a><br />
      Moodle-ID: <input type="text" name="moodleid" value="<?= htmlentities($kurs->moodleid,ENT_COMPAT) ?>" style="width:150px;" />
<?php
} else {
?>
      <input type="checkbox" name="moodleerstellen" value="J" />Moodle-Kurs erstellen<br />
      Mit Moodle-ID verbinden: <input type="text" name="moodleid" value="" style="width:150px;" /><br />
<?php
}
?>
    </td>
    <td class="fehler"><?= isset($fehler['moodleid']) ? $fehler['moodleid'] : '' ?></td>
  </tr>
  <tr>
    <th></th>
    <td>
<?php
if($kurs->id>0) {
?>
      <button type="button" onclick="kurs_loeschen()" style="float:right;">Kurs löschen<?= $kurs->moodleid>0 ? '<br />(bleibt im Moodle)' : '' ?></button>
<?php
}
?>
      <input type="submit" value="Speichern" />
      <a href="kurse.php">Abbrechen</a>
    </td>
    <td class="fehler"><?= isset($fehler['speichern']) ? $fehler['speichern'] : '' ?></td>
  </tr>
</table>
</form>
<?php
if($kurs->modulid) {
?>
<script>
document.getElementById('modulid').modul={'id':<?= $kurs->modulid ?>,'kuerzel':<?= json_encode($kurs->modulkuerzel) ?>,'titel':<?= json_encode($kurs->modultitel) ?>};
</script>
<?php
}
$seite->endeGenerieren();
?>