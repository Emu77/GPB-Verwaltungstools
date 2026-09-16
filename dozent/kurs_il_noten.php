<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'DozentKurs.php';
require_once 'DozentTn.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'DozentKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}
if(!in_array($ich->id,$kurs->dozentenids)) { //Dozent nicht im Kurs angemeldet: darf nicht
  header('Location:kurs_sehen.php?kursid='.$kurs->id);
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
    and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>='".$kurs->beginn."') order by ".$ich->tnsortierung);
$tns=new Liste('DozentTn',$stmt);
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
    error_log('Moodle-Fehler bei courseid='.$kurs->moodleid.': '.json_encode($aufgaben));
    $fehler='Die Aufgaben aus Moodle konnten gerade nicht geladen werden. Bitte versuche es später erneut.';
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

function makeKopfTr() {
  global $kurs,$il,$klausur,$nachklausur;
?>
  <tr>
    <td>&nbsp;</td>
<?php
  if($kurs->notenstatus!='keine') {
    makeKopfTd($klausur,'Keine Klausur Tätigkeit gefunden','note');
?>
    <th valign="bottom">TN Fehlt</th>
    <th valign="bottom">Punkte<br />/100</th>
<?php
    makeKopfTd($nachklausur,'Keine Nachklausur Tätigkeit gefunden',$kurs->notenstatus=='ok' ? 'nachnote' : null);
?>
    <th valign="bottom">Nachklausur<br />Punkte /100</th>
    <td class="abstand">&nbsp;</td>
<?php
  }
  if(empty($il)) {
    makeKopfTd(null,'Keine IL-Tätigkeiten gefunden',null);
  } else {
    foreach($il as $aufgabe) {
      makeKopfTd($aufgabe,'',null);
    }
  }
?>
    <td>&nbsp;</td>
  </tr>
<?php
}
function makeKopfTd($aufgabe,$fehlertext,$notenuebernehmen=null) {
  if(empty($aufgabe)) {
?>
    <td class="ilaufgabe" align="right" valign="bottom"><?= $fehlertext ?></td>
<?php
  } else {
?>
    <td class="ilaufgabe" align="right" valign="bottom">
<?php
    if($notenuebernehmen && ($aufgabe->modname=='quiz' || $aufgabe->modname=='assign')) {
?>
      <button type="button" onclick="noten_uebernehmen('<?= $aufgabe->modname ?>',<?= $aufgabe->cminstance ?>,'<?= $notenuebernehmen ?>')">Bewertung aus Moodle holen</button>
<?php
    }
?>
      <a href="<?= $aufgabe->url ?>" target="moodle"><?= $aufgabe->titel ?></a>
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
    makeInhaltTd($klausur);
?>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
<?php
    makeInhaltTd($nachklausur);
?>
    <td>&nbsp;</td>
    <td class="abstand">&nbsp;</td>
<?php
  }
  if(empty($il)) {
    makeInhaltTd(null);
  } else {
    foreach($il as $aufgabe) {
      makeInhaltTd($aufgabe);
    }
  }
?>
    <td>Inhalt vorhanden?</td>
  </tr>
<?php
}
function makeInhaltTd($aufgabe) {
  if(empty($aufgabe)) {
?>
    <td class="aufgabeninhalt">&nbsp;</td>
<?php
  } else {
?>
    <td class="aufgabeninhalt <?= $aufgabe->inhalt_vorhanden<0 ? '' : ($aufgabe->inhalt_vorhanden>0 ? 'ok' : 'nok') ?>" <?= $aufgabe->inhalt_vorhanden<0 ? 'title="Bitte in Moodle nachschauen"' : '' ?>><?= $aufgabe->inhalt_vorhanden<0 ? '?' : ($aufgabe->inhalt_vorhanden>0 ? '✓' : 'X') ?></td>
<?php
  }
}
function makeAbgabeTr($tn) {
  global $kurs,$il,$klausur,$nachklausur;
?>
  <tr>
    <td><?= $tn->anrede ?> <?= $tn->vorname ?> <?= $tn->nachname ?></td>
<?php
  if($kurs->notenstatus!='keine') {
    makeAbgabeTd($klausur,$tn);
?>
    <td align="center"><input type="checkbox" name="fehlt_<?=$tn->id ?>" id="fehlt_<?=$tn->id ?>" value="J" <?= $tn->fehlt ? 'checked' : '' ?> onchange="calcMittelwert(null,true)" /></td>
    <td align="center"><input type="number" name="note_<?=$tn->id ?>" id="note_<?=$tn->id ?>" value="<?=$tn->note ?>" min="0" max="100" step="1" style="width:50px;" onchange="calcMittelwert(null,true)" /></td>
<?php
    makeAbgabeTd($nachklausur,$tn);
    if($kurs->notenstatus=='ok') {
?>
    <td align="center" id="nachnote_<?= $tn->id ?>" class="edit" onclick="nachnote_bearbeiten(<?= $tn->id ?>,'<?= $tn->nachnote ?>')">&#x270E; <?= $tn->nachnote ?></td>
<?php
    } else {
?>
    <td></td>
<?php
    }
?>
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
    <td><?= $tn->anrede ?> <?= $tn->vorname ?> <?= $tn->nachname ?></td>
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

require_once 'DozentSeite.php';
$seite=new DozentSeite('IL+Noten vom Kurs '.$kurs->titel);
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
const tnids=<?= json_encode(array_keys($tns->byId)) ?>;
const tnmoodleids=<?= json_encode($tnmoodleids) ?>;
function calcMittelwert(event,geaendert=false) {
  let mittelwerttd=document.getElementById('mittelwerttd');
  if(!mittelwerttd) return;
  let summe=0;
  let anzahl=0;
  for(let tnid of tnids) {
    let elem=document.getElementById('nachnote_'+tnid);
    let note=elem ? elem.innerText.substring(2).trim() : '';
    if(note.length>0) {
      summe+=parseInt(note);
      ++anzahl;
    } else {
      elem=document.getElementById('fehlt_'+tnid);
      if(!elem.checked) {
        note=document.getElementById('note_'+tnid).value;
        if(note.length>0) {
          summe+=parseInt(note);
          ++anzahl;
        }
      }
    }
  }
  let mw=anzahl<=0 ? '' : Math.round(summe/anzahl);
  mittelwerttd.innerText=mw;
  document.getElementById('mittelwert').value=mw;
  let erkl=document.getElementById('notenerklaerung');
  erkl.disabled=!erkl.value && mw!=='' && mw>=65 && mw<=85;
  document.getElementById('notenerklaerung_nachricht').style.display=erkl.disabled ? 'none' : '';
  if(geaendert) {
      document.getElementById('noten_nachricht').style.display='none';
  }
}
document.body.onload=calcMittelwert;
function nachnote_bearbeiten(tnid,wert) {
  const neu=prompt('Nachnote:',wert);
  if(neu===null || neu==wert) return;
  let req=new XMLHttpRequest();
  req.onreadystatechange = function () {
    if(this.readyState != 4) return;
    if(this.responseText.startsWith('OK')) {
      let elem=document.getElementById('nachnote_'+tnid);
      elem.innerHTML='&#x270E; '+neu;
      elem.onclick=function() {
        nachnote_bearbeiten(tnid,neu);
      };
      calcMittelwert(null,true);
    } else {
      alert(this.responseText);
    }
  };
  req.open('GET','kurs_nachnote_speichern.php?kursid=<?= $kurs->id ?>&tnid='+tnid+'&nachnote='+encodeURIComponent(neu));
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
      alert('Die Noten konnten gerade nicht aus Moodle geladen werden.');
      return;
    }
    if(typeof noten=='string') {
      noten=JSON.parse(noten);
    }
    for(let n of noten) {
      let tnid=tnmoodleids[n.userid];
      let elem=document.getElementById(was+'_'+tnid);
      if(elem) {
        elem.value=Math.round(parseFloat(n.finalgrade));
      }
    }
  };
  let url='<?= $moodleurl ?>webservice/rest/server.php?wstoken=<?= $moodletoken ?>&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=noten_finden&daten='+encodeURIComponent(JSON.stringify(daten));
  req.open('GET',url);
  req.send();
}
</script>
<?php
  if(isset($fehler)) {
?>
<div class="nok"><?= $fehler ?></div><br />
<?php
  }
?>
<input type="checkbox" id="keinenoten" <?= $kurs->notenstatus=='keine' ? 'checked' : '' ?>
  onchange="location.href='kurs_keine_noten.php?kursid=<?= $kurs->id ?>&keinenoten=<?= $kurs->notenstatus=='keine' ? 'N' : 'J' ?>'" />
Keine Noten
<?php
if($kurs->notenstatus!='keine') {
?>
&nbsp; &nbsp; &nbsp; <a href="kurs_klausur_deckblatt.php?kursid=<?= $kurs->id ?>&art=Klausur" target="deckblatt">Klausur-Deckblatt</a>
&nbsp; <a href="kurs_klausur_deckblatt.php?kursid=<?= $kurs->id ?>&art=Nachklausur" target="deckblatt">Nachklausur-Deckblatt</a>
<?php
}
?>
<br />
<br />
<form action="kurs_noten_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="kursid" value="<?= $kurs->id ?>" />
<table id="auswertung" border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <td>Sortierung: <a href="tnsortierung_aendern.php?url=<?= urlencode('kurs_il_noten.php?kursid='.$kurs->id) ?>&kriterium=vorname">VN</a> <a href="tnsortierung_aendern.php?url=<?= urlencode('kurs_il_noten.php?kursid='.$kurs->id) ?>&kriterium=nachname">NN</a></td>
<?php
if($kurs->notenstatus!='keine') {
?>
    <th colspan="5">Noten</th>
    <td class="abstand">&nbsp;</td>
<?php
}
?>
    <th colspan="<?= max(1,count($il)) ?>">IL-Auswertung</th>
    <td align="right">Sortierung: <a href="tnsortierung_aendern.php?url=<?= urlencode('kurs_il_noten.php?kursid='.$kurs->id) ?>&kriterium=vorname">VN</a> <a href="tnsortierung_aendern.php?url=<?= urlencode('kurs_il_noten.php?kursid='.$kurs->id) ?>&kriterium=nachname">NN</a></td>
  </tr>
<?php
  makeKopfTr();
  makeInhaltTr();
  if(empty($tns->alle)) {
?>
  <tr>
    <td colspan="<?= 1+max(1,count($il))+($kurs->notenstatus=='keine' ? 0 : 6) ?>">Keine Teilnehmer gefunden</td>
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
    <td colspan="2">&nbsp;</td>
    <td class="abstand">&nbsp;</td>
    <td colspan="<?= 1+max(1,count($il)) ?>" rowspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td colspan="5">
      <input type="checkbox" name="noten_ok" value="J" <?= $kurs->notenstatus=='ok' ? 'checked' : '' ?> /> Noten vollständig
      <div id="notenerklaerung_div">
        <input type="hidden" name="mittelwert" id="mittelwert" value="" />
        Mittelwert &lt; 65 oder &gt; 85 bitte für Kundenservice und TN erläutern:<br />
        <textarea name="notenerklaerung" id="notenerklaerung" style="width:380px;" disabled><?= $kurs->notenerklaerung ?></textarea>
        <div id="notenerklaerung_nachricht">
<?php
      if(isset($_SESSION['notenerklaerung_nachricht'])) {
        echo $_SESSION['notenerklaerung_nachricht'];
        unset($_SESSION['notenerklaerung_nachricht']);
      }
?>
        </div>
      </div>
      <input type="submit" value="Speichern" />
      <div id="noten_nachricht">
<?php
      if(isset($_SESSION['noten_nachricht'])) {
        echo $_SESSION['noten_nachricht'];
        unset($_SESSION['noten_nachricht']);
      }
?>
      </div>
    </td>
    <td class="abstand">&nbsp;</td>
<?php
    }
  }
?>
</table>
</form>
<br />
<a href="kurse.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
exit;
?>