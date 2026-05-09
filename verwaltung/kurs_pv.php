<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungKurs.php';
require_once 'VerwaltungPVTermin.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'VerwaltungKurs');

if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}
if(!$kurs->istPV) {
  header('Location:kurs_sehen.php?kursid='.$kursid);
  exit;
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

$pvklassenById=array();
$result=$db->query("select * from gpb_pruefungsvorbereitung_klasse where kursid=".$kurs->id);
while($row=$result->fetch_object()) {
  $pvklassenById[$row->klasseid]=$row;
}
$result->free();
foreach($kurs->klassen as $k) {
  if(isset($pvklassenById[$k->id])) {
    $k->pvklasse=$pvklassenById[$k->id];
  } else {
    $k->pvklasse=(object)array('kursid'=>$kurs->id,'klasseid'=>$k->id,'hatAP1'=>1,'hatAP2'=>1,'hatMuendliche'=>1);
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

$termine=new Liste('VerwaltungPVTermin',$db->prepare("select * from gpb_pruefungsvorbereitung_termin where kursid=".$kurs->id." order by beginn,beginn_uhrzeit,ende,ende_uhrzeit,was"));

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('PV Einstellungen - '.$kurs->titel);
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
  const o=select.options[idx];
  if(klassenids.indexOf(o.value)>=0) return;
  o.remove();
  let tr=document.getElementById('pvklassen').insertRow();
  tr.id='pvklasse_'+o.value;
  tr.setAttribute('klasseid',o.value);
  tr.setAttribute('anzahlTN',o.getAttribute('anzahlTN'));
  tr.setAttribute('anzahlAnmeldungen',o.getAttribute('anzahlAnmeldungen'));
  let td=tr.insertCell();
  td.ondblcklick=()=>klasse_entfernen(o.value);
  td.innerText=o.innerText;
  tr.insertCell().innerHTML='<input type="checkbox" name="hatAP1_'+o.value+'" value="J" checked /> AP1';
  tr.insertCell().innerHTML='<input type="checkbox" name="hatAP2_'+o.value+'" value="J" checked /> AP2/schriftliche';
  tr.insertCell().innerHTML='<input type="checkbox" name="hatMuendliche_'+o.value+'" value="J" checked /> mündliche/Projekt/Fachgespräch';
  let btn=document.createElement('button');
  btn.type='button';
  btn.innerText='entfernen';
  btn.onclick=()=>klasse_entfernen(o.value);
  tr.insertCell().appendChild(btn);
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
function klasse_entfernen(id) {
  let tr=document.getElementById('pvklasse_'+id);
  tr.remove();
  let o=document.createElement('option');
  o.value=id;
  o.setAttribute('anzahlTN',tr.getAttribute('anzahlTN'));
  o.setAttribute('anzahlAnmeldungen',tr.getAttribute('anzahlAnmeldungen'));
  o.innerText=tr.cells[0].innerText;
  document.getElementById('gefundene_klassen').appendChild(o);
  klassenids.splice(klassenids.indexOf(o.value),1);
  document.getElementById('klassenids').value=klassenids.join(',');
  anzahlTN-=parseInt(o.getAttribute('anzahlTN'));
  anzahlAnmeldungen-=parseInt(o.getAttribute('anzahlAnmeldungen'));
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
  let table=document.getElementById('pvklassen');
  for(let i=table.rows.length-1;i>=0;--i) {
    let r=table.rows[i];
    if(beschaeftigteklassen[r.getAttribute('klasseid')]) {
      r.cells[0].classList.add('beschaeftigt');
    } else {
      r.cells[0].classList.remove('beschaeftigt');
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

var pvterminInBearbeitung=null;
function pvtermin_bearbeiten(id) {
  pvtermin_bearbeiten_abbrechen();
  pvterminInBearbeitung=id;
  document.getElementById('pvtermin_bearbeiten_'+id+'_tr').style.display='';
  document.getElementById('pvtermin_'+id+'_tr').style.display='none';
}
function pvtermin_bearbeiten_abbrechen() {
  if(pvterminInBearbeitung) {
    document.getElementById('pvtermin_bearbeiten_'+pvterminInBearbeitung+'_tr').style.display='none';
    document.getElementById('pvtermin_'+pvterminInBearbeitung+'_tr').style.display='';
    pvterminInBearbeitung=null;
  }
}

document.body.onload=zeitraum_geaendert;
</script>
<style>
.beschaeftigt {
  color:grey;
}
</style>
<form action="kurs_pv_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="id" value="<?= $kurs->id ?>" />
  <input type="hidden" name="notenstatus_alt" value="<?= $kurs->notenstatus ?>" />
  <input type="hidden" name="einzeltage" value="<?= $kurs->einzeltage ? 'J' : 'N' ?>" />
  <input type="hidden" name="warPV" value="<?= $kurs->istPV ? 'J' : 'N' ?>" />
  <input type="hidden" name="istPV" value="<?= $kurs->istPV ? 'J' : 'N' ?>" />
  <input type="hidden" name="modulid" id="modulid" value="<?= $kurs->modulid ?>" />
  <input type="hidden" name="zeugnisrelevant" value="<?= $kurs->zeugnisrelevant ? 'J' : 'N' ?>" />
  <input type="hidden" name="keinenoten" value="<?= $kurs->notenstatus=='keine' || (isset($kurs->keinenoten) && $kurs->keinenoten!='N') ? 'J' : 'N' ?>" />
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
    <th>Klassen</th>
    <td valign="bottom">
      <input type="hidden" name="klassenids" id="klassenids" value="<?= implode(',',$kurs->klassenids) ?>" />
      <div style="display:inline-block;min-height:calc(202px + 1em);">
        <table id="pvklassen" border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
foreach($kurs->klassen as $k) {
?>
          <tr id="pvklasse_<?= $k->id ?>" klasseid="<?= $k->id ?>" anzahlTN="<?= $k->anzahlTN ?>" anzahlAnmeldungen="<?= $k->anzahlAnmeldungen ?>">
            <td ondblclick="klasse_entfernen(<?= $k->id ?>)"><?= $k->bezeichnung ?> (<?= $k->anzahlTN ?>/<?= $k->anzahlAnmeldungen ?> TN)</td>
            <td><input type="checkbox" name="hatAP1_<?= $k->id ?>" value="J" <?= $k->pvklasse->hatAP1 ? 'checked' : '' ?> /> AP1</td>
            <td><input type="checkbox" name="hatAP2_<?= $k->id ?>" value="J" <?= $k->pvklasse->hatAP2 ? 'checked' : '' ?> /> AP2/schriftliche</td>
            <td><input type="checkbox" name="hatMuendliche_<?= $k->id ?>" value="J" <?= $k->pvklasse->hatMuendliche ? 'checked' : '' ?> /> mündliche/Projekt/Fachgespräch</td>
            <td><button type="button" onclick="klasse_entfernen(<?= $k->id ?>)">entfernen</button></td>
          </tr>
<?php
}
?>
        </table>
      </div>
      <div style="display:inline-block;" class="suche">
        <input type="text" id="klassen_suche" value="" style="width:200px;" onchange="klassen_finden()" onkeydown="klassen_suche_onkey(event)" /><input type="button" value="&#x1F50D;" class="suchbutton" /><br />
        <select id="gefundene_klassen" multiple ondblclick="klasse_hinzufuegen()" style="width:220px;height:200px;"></select><br />
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
        <input type="text" id="dozenten_suche" value="" style="width:200px;" onchange="dozenten_finden()" onkeydown="dozenten_suche_onkey(event)" /><input type="button" value="&#x1F50D;" class="suchbutton" /><br />
        <select id="gefundene_dozenten" multiple ondblclick="dozent_hinzufuegen()" style="width:220px;"></select><br />
        <button type="button" onclick="dozenten_hinzufuegen()">Markierte hinzufügen</button>
      </div>
    </td>
    <td class="fehler"><?= isset($fehler['dozenten']) ? $fehler['dozenten'] : '' ?></td>
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
      <input type="text" name="titel" id="titel" value="<?= htmlentities($kurs->titel,ENT_COMPAT) ?>" style="width:400px;" />
    </td>
    <td class="fehler"><?= isset($fehler['titel']) ? $fehler['titel'] : '' ?></td>
  </tr>
  <tr>
    <th>IHK Projektantrag</th>
    <td>
<?php
if($kurs->hatProjektantrag) {
?>
      <a href="ihkprojektantrag.php?kursid=<?= $kurs->id ?>" style="float:right;">Zuweisung der Betreuer</a>
<?php
}
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
      <input type="submit" value="Speichern" />
      Abbrechen:
      <a href="kurs_sehen.php?kursid=<?= $kurs->id ?>">Kurs sehen</a>
      <a href="kurs_bearbeiten.php?kursid=<?= $kurs->id ?>">Kurs bearbeiten</a>
<?php
if($kurs->hatProjektantrag) {
?>
      <a href="ihkprojektantrag.php?kursid=<?= $kurs->id ?>">IHK-Projektantrag</a>
<?php
}
?>
      <a href="kurse.php">Zurück zur Kurs-Suche</a>
    </td>
    <td class="fehler"><?= isset($fehler['speichern']) ? $fehler['speichern'] : '' ?></td>
  </tr>
</table>
</form>
<h2>Termine</h2>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
<?php
VerwaltungPVTermin::makeHeaderTr();
foreach($termine->alle as $termin) {
  $termin->makeTr();
}
?>
  <form action="kurs_pvtermin_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="kursid" value="<?= $kurs->id ?>" />
    <input type="hidden" name="id" value="0" />
  <tr>
    <td>
      Von <input type="date" name="beginn" value="" /> <input type="time" name="beginn_uhrzeit" value="" /><br />
      Bis <input type="date" name="ende" value="" /> <input type="time" name="ende_uhrzeit" value="" />
    </td>
    <td><input type="text" name="was" value="" style="width:250px;" /></td>
    <td align="center"><input type="checkbox" name="betrifftAP1" value="J" />&nbsp;AP1</td>
    <td align="center"><input type="checkbox" name="betrifftAP2" value="J" />&nbsp;AP2/schriftliche</td>
    <td align="center"><input type="checkbox" name="betrifftMuendliche" value="J" />&nbsp;mündliche/Projekt/Fachgespräch</td>
    <td><input type="submit" value="Termin hinzufügen" /></td>
  </tr>
  </form>
</table>
<a href="kurse.php">Zurück zur Kurs-Suche</a>
<?php
$seite->endeGenerieren();
?>