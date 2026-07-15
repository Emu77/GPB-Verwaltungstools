<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once '../Kurs.php';
require_once '../Ferien.php';
require_once 'DozentVerfuegbarkeit.php';

$konfigid=isset($_SESSION['planungkonfigid']) && !empty($_SESSION['planungkonfigid']) ? $_SESSION['planungkonfigid'] : 0;
$konfig=null;
$planungkonfigs=array();
$result=$db->query("select * from gpb_planungkonfig order by bezeichnung");
while($row=$result->fetch_object()) {
  $planungkonfigs[]=$row;
  if($row->id==$konfigid) {
    $konfig=$row;
  }
}
$result->free();
if(!$konfig) {
  $konfig=(object)array('id'=>0,'bezeichnung'=>'Bitte Konfiguration auswählen oder erstellen');
}

$konfig->ids=(object)array('klasse'=>array(),'dozent'=>array(),'raum'=>array());
if($konfig->id>0) {
  $result=$db->query("select * from gpb_planungkonfigzeile where planungkonfigid=".$_SESSION['planungkonfigid']." order by was");
  while($row=$result->fetch_object()) {
    $was=$row->was;
    $konfig->ids->$was[]=$row->wasid;
  }
  $result->free();
}

$konfig->klassen=array();
if(!empty($konfig->ids->klasse)) {
  $result=$db->query("select * from gpb_klasse_view where id in(".implode(',',$konfig->ids->klasse).") order by bezeichnung");
  while($row=$result->fetch_object()) {
    $konfig->klassen[]=$row;
  }
  $result->free();
}
$dozentenById=array();
$konfig->dozenten=array();
if(!empty($konfig->ids->dozent)) {
  foreach($konfig->ids->dozent as $dozid) {
    $konfig->dozenten[$dozid]=true;
    $dozentenById[$dozid]=true;
  }
}

$konfig->raeume=array();
if(!empty($konfig->ids->raum)) {
  $result=$db->query("select * from gpb_raum where id in(".implode(',',$konfig->ids->raum).") order by ort,tuer");
  while($row=$result->fetch_object()) {
    $konfig->raeume[]=$row;
  }
  $result->free();
}

$von=isset($_SESSION['planungbeginn']) ? strtotime($_SESSION['planungbeginn']) : strtotime('last monday');
$bis=isset($_SESSION['planungende']) ? strtotime($_SESSION['planungende']) : strtotime('+3 months',$von);
$wochen=array();
$zeitraeume=array();
for($t=$von;$t<=$bis;$t=strtotime('+1 week',$t)) {
  $w=date('Y-W',$t);
  $freitag=strtotime('next friday',$t);
  $wochen[]=(object)array('kw'=>$w,'display'=>'KW '.substr($w,5),'montag'=>date('Y-m-d',$t),'freitag'=>date('Y-m-d',$freitag));
  $zeitraeume[$w]=date('d.m',$t).' - '.date('d.m.',$freitag);
}

$kurse=new Liste('Kurs',$db->prepare("select k.* from gpb_kurs_view k
  where k.beginn<='".date('Y-m-d',$bis)."' and k.ende>='".date('Y-m-d',$von)."'"
    ." and (false"
    .(empty($konfig->ids->klasse) ? '' : " or k.id in(select kk.kursid from gpb_kurs_klasse kk where kk.klasseid in(".implode(',',$konfig->ids->klasse)."))")
    .(empty($konfig->ids->dozent) ? '' : " or k.id in(select kd.kursid from gpb_kurs_dozent kd where kd.dozentid in(".implode(',',$konfig->ids->dozent)."))")
    .(empty($konfig->ids->raum) ? '' : " or k.raumid in(".implode(',',$konfig->ids->raum).")")
    .")"));
Kurs::klassenLaden($kurse);
if(!empty($kurse->byId)) {
  $result=$db->query("select * from gpb_kurs_dozent where kursid in(".implode(',',array_keys($kurse->byId)).")");
  while($row=$result->fetch_object()) {
    $kurse->byId[$row->kursid]->dozentenids[]=$row->dozentid;
    $dozentenById[$row->dozentid]=true;
  }
  $result->free();
}

if(!empty($dozentenById)) {
  $result=$db->query("select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent where id in(".implode(',',array_keys($dozentenById)).") order by nachname,vorname");
  while($row=$result->fetch_object()) {
    $row->verfuegbarkeiten=array();
    $row->vergleich=mb_strtolower($row->nachname.' '.$row->vorname);
    $dozentenById[$row->id]=$row;
    if(isset($konfig->dozenten[$row->id])) {
      $konfig->dozenten[$row->id]=$row;
    }
  }
  $result->free();
  
  $result=$db->query("select * from gpb_dozent_verfuegbarkeit where beginn<='".date('Y-m-d',$bis)."' and ende>='".date('Y-m-d',$von)."' and dozentid in(".implode(',',array_keys($dozentenById)).")");
  while($row=$result->fetch_object()) {
    $row->von=strtotime($row->beginn);
    $row->bis=strtotime($row->ende);
    $row->vondatum=date('d.m.Y',$row->von);
    $row->bisdatum=date('d.m.Y',$row->bis);
    $row->kw=array();
    for($t=$row->von;$t<=$row->bis;$t=strtotime('+1 week',$t)) {
      $row->kw[]=date('Y-W',$t);
    }
    $dozentenById[$row->dozentid]->verfuegbarkeiten[]=$row;
  }
  $result->free();
  
  function dozenten_vergleich($d0,$d1) {
    return strcmp($d0->vergleich,$d1->vergleich);
  }
  foreach($konfig->ids->dozent as $dozid) {
    $konfig->dozenten[]=$dozentenById[$dozid];
  }
  usort($konfig->dozenten,'dozenten_vergleich');
  foreach($kurse->alle as $kurs) {
    foreach($kurs->dozentenids as $dozid) {
      $kurs->dozenten[]=$dozentenById[$dozid];
    }
    usort($kurs->dozenten,'dozenten_vergleich');
  }
}

$ferien=new Liste('Ferien',$db->prepare("select f.* from gpb_ferien f
  where f.beginn<='".date('Y-m-d',$bis)."' and f.ende>='".date('Y-m-d',$von)."'"
    ." and art<>'Berliner Schulferien'"
    ." and (art<>'Klassenferien'"
    .(empty($konfig->ids->klasse) ? '' : " or f.id in(select kf.ferienid from gpb_klasse_ferien kf where kf.klasseid in(".implode(',',$konfig->ids->klasse)."))")
    .")"));
Ferien::refsLaden($ferien);

$kompakt=isset($_SESSION['kompakt']) ? $_SESSION['kompakt'] : true;

require_once 'LeitungSeite.php';
$seite=LeitungSeite::$menueByUrl['../leitung/planung.php'];
$seite->anfangGenerieren();
?>
<div id="topbereich">
<div id="konfigauswahl">
  <div class="tododev">Adhoc-Modul erstellen und/oder Modultitel ohne Modulid</div>
  <div class="tododev">Check Kurs ohne Klasse oder ohne Dozent oder ohne Raum?</div>
  <div class="tododev">Check Kurs ohne Klasse und ohne Dozent und ohne Raum</div>
  <br />
  <script>
  function konfig_ausgewaehlt() {
    location.href='planungkonfig_merken.php?konfigid='+document.getElementById('konfigselect').value;
  }
  function konfig_umbenennen() {
    let bez=prompt('Neue Bezeichnung:','<?= $konfig->id>0 ? str_replace("'","\\'",$konfig->bezeichnung) : '' ?>');
    if(!bez) return;
    location.href='planungkonfig_aendern.php?bezeichnung='+encodeURIComponent(bez);
  }
  function konfig_erstellen(kopie) {
    let bez=prompt('Bezeichnung der neuen Konfig:',kopie ? '<?= $konfig->id>0 ? str_replace("'","\\'",$konfig->bezeichnung) : '' ?>' : '');
    if(!bez) return;
    location.href='planungkonfig_erstellen.php?kopie='+(kopie ? 'J' : 'N')+'&bezeichnung='+encodeURIComponent(bez);
  }
<?php
if($konfig->id>0) {
?>
  function konfig_loeschen() {
    if(confirm('Konfig "<?= str_replace("'","\\'",$konfig->bezeichnung) ?>" löschen, sicher?')) {
      location.href='planungkonfig_loeschen.php';
    }
  }
<?php
}
?>
  function zeitraum_ausgewaehlt(info) {
    location.href='planung_zeitraum_merken.php?'+info+'='+document.getElementById(info).value;
  }
  </script>
  Konfig: <select id="konfigselect" onchange="konfig_ausgewaehlt()">
<?php
if($konfig->id<=0) {
?>
    <option value="<?= $konfig->id ?>"><?= $konfig->bezeichnung ?></option>
<?php
}
foreach($planungkonfigs as $k) {
?>
    <option value="<?= $k->id ?>" <?= $k->id==$konfig->id ? 'selected' : '' ?>><?= $k->bezeichnung ?></option>
<?php
}
?>
  </select>
  <button type="button" onclick="konfig_umbenennen()">Konfig umbenennen</button>
<?php
if($konfig->id>0) {
?>
  <button type="button" onclick="konfig_loeschen()">Konfig löschen</button>
<?php
}
?>
  <button type="button" onclick="konfig_erstellen(true)">Konfig kopieren</button>
  <button type="button" onclick="konfig_erstellen(false)">Neue Konfig</button><br />
  <br />
  Von&nbsp;<input type="date" id="beginn" value="<?= date('Y-m-d',$von) ?>" onchange="zeitraum_ausgewaehlt('beginn')" />
  bis&nbsp;<input type="date" id="ende" value="<?= date('Y-m-d',$bis) ?>" onchange="zeitraum_ausgewaehlt('ende')" />
  <input type="checkbox" id="cb_kompakt" />&nbsp;Kompakt
</div>
<table id="meldungen" border="1" cellspacing="0" style="border-collapse:collapse;"></table>
<div id="meldungenohnekw"></div>
</div>
<script>
function klassen_keyup(event) {
  if(event.key=='Enter') {
    klassen_suchen();
  } else if(event.key='Escape') {
    klassensuche_fertig();
  }
}
function formatDate(d) {
  if(!d || d.length<10) return '?';
  return d.substr(8,2)+'.'+d.substr(5,2)+'.'+d.substr(0,4);
}
function klassen_suchen() {
  let bez=document.getElementById('klassebez').value;
  if(!bez) return;
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText.startsWith('OK')) {
      let klass=JSON.parse(req.responseText.substring(2));
      let table=document.getElementById('gefundene_klassen');
      let fertigtr=table.rows[table.rows.length-1];
      for(let i=table.rows.length-1;i>=0;--i) {
        table.rows[i].remove();
      }
      for(let k of klass) {
        let tr=table.insertRow();
        let td=tr.insertCell();
        td.innerText=k.bezeichnung+' ('+k.anzahlAnmeldungen+' angemeldete, '+k.anzahlTN+' eingestiegene TN) '+formatDate(k.beginn)+' '+formatDate(k.ende);
        td.ondblclick=()=>klasse_hinzufuegen(k.id);
        td=tr.insertCell();
        let btn=document.createElement('button');
        btn.type='button';
        btn.innerText='Aufnehmen';
        btn.onclick=()=>klasse_hinzufuegen(k.id);
        td.appendChild(btn);
      }
      table.appendChild(fertigtr);
      table.style.display='';
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','planung_klassen_finden.php?bezeichnung='+encodeURIComponent(bez));
  req.send();
}
function klassensuche_fertig() {
  document.getElementById('gefundene_klassen').style.display='none';
}
function klasse_hinzufuegen(id) {
  location.href='planungkonfigzeile_erstellen.php?was=klasse&wasid='+id;
}

function dozenten_keyup(event) {
  if(event.key=='Enter') {
    dozenten_suchen();
  } else if(event.key=='Escape') {
    dozentensuche_fertig();
  }
}
function dozenten_suchen() {
  let name=document.getElementById('dozentname').value;
  if(!name) return;
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText.startsWith('OK')) {
      let dozs=JSON.parse(req.responseText.substring(2));
      let table=document.getElementById('gefundene_dozenten');
      let fertigtr=table.rows[table.rows.length-1];
      for(let i=table.rows.length-1;i>=0;--i) {
        table.rows[i].remove();
      }
      for(let d of dozs) {
        let tr=table.insertRow();
        let td=tr.insertCell();
        td.innerText=d.vorname+' '+d.nachname;
        td.ondblclick=()=>dozent_hinzufuegen(k.id);
        td=tr.insertCell();
        let btn=document.createElement('button');
        btn.type='button';
        btn.innerText='Aufnehmen';
        btn.onclick=()=>dozent_hinzufuegen(d.id);
        td.appendChild(btn);
      }
      table.appendChild(fertigtr);
      table.style.display='';
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','planung_dozenten_finden.php?name='+encodeURIComponent(name));
  req.send();
}
function dozentsuche_fertig() {
  document.getElementById('gefundene_dozenten').style.display='none';
}
function dozent_hinzufuegen(id) {
  location.href='planungkonfigzeile_erstellen.php?was=dozent&wasid='+id;
}

function raeume_keyup(event) {
  if(event.key=='Enter') {
    raeume_suchen();
  } else if(event.key=='Escape') {
    raeumesuche_fertig();
  }
}
function raeume_suchen() {
  let tuer=document.getElementById('raumtuer').value;
  if(!tuer) return;
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText.startsWith('OK')) {
      let raeume=JSON.parse(req.responseText.substring(2));
      let table=document.getElementById('gefundene_raeume');
      let fertigtr=table.rows[table.rows.length-1];
      for(let i=table.rows.length-1;i>=0;--i) {
        table.rows[i].remove();
      }
      for(let r of raeume) {
        let tr=table.insertRow();
        let td=tr.insertCell();
        td.innerText=r.tuer+' ('+r.anzahlPlaetze+' Plätze)';
        td.ondblclick=()=>raum_hinzufuegen(r.id);
        td=tr.insertCell();
        let btn=document.createElement('button');
        btn.type='button';
        btn.innerText='Aufnehmen';
        btn.onclick=()=>raum_hinzufuegen(r.id);
        td.appendChild(btn);
      }
      table.appendChild(fertigtr);
      table.style.display='';
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','planung_raeume_finden.php?tuer='+encodeURIComponent(tuer));
  req.send();
}
function raeumesuche_fertig() {
  document.getElementById('gefundene_raeume').style.display='none';
}
function raum_hinzufuegen(id) {
  location.href='planungkonfigzeile_erstellen.php?was=raum&wasid='+id;
}
</script>
<script type="module">
import { Planung } from './Planung.js';
import { PlanungDozentVerfuegbarkeit } from './PlanungDozentVerfuegbarkeit.js';
PlanungDozentVerfuegbarkeit.modelleByWert=<?= json_encode(DozentVerfuegbarkeit::$instanzenByWert) ?>;
Planung.init(<?= json_encode($wochen) ?>
  ,<?= json_encode($konfig->klassen) ?>
  ,<?= json_encode($konfig->dozenten) ?>
  ,<?= json_encode($konfig->raeume) ?>
  ,<?= json_encode($kurse->alle) ?>
  ,<?= json_encode($ferien->alle) ?>
  ,<?= json_encode($kompakt) ?>);
</script>
<style>
#topbereich {
  display:flex;
  flex-direction:row;
  gap:2ch;
  align-items:flex-end;
}
#konfigauswahl {
}
#meldungen {
  border-color:red;
  padding:4px;
  color:red;
}
#meldungenohnekw {
  padding:4px;
}
.meldung {
  color:red;
}
#planungtable {

}
#planungtable .btn {
  height:1em;
  cursor:pointer;
  display:inline-block;
}
.erstespalte {
  position:sticky;
  left:0px;
  background-color:white;
  padding:2px;
  border-right:1px solid black; /* funktioniert nicht :-( */
}
#planungtable td.nichtda {
  background-color:rgb(200,200,200);
}
.planungzeile.kompakt th {
  max-height:2em;
  overflow-y:hidden;
}
.planungzeile.kompakt th button {
  display:none;
}
.planungzeile th a {
  display:block;
}
.planungzeile td > div {
  display:flex;
  flex-direction:column;
  gap:5px;
}
.geplantes {
  padding:2px;
  display:flex;
  flex-direction:column;
}
.geplantes > div {
  display:flex;
  flex-direction:row;
}
.geplantes .klassen,
.geplantes .dozenten {
  display:flex;
  flex-direction:column;
}
.geplantes div {
  white-space:pre;
}
.geplantes div .btn {
  padding-right:1ch;
}
.geplantes > div.popupmenue {
  display:flex;
  flex-direction:column;
  background-color:white;
}
.popupmenue .menuitem {
  cursor:pointer;
}
.popupmenue .menuitem:hover {
  background-color:rgb(200,200,200);
}
.popupmenue button {
  display:block;
  width:100%;
  white-space:pre;
}
.ferien {
  color:green;
}
</style>
<datalist id="planungfarben">
  <option value="#ffffff"/>
  <option value="#ffff00"/>
  <option value="#ff0000"/>
  <option value="#ff8800"/>
  <option value="#00ff00"/>
</datalist>
<table id="planungtable" border="1" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin-top:1em;">
  <tr id="klassentr" style="border-top:2px solid black;">
    <th class="erstespalte">Klassen</th>
    <td colspan="<?= count($wochen) ?>">
      <input type="text" id="klassebez" style="width:150px;" onkeyup="klassen_keyup(event)" /><button type="button" onclick="klassen_suchen()">Suchen</button>
      <table id="gefundene_klassen" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse;display:none;">
        <tr><td colspan="2"><button type="button" onclick="klassensuche_fertig()">Fertig</button></td></tr>
      </table>
    </td>
  </tr>
  <tr id="klassenkwtr">
    <th class="erstespalte"></th>
<?php
foreach($wochen as $w) {
?>
    <th>
      <?= $w->display ?><br />
      <?= $zeitraeume[$w->kw] ?>
    </th>
<?php
}
?>
  </tr>
  <tr id="dozententr" style="border-top:2px solid black;">
    <th class="erstespalte">Dozenten</th>
    <td colspan="<?= count($wochen) ?>">
      <input type="text" id="dozentname" style="width:150px;" onkeyup="dozenten_keyup(event)" /><button type="button" onclick="dozenten_suchen()">Suchen</button>
      <table id="gefundene_dozenten" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse;display:none;">
        <tr><td colspan="2"><button type="button" onclick="dozentensuche_fertig()">Fertig</button></td></tr>
      </table>
    </td>
  </tr>
  <tr id="dozentenkwtr">
    <th class="erstespalte"></th>
<?php
foreach($wochen as $w) {
?>
    <th>
      <?= $w->display ?><br />
      <?= $zeitraeume[$w->kw] ?>
    </th>
<?php
}
?>
  </tr>
  <tr id="raeumetr" style="border-top:2px solid black;">
    <th class="erstespalte">Räume</th>
    <td colspan="<?= count($wochen) ?>">
      <input type="text" id="raumtuer" style="width:150px;" onkeyup="raeume_keyup(event)" /><button type="button" onclick="raeume_suchen()">Suchen</button>
      <table id="gefundene_raeume" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse;display:none;">
        <tr><td colspan="2"><button type="button" onclick="raeumesuche_fertig()">Fertig</button></td></tr>
      </table>
    </td>
  </tr>
  <tr id="raeumekwtr">
    <th class="erstespalte"></th>
<?php
foreach($wochen as $w) {
?>
    <th>
      <?= $w->display ?><br />
      <?= $zeitraeume[$w->kw] ?>
    </th>
<?php
}
?>
  </tr>
</table>
<?php
$seite->endeGenerieren();
?>