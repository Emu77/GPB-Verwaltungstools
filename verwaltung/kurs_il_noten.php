<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungKurs.php';
require_once 'VerwaltungTn.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'VerwaltungKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}

$klausur=null;
$nachklausur=null;
$il=array();
$stmt=empty($kurs->klassenids) ? null : 
  $db->prepare("select distinct tn.*,n.*
    from gpb_klasse_tn ktn
    join gpb_tn tn on tn.id=ktn.tnid
    left outer join gpb_note n on n.kursid=".$kurs->id." and n.tnid=tn.id
    where ktn.klasseid in(".implode(',',$kurs->klassenids).")
    and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<='".$kurs->ende."')
    and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>='".$kurs->beginn."') order by tn.nachname,tn.vorname");
$tns=new Liste('VerwaltungTn',$stmt);
$tnmoodleids=array();
foreach($tns->alle as $tn) {
  if($tn->moodleid>0) {
    $tnmoodleids[$tn->moodleid]=$tn->id;
  }
}

if($kurs->moodleid>0) {
  $daten=(object)array(
    'courseid'=>$kurs->moodleid
  );
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=auswerten&daten='.urlencode(json_encode($daten));
  $aufgaben=json_decode(file_get_contents($url));
  if(is_object($aufgaben) && isset($aufgaben->exception)) {
    $fehler=json_encode($aufgaben);
    unset($aufgaben);
  } else if(!is_object($aufgaben)) {
    $aufgaben=json_decode($aufgaben);
  }
} else {
  $fehler='Kein Moodle-Kurs';
}
if(isset($aufgaben)) {
  foreach($aufgaben as $aufgabe) {
    $aufgabe->abgaben=(array)$aufgabe->abgaben; //dies ist eine Map mit User-IDs als Keys (JSON-Objekt zu PHP Map konvertieren)
    if(strtolower(substr($aufgabe->titel,0,2))=='il') {
      $il[]=$aufgabe;
    } else if(strtolower(substr($aufgabe->titel,0,4))=='nach') {
      $nachklausur=$aufgabe;
    } else {
      $klausur=$aufgabe;
    }
  }
}

$db->query("delete from gpb_nachschreibetermin where termin<'".date('Y-m-d')."'");
$nachschreibetermine=array();
$result=$db->query("select * from gpb_nachschreibetermin order by termin");
while($row=$result->fetch_object()) {
  $row->wann=date('d.m.Y H:i',strtotime($row->termin));
  $nachschreibetermine[]=$row;
}
$result->free();

function makeKopfTr() {
  global $kurs,$il,$klausur,$nachklausur;
?>
  <tr>
    <td>&nbsp;</td>
<?php
  if($kurs->notenstatus!='keine') {
    makeKopfTd('klausurtd',$klausur,'Keine Klausur Tätigkeit gefunden <button type="button" onclick="aufgabe_erstellen(\'klausur\')">Erstellen</button>','note');
?>
    <th valign="bottom">TN Fehlt</th>
    <th valign="bottom">Note</th>
    <th valign="bottom">Termin</th>
    <th valign="bottom">Nachschreiben</th>
<?php
    makeKopfTd('nachklausurtd',$nachklausur,'Keine Nachklausur Tätigkeit gefunden <button type="button" onclick="aufgabe_erstellen(\'nachklausur\')">Erstellen</button>',$kurs->notenstatus=='ok' ? 'nachnote' : null);
?>
    <th valign="bottom">Nachklausur-Note</th>
    <td class="abstand">&nbsp;</td>
<?php
  }
  if(empty($il)) {
    makeKopfTd(null,null,'Keine IL-Tätigkeiten gefunden',null);
  } else {
    foreach($il as $aufgabe) {
      makeKopfTd(null,$aufgabe,'',null);
    }
  }
?>
    <td>&nbsp;</td>
  </tr>
<?php
}
function makeKopfTd($id,$aufgabe,$fehlertext,$notenuebernehmen=null) {
  if(empty($aufgabe)) {
?>
    <td <?= empty($id) ? '' : 'id="'.$id.'"' ?> class="ilaufgabe" align="right" valign="bottom"><?= $fehlertext ?></td>
<?php
  } else {
?>
    <td <?= empty($id) ? '' : 'id="'.$id.'"' ?> class="ilaufgabe" align="right" valign="bottom">
<?php
    if($notenuebernehmen && ($aufgabe->modname=='quiz' || $aufgabe->modname=='assign')) {
?>
      <button type="button" onclick="noten_uebernehmen('<?= $aufgabe->modname ?>',<?= $aufgabe->cminstance ?>,'<?= $notenuebernehmen ?>')">Bewertung aus Moodle holen</button>
<?php
    }
?>
      <a href="<?= $aufgabe->url ?>" target="moodle"><?= $aufgabe->titel ?><?= $aufgabe->sichtbar ? '' : ' (verborgen)' ?></a>
    </td>
<?php
  }
}
function makeInhaltTr() {
  global $kurs,$il,$klausur,$nachklausur;
?>
  <tr>
    <td align="right">Inhalt vorhanden?</td>
<?php
  if($kurs->notenstatus!='keine') {
    makeInhaltTd('klausurinhalttd',$klausur);
?>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
<?php
    makeInhaltTd('nachklausurinhalttd',$nachklausur);
?>
    <td>&nbsp;</td>
    <td class="abstand">&nbsp;</td>
<?php
  }
  if(empty($il)) {
    makeInhaltTd(null,null);
  } else {
    foreach($il as $aufgabe) {
      makeInhaltTd(null,$aufgabe);
    }
  }
?>
    <td>Inhalt vorhanden?</td>
  </tr>
<?php
}
function makeInhaltTd($id,$aufgabe) {
  if(empty($aufgabe)) {
?>
    <td <?= empty($id) ? '' : 'id="'.$id.'"' ?> class="aufgabeninhalt">&nbsp;</td>
<?php
  } else {
?>
    <td <?= empty($id) ? '' : 'id="'.$id.'"' ?> class="aufgabeninhalt <?= $aufgabe->inhalt_vorhanden<0 ? '' : ($aufgabe->inhalt_vorhanden>0 ? 'ok' : 'nok') ?>" <?= $aufgabe->inhalt_vorhanden<0 ? 'title="Bitte in Moodle nachschauen"' : '' ?>><?= $aufgabe->inhalt_vorhanden<0 ? '?' : ($aufgabe->inhalt_vorhanden>0 ? '✓' : 'X') ?></td>
<?php
  }
}
function makeAbgabeTr($tn) {
  global $kurs,$il,$klausur,$nachklausur;
?>
  <tr>
    <td><a href="tn_sehen.php?tnid=<?= $tn->id ?>"><?= $tn->anrede ?> <?= $tn->vorname ?> <?= $tn->nachname ?></a></td>
<?php
  if($kurs->notenstatus!='keine') {
    makeAbgabeTd($klausur,$tn);
?>
    <td align="center"><input type="checkbox" id="fehlt_<?=$tn->id ?>" value="J" <?= $tn->fehlt ? 'checked' : '' ?> onchange="speichern(<?= $tn->id ?>,'fehlt',this.checked ? '1' : '0')" /></td>
    <td align="left" class="edit" id="note_<?=$tn->id ?>" onclick="note_bearbeiten(<?= $tn->id ?>,'note','Note','<?= $tn->note ?>')">&#x270E; <span id="note_span_<?= $tn->id ?>"><?=$tn->note ?></span></td>
    <td align="left" class="edit" id="nachschreibetermin_<?= $tn->id ?>"><div onclick="nachschreibetermin_bearbeiten(<?= $tn->id ?>,'<?= $tn->nachschreibetermin ?>')">&#x270E; <?= $tn->nachschreibetermin ? date('d.m.Y H:i',strtotime($tn->nachschreibetermin)) : '' ?></div></td>
    <td align="left" id="nachschreibestatus_<?= $tn->id ?>"><div onclick="nachschreibestatus_bearbeiten(<?= $tn->id ?>,'<?= $tn->nachschreibestatus ?>')">&#x270E; <?= $tn->nachschreibestatus ?></div></td>
<?php
    makeAbgabeTd($nachklausur,$tn);
?>
    <td align="left" class="edit" id="nachnote_<?=$tn->id ?>" onclick="note_bearbeiten(<?= $tn->id ?>,'nachnote','Nachklausur-Note','<?= $tn->nachnote ?>')">&#x270E; <span id="nachnote_span_<?= $tn->id ?>"><?=$tn->nachnote ?></span></td>
    <td class="abstand">&nbsp;</td>
<?php
  }
  if(empty($il)) {
    makeAbgabeTd(null,$tn);
  } else {
    foreach($il as $aufgabe) {
      makeAbgabeTd($aufgabe,$tn);
    }
  }
?>
    <td><a href="tn_sehen.php?tnid=<?= $tn->id ?>"><?= $tn->anrede ?> <?= $tn->vorname ?> <?= $tn->nachname ?></a></td>
  </tr>
<?php
}
function makeAbgabeTd($aufgabe,$tn) {
  if(empty($aufgabe)) {
?>
    <td class="abgabe">&nbsp;</td>
<?php
  } else {
?>
    <td class="abgabe <?= isset($aufgabe->abgaben[$tn->moodleid]) ? ($aufgabe->abgaben[$tn->moodleid] ? 'ok' : 'nok') : '' ?>">
      <?= isset($aufgabe->abgaben[$tn->moodleid]) ? ($aufgabe->abgaben[$tn->moodleid] ? '✓' : 'X') : '&nbsp;' ?>
    </td>
<?php
  }
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('IL+Noten vom Kurs '.$kurs->titel);
$seite->anfangGenerieren('');
$kurs->makeSehen();
?>
<style>
.ilaufgabe {
  writing-mode:vertical-rl; /* sideways-lr nicht von Chrome unterstützt */
  width:1.5em;
  padding:2px;
}
.abgabe,
.aufgabeninhalt {
  text-align:center;
}
.abgabe.ok,
.aufgabeninhalt.ok {
  background-color:green;
}
.abgabe.nok,
.aufgabeninhalt.nok {
  color:black;
  background-color:red;
}
.abstand {
  width:4em;
  border-top:1px solid white;
  border-bottom:1px solid white;
}
.edit {
  cursor:pointer;
}
</style>
<script>
<?php
if($kurs->moodleid>0) {
?>
//art=='klausur' oder 'nachklausur'
function aufgabe_erstellen(art) {
  let req=new XMLHttpRequest();
  req.onreadystatechange = function () {
    if(this.readyState != 4) return;
    let aufgabe=JSON.parse(this.responseText);
    if((typeof aufgabe=='object' && aufgabe.exception) || aufgabe=='NOK') {
      alert(this.responseText);
      return;
    }
    if(typeof aufgabe=='string') {
      aufgabe=JSON.parse(aufgabe);
    }
    document.getElementById(art+'td').innerHTML='<a href="'+(aufgabe.url.startsWith('http') ? '' : '<?= $moodleurl ?>')+aufgabe.url+'" target="moodle">'+aufgabe.titel+(aufgabe.sichtbar ? '' : ' (verborgen)')+'</a>';
    let td=document.getElementById(art+'inhalttd');
    td.classList.add('nok');
    td.innerText='X';
  };
  let daten={
    'courseid':<?= $kurs->moodleid ?>,
    'kapitelnummer':-1,
    'kapiteltitel':'Klausur und Nachklausur',
    'aufgabentitel':(art=='klausur' ? 'Klausur' : 'Nachklausur'),
    'benachrichtigungen':(art=='nachklausur' ? 1 : 0),
    'sichtbar':0
  };
  req.open('GET','<?= $moodleurl ?>webservice/rest/server.php?wstoken=<?= $moodletoken ?>&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=aufgabe_erstellen&daten='+encodeURIComponent(JSON.stringify(daten)));
  req.send();
}
<?php
}
?>
const tnids=<?= json_encode(array_keys($tns->byId)) ?>;
const tnmoodleids=<?= json_encode($tnmoodleids) ?>;
function calcMittelwert() {
  let mittelwerttd=document.getElementById('mittelwerttd');
  if(!mittelwerttd) return;
  let summe=0;
  let anzahl=0;
  for(let tnid of tnids) {
    let note=document.getElementById('nachnote_'+tnid).innerText.substring(2);
    if(note.length>0) {
      summe+=parseInt(note);
      ++anzahl;
    } else {
      let elem=document.getElementById('fehlt_'+tnid);
      if(!elem.checked) {
        note=document.getElementById('note_'+tnid).innerText.substring(2);
        if(note.length>0) {
          summe+=parseInt(note);
          ++anzahl;
        }
      }
    }
  }
  mittelwerttd.innerText=anzahl<=0 ? '' : Math.round(summe/anzahl);
}
document.body.onload=calcMittelwert;
function note_bearbeiten(tnid,spalte,kopf,wert) {
  if(typeof geaenderteWerte[spalte+'_'+tnid]!='undefined') {
    wert=geaenderteWerte[spalte+'_'+tnid];
  }
  let neuerwert=prompt(kopf,wert);
  if(neuerwert===null || neuerwert===wert) return;
  speichern(tnid,spalte,neuerwert);
}
let nachschreibetermine=<?= json_encode($nachschreibetermine) ?>;
function nachschreibetermin_bearbeiten(tnid,wert) {
  if(typeof geaenderteWerte['nachschreibetermin_'+tnid]!='undefined') {
    wert=geaenderteWerte['nachschreibetermin_'+tnid];
  }
  let elem=document.getElementById('nachschreibetermin_'+tnid);
  const altHTML=elem.innerHTML;
  elem.innerHTML='';
  let ed=document.createElement('select');
  for(let t of nachschreibetermine) {
    let o=document.createElement('option');
    o.value=t.termin;
    o.innerText=t.wann;
    ed.appendChild(o);
  }
  ed.value=wert;
  ed.onchange=function() {
    speichern(tnid,'nachschreibetermin',this.value,nachschreibetermin_gespeichert);
  }
  elem.appendChild(ed);
  ed.focus();
  elem.appendChild(document.createElement('br'));
  ed=document.createElement('input');
  ed.type='datetime-local';
  ed.value=wert.length<=0 ? '<?= date('Y-m-d H:i',strtotime('next thursday 12:30')) ?>' : wert;
  ed.onkeydown=function(event) {
    if(event.key=='Escape') {
      elem.innerHTML=altHTML;
    } else if(event.key=='Enter') {
      speichern(tnid,'nachschreibetermin',this.value,nachschreibetermin_gespeichert);
    }
  };
  elem.appendChild(ed);
}
function nachschreibetermin_gespeichert(tnid,neuerWert) {
  let i=neuerWert.indexOf('=');
  let td=document.getElementById('nachschreibetermin_'+tnid);
  td.innerHTML='<div onclick="nachschreibetermin_bearbeiten('+tnid+',\''+neuerWert.substring(0,i)+'\')">&#x270E; '+neuerWert.substring(i+1)+'</div>';
}
var nachschreibestatusse=['','beantragt','genehmigt','abgelehnt','geschrieben','erledigt'];
function nachschreibestatus_bearbeiten(tnid,wert) {
  if(typeof geaenderteWerte['nachschreibestatus_'+tnid]!='undefined') {
    wert=geaenderteWerte['nachschreibestatus_'+tnid];
  }
  let elem=document.getElementById('nachschreibestatus_'+tnid);
  const altHTML=elem.innerHTML;
  elem.innerHTML='';
  let ed=document.createElement('select');
  for(let s of nachschreibestatusse) {
    let o=document.createElement('option');
    o.value=s;
    o.innerText=s;
    ed.appendChild(o);
  }
  ed.value=wert;
  ed.onchange=function() {
    speichern(tnid,'nachschreibestatus',this.value,nachschreibestatus_gespeichert);
  }
  ed.onkeydown=function(event) {
    if(event.key=='Escape') {
      elem.innerHTML=altHTML;
    } else if(event.key=='Enter') {
      speichern(tnid,'nachschreibetermin',this.value,nachschreibestatus_gespeichert);
    }
  }
  elem.appendChild(ed);
  ed.focus();
}
function nachschreibestatus_gespeichert(tnid,neuerWert) {
  let td=document.getElementById('nachschreibestatus_'+tnid);
  td.innerHTML='<div onclick="nachschreibestatus_bearbeiten('+tnid+',\''+neuerWert+'\')">&#x270E; '+neuerWert+'</div>';
  if(neuerWert!='erledigt') {
    alert('TODO Emailfenster öffnen oder Email senden');
  }
}
const geaenderteWerte={};
function speichern(tnid,spalte,neuerWert,callback) {
  let req=new XMLHttpRequest();
  req.onreadystatechange = function () {
    if(this.readyState != 4) return;
    if(this.responseText.startsWith('OK')) {
      geaenderteWerte[spalte+'_'+tnid]=neuerWert;
      let elem=document.getElementById(spalte+'_'+tnid);
      if(elem.tagName.toLowerCase()=='input' && elem.type.toLowerCase()=='checkbox') {
        elem.checked=neuerWert=='1';
      } else if(callback) {
        callback(tnid,this.responseText.substring(2));
      } else {
        elem.innerHTML='&#x270E; '+this.responseText.substring(2);
      }
      calcMittelwert();
    } else {
      let elem=document.getElementById(spalte+'_'+tnid);
      if(elem.tagName.toLowerCase()=='input' && elem.type.toLowerCase()=='checkbox') {
        elem.checked=neuerWert!=1;
      }
      alert(this.responseText);
    }
  };
  req.open('GET','kurs_notenteil_speichern.php?kursid=<?= $kurs->id ?>&tnid='+tnid+'&spalte='+spalte+'&wert='+encodeURIComponent(neuerWert));
  req.send();
}
function noten_uebernehmen(modname,cminstance,was) {
  let daten={
      'courseid':<?= $kurs->moodleid ?>,
      'modname':modname,
      'cminstance':cminstance,
      'userids':Object.keys(tnmoodleids)
    };
  if(daten.userids.length<=0) {
    alert('Keine TN, keine Noten!');
    return;
  }
  let req=new XMLHttpRequest();
  req.onreadystatechange=function() {
    if(this.readyState != 4) return;
    let noten=JSON.parse(this.responseText);
    if(typeof noten=='object' && typeof noten.exception!='undefined') {
      console.log(noten);
      alert(this.responseText);
      return;
    }
    if(typeof noten=='string') {
      noten=JSON.parse(noten);
    }
    for(let n of noten) {
      let tnid=tnmoodleids[n.userid];
      speichern(tnid,was,Math.round(parseFloat(n.finalgrade)),() => {
        let elem=document.getElementById(was+'_span_'+tnid);
        if(elem) {
          elem.innerText=Math.round(parseFloat(n.finalgrade));
        }
      });
    }
  };
  let url='<?= $moodleurl ?>webservice/rest/server.php?wstoken=<?= $moodletoken ?>&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=noten_finden&daten='+encodeURIComponent(JSON.stringify(daten));
  req.open('GET',url);
  req.send();
}
</script>
<div style="margin-top:1em;margin-bottom:1em;">
  <input type="checkbox" id="keinenoten" <?= $kurs->notenstatus=='keine' ? 'checked' : '' ?>
    onchange="location.href='kurs_notenstatus_speichern.php?kursid=<?= $kurs->id ?>&notenstatus=<?= $kurs->notenstatus=='keine' ? 'todo' : 'keine' ?>'" />
  Keine Noten
<?php
  if($kurs->notenstatus!='keine') {
?>
&nbsp; &nbsp; &nbsp; <a href="kurs_klausur_deckblatt.php?kursid=<?= $kurs->id ?>&art=Klausur" target="deckblatt">Klausur-Deckblatt</a>
&nbsp; <a href="kurs_klausur_deckblatt.php?kursid=<?= $kurs->id ?>&art=Nachklausur" target="deckblatt">Nachklausur-Deckblatt</a><br />
<?php
  }
?>
</div>
<?php
  if(isset($fehler)) {
?>
<div class="nok"><?= $fehler ?></div><br />
<?php
  }
?>
<table id="auswertung" border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:2em;">
  <tr>
    <td>&nbsp;</td>
<?php
if($kurs->notenstatus!='keine') {
?>
    <th colspan="7">Noten</th>
    <td class="abstand">&nbsp;</td>
<?php
}
?>
    <th colspan="<?= max(1,count($il)) ?>">IL-Auswertung</td>
  </tr>
<?php
  makeKopfTr();
  makeInhaltTr();
  if(empty($tns->alle)) {
?>
  <tr>
    <td colspan="<?= 1+max(1,count($il))+($kurs->notenstatus=='keine' ? 0 : 8) ?>">Keine Teilnehmer gefunden</td>
  </tr>
<?php
  } else {
    foreach($tns->alle as $tn) {
      makeAbgabeTr($tn);
    }
    if($kurs->notenstatus!='keine') {
?>
  <tr>
    <td>&nbsp;</td>
    <td colspan="2" align="right" style="border-right:1px solid white;">Mittelwert:</td>
    <td id="mittelwerttd" align="center"></td>
    <td colspan="4">&nbsp;</td>
    <td class="abstand">&nbsp;</td>
    <td colspan="<?= 1+max(1,count($il)) ?>" rowspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td colspan="7">
      <input type="checkbox" name="noten_ok" value="J" <?= $kurs->notenstatus=='ok' ? 'checked' : '' ?>
        onchange="location.href='kurs_notenstatus_speichern.php?kursid=<?= $kurs->id ?>&notenstatus=<?= $kurs->notenstatus=='ok' ? 'todo' : 'ok' ?>'" /> Noten vollständig<br />
      <b>Erläutertung vom Dozent bei Mittelwert &lt; 65 oder &gt; 85:</b><br />
      <?= empty($kurs->notenerklaerung) ? '(keine)' : nl2br($kurs->notenerklaerung) ?>
    </td>
    <td class="abstand">&nbsp;</td>
  </tr>
<?php
    }
  }
?>
</table>
<br />
<a href="kurse.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
exit;
?>