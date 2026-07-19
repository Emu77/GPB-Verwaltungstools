<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungKurs.php';
require_once 'VerwaltungTn.php';

$db->query("delete from gpb_nachschreibetermin where termin<'".date('Y-m-d')."'");
$termine=array();
$naechstertermin=null;
$result=$db->query("select * from gpb_nachschreibetermin ".(empty($ich->ort) ? "" : "where ort='".addslashes($ich->ort)."'")." order by termin");
while($row=$result->fetch_object()) {
  $row->wann=date('d.m.Y H:i',strtotime($row->termin));
  $termine[]=$row;
  $naechstertermin=$row->termin;
}
$result->free();
$naechstertermin=empty($naechstertermin) ? strtotime('next thursday 12:30') : strtotime('+2 weeks',strtotime($naechstertermin));

$sortierung=isset($_SESSION['nachschreiber_sortierung']) ? $_SESSION['nachschreiber_sortierung'] : array('n.nachschreibetermin','k.titel','tn.nachname');
$nachschreiber=array();
$nachschreiber_offen=array();
$nachschreiber_fertig=array();
$kursids=array();
$tnids=array();
$result=$db->query("select n.*
    ,k.titel
    ,tn.nachname
  from gpb_note n
  join gpb_tn_view tn on tn.id=n.tnid
  join gpb_kurs k on k.id=n.kursid
  left outer join gpb_raum r on r.id=k.raumid
  where ".(empty($ich->ort) ? "" : "(r.ort='' or r.ort='".addslashes($ich->ort)."') and ")."
    ((n.nachnote is null) or (n.nachschreibetermin is not null)) and
    (n.nachschreibestatus='beantragt' or n.nachschreibestatus='genehmigt' or n.nachschreibestatus='geschrieben'
      or ((n.nachschreibestatus='abgelehnt' or n.nachschreibestatus='keine Abgabe' or n.nachschreibestatus='Dozent reagiert nicht' or n.nachschreibestatus='erledigt') and n.nachschreibetermin>'".date('Y-m-d',strtotime('-1 month'))."'))
  order by ".implode(',',$sortierung));
while($row=$result->fetch_object()) {
  $row->wann=empty($row->nachschreibetermin) || $row->nachschreibetermin=='0000-00-00 00:00:00' ? '' : date('d.m.Y H:i',strtotime($row->nachschreibetermin));
  $nachschreiber[]=$row;
  if($row->nachschreibestatus=='abgelehnt' || $row->nachschreibestatus=='Dozent reagiert nicht' || $row->nachschreibestatus=='erledigt') {
    $nachschreiber_fertig[]=$row;
  } else {
    $nachschreiber_offen[]=$row;
  }
  $kursids[$row->kursid]=true;
  $tnids[$row->tnid]=true;
}
$result->free();

$kurse=new Liste('VerwaltungKurs',empty($kursids) ? null : $db->prepare("select * from gpb_kurs_view where id in(".implode(',',array_keys($kursids)).")"));
Kurs::dozentenLaden($kurse);
$tns=new Liste('VerwaltungTn',empty($tnids) ? null : $db->prepare("select * from gpb_tn_view where id in(".implode(',',array_keys($tnids)).")"));

$kurseByMid=array();
foreach($kurse->alle as $kurs) {
  $kurs->noten=array();
  $kurs->nachklausur=null;
  if($kurs->moodleid>0) {
    $kurseByMid[$kurs->moodleid]=$kurs;
  }
}
if(!empty($kurseByMid)) {
  $daten=(object)array(
    'courseids'=>array_keys($kurseByMid)
  );
  $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nachklausuren_finden&daten='.urlencode(json_encode($daten));
  $aufgaben=json_decode(file_get_contents($url));
  if(is_object($aufgaben) && isset($aufgaben->exception)) {
    $fehler=json_encode($aufgaben);
    unset($aufgaben);
  } else if(!is_object($aufgaben)) {
    $aufgaben=json_decode($aufgaben);
  }
  if(isset($aufgaben)) {
    foreach($aufgaben as $aufgabe) {
      $kurseByMid[$aufgabe->courseid]->nachklausur=$aufgabe;
    }
  }
}
$nachschreibungen=array();
foreach($nachschreiber_offen as $n) {
  $n->kurs=$kurse->byId[$n->kursid];
  $n->tn=$tns->byId[$n->tnid];
  if(!isset($nachschreibungen[$n->kursid])) {
    $nachschreibungen[$n->kursid]=array();
  }
  $nachschreibungen[$n->kursid][$n->tnid]=array('termin'=>$n->nachschreibetermin,'status'=>$n->nachschreibestatus);
}
foreach($nachschreiber_fertig as $n) {
  $n->kurs=$kurse->byId[$n->kursid];
  $n->tn=$tns->byId[$n->tnid];
  if(!isset($nachschreibungen[$n->kursid])) {
    $nachschreibungen[$n->kursid]=array();
  }
  $nachschreibungen[$n->kursid][$n->tnid]=array('termin'=>$n->nachschreibetermin,'status'=>$n->nachschreibestatus);
}

$nachschreibestatusse=array(
  ''=>'white',
  'beantragt'=>'white',
  'genehmigt'=>'rgb(131,202,235)',
  'geschrieben'=>'rgb(255,192,0)',
  'keine Abgabe'=>'rgb(229,161,283)',
  'abgelehnt'=>'red',
  'Dozent reagiert nicht'=>'rgb(200,0,0)',
  'erledigt'=>'rgb(179,229,161)'
);

function makeTr($n) {
  global $nachschreibestatusse,$moodleisttest,$moodleurl;
  $kurs=$n->kurs;
  $tn=$n->tn;
  $aufgabe=null;
?>
  <tr>
    <td><a href="kurs_il_noten.php?kursid=<?= $kurs->id ?>"><?= $kurs->titel ?></a><br /><?= $kurs->ort ?></td>
<?php
      $kurs->makeMoodleMiniTd();
      if(empty($kurs->nachklausur)) {
        if($kurs->moodleid<=0) {
?>
    <td id="nachklausurtd_<?= $kurs->id ?>" class="aufgabeninhalt nok">Kein Moodle-Kurs</td>
<?php
        } else {
?>
    <td id="nachklausurtd_<?= $kurs->id ?>" class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?> aufgabeninhalt nok">(keine gefunden) <button type="button" onclick="aufgabe_erstellen(<?= $kurs->id ?>,<?= $kurs->moodleid ?>)">Erstellen</button></td>
<?php
        }
      } else {
        $aufgabe=$kurs->nachklausur;
?>
    <td id="nachklausurtd_<?= $kurs->id ?>" class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?> aufgabeninhalt <?= $aufgabe->inhalt_vorhanden==1 ? 'ok' : ($aufgabe->inhalt_vorhanden==0 ? 'nok' : '') ?>" <?= $aufgabe->inhalt_vorhanden<0 ? 'title="Inhalt im Moodle prüfen!"' : '' ?>>
      <a href="<?= substr($aufgabe->url,0,4)!='http' ? $moodleurl : '' ?><?= $aufgabe->url ?>" target="moodle"><?= $aufgabe->titel ?><?= $aufgabe->inhalt_vorhanden==0 ? ' (kein Inhalt)' : '' ?><?= $aufgabe->sichtbar ? '' : ' (verborgen)' ?></a>
    </td>
<?php
      }
?>
    <td class="dozenten">
<?php
    foreach($kurs->dozenten as $d) {
?>
      <a href="dozent_sehen.php?dozentid=<?= $d->id ?>" style="display:block;"><?= $d->vorname ?> <?= $d->nachname ?></a>
      <a href="mailto:<?= htmlentities($d->email,ENT_COMPAT) ?>?subject=Nachklausur <?= htmlentities($kurs->titel,ENT_COMPAT) ?>" style="display:block;"><?= $d->email ?></a>
<?php
    }
?>
    </td>
    <td><a href="tn_sehen.php?tnid=<?= $tn->id ?>"><?= $tn->anrede ?> <?= $tn->vorname ?> <?= $tn->nachname ?></a><br /><a href="mailto:<?= $tn->email ?>"><?= $tn->email ?></a></td>
    <td align="center"><input type="checkbox" id="fehlt_<?= $kurs->id ?>_<?= $tn->id ?>" value="J" <?= $n->fehlt ? 'checked' : '' ?> onchange="speichern(<?= $kurs->id ?>,<?= $tn->id ?>,'fehlt',this.checked ? '1' : '0')" /></td>
    <td align="center"><?=$n->note ?></td>
    <td align="left" class="edit" id="nachschreibetermin_<?= $kurs->id ?>_<?= $tn->id ?>"><div onclick="nachschreibetermin_bearbeiten(<?= $kurs->id ?>,<?= $tn->id ?>)">&#x270E; <span id="termin_<?= $kurs->id ?>_<?= $tn->id ?>"><?= $n->nachschreibetermin ? date('d.m.Y H:i',strtotime($n->nachschreibetermin)) : '' ?></span></div></td>
    <td align="left">
<?php
    if($aufgabe) {
      if($aufgabe->modname=='assign' || $aufgabe->modname=='quiz') {
?>
      <button type="button" onclick="aufgabe_freischalten(<?= $kurs->id ?>,<?= $tn->id ?>,<?= $kurs->moodleid ?>,'<?= $aufgabe->modname ?>',<?= $aufgabe->cmid ?>,<?= $aufgabe->cminstance ?>)">Aufgabe freischalten</button>
<?php
      } else if(!$aufgabe->sichtbar) {
?>
      <button type="button" onclick="aufgabe_freischalten(<?= $kurs->id ?>,<?= $tn->id ?>,<?= $kurs->moodleid ?>,'<?= $aufgabe->modname ?>',<?= $aufgabe->cmid ?>,<?= $aufgabe->cminstance ?>)">Aufgabe anzeigen</button>
<?php
      }
    }
?>
    </td>
    <td align="left" id="nachschreibestatus_<?= $kurs->id ?>_<?= $tn->id ?>" style="background-color:<?= $nachschreibestatusse[$n->nachschreibestatus] ?>"><div onclick="nachschreibestatus_bearbeiten(<?= $kurs->id ?>,<?= $tn->id ?>)">&#x270E; <?= $n->nachschreibestatus ?></div></td>
    <td align="left" class="edit" id="nachnote_<?= $kurs->id ?>_<?=$tn->id ?>" onclick="note_bearbeiten(<?= $kurs->id ?>,<?= $tn->id ?>,'nachnote','Nachklausur-Note','<?= $n->nachnote ?>')">&#x270E; <?=$n->nachnote ?></td>
  </tr>
<?php
    $erst=false;
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Nachschreiber '.(empty($ich->ort) ? '' : $ich->ort));
$seite->anfangGenerieren();
?>
<h2>Nachschreibe-Termine</h2>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
foreach($termine as $t) {
?>
  <form action="nachschreibetermin_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="alt" value="<?= $t->termin ?>" />
    <input type="hidden" name="ort" value="<?= htmlspecialchars($t->ort,ENT_QUOTES) ?>" />
  <tr>
    <td><?= $t->ort ?></td>
    <td><input type="datetime-local" name="termin" value="<?= $t->termin ?>" /></td>
    <td>
      <input type="submit" value="Änderung speichern" />
      <input type="button" value="Löschen" onclick="location.href='nachschreibetermin_loeschen.php?termin=<?= urlencode($t->termin) ?>';" />
      <a href="nachschreibetermin_csv.php?termin=<?= $t->termin ?>">Genehmigte als CSV</a>
    </td>
  </tr>
  </form>
<?php
}
?>
  <form action="nachschreibetermin_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="alt" value="" />
  <tr>
    <td><select name="ort">
      <option value="Mitte" <?= $ich->ort=='Mitte' ? 'selected' : '' ?>>Mitte</option>
      <option value="Neukölln" <?= $ich->ort=='Neukölln' ? 'selected' : '' ?>>Neukölln</option>
    </select></td>
    <td><input type="datetime-local" name="termin" value="<?= date('Y-m-d H:i',$naechstertermin) ?>" /></td>
    <td>
      <input type="submit" value="Hinzufügen" />
    </td>
  </tr>
  </form>
</table>

<h2>Nachschreiber</h2>
<style>
.ilaufgabe {
  writing-mode:vertical-lr; /* sideways-lr nicht von Chrome unterstützt */
  width:1.5em;
  padding:2px;
}
.aufgabeninhalt {
  text-align:center;
}
.aufgabeninhalt.ok a {
  color:green;
}
.aufgabeninhalt.nok,
.aufgabeninhalt.nok a {
  color:red;
}
.edit {
  cursor:pointer;
}
</style>
<script>
function note_bearbeiten(kursid,tnid,spalte,kopf,wert) {
  let neuerwert=prompt(kopf,wert);
  if(neuerwert===null || neuerwert===wert) return;
  speichern(kursid,tnid,spalte,neuerwert);
}
let nachschreibetermine=<?= json_encode($termine) ?>;
var nachschreibungen=<?= json_encode($nachschreibungen) ?>;
function nachschreibetermin_bearbeiten(kursid,tnid) {
  let wert=nachschreibungen[kursid][tnid].termin;
  let elem=document.getElementById('nachschreibetermin_'+kursid+'_'+tnid);
  const altHTML=elem.innerHTML;
  elem.innerHTML='';
  const ed=document.createElement('select');
  for(let t of nachschreibetermine) {
    let o=document.createElement('option');
    o.value=t.termin;
    o.innerText=t.wann;
    ed.appendChild(o);
  }
  ed.value=wert;
  ed.onkeydown=function(event) {
    if(event.key=='Escape') {
      elem.innerHTML=altHTML;
    } else if(event.key=='Enter') {
      speichern(kursid,tnid,'nachschreibetermin',ed.value,nachschreibetermin_gespeichert);
    }
  }
  ed.onchange=function() {
    speichern(kursid,tnid,'nachschreibetermin',ed.value,nachschreibetermin_gespeichert);
  }
  elem.appendChild(ed);
  ed.focus();
  elem.appendChild(document.createElement('br'));
  const ed2=document.createElement('input');
  ed2.type='datetime-local';
  ed2.value=wert.length<=0 ? '<?= date('Y-m-d H:i',strtotime('next thursday 12:30')) ?>' : wert;
  ed2.onkeydown=function(event) {
    if(event.key=='Escape') {
      elem.innerHTML=altHTML;
    } else if(event.key=='Enter') {
      speichern(kursid,tnid,'nachschreibetermin',ed2.value,nachschreibetermin_gespeichert);
    }
  };
  ed2.onchange=function() {
    speichern(kursid,tnid,'nachschreibetermin',ed2.value,nachschreibetermin_gespeichert);
  };
  elem.appendChild(ed2);
}
function nachschreibetermin_gespeichert(kursid,tnid,neuerWert) {
  let i=neuerWert.indexOf('=');
  nachschreibungen[kursid][tnid].termin=neuerWert.substring(0,i);
  document.getElementById('nachschreibetermin_'+kursid+'_'+tnid).innerText=neuerWert.substring(i+1);
}
var nachschreibestatusse=<?= json_encode($nachschreibestatusse) ?>;
function nachschreibestatus_bearbeiten(kursid,tnid) {
  let wert=nachschreibungen[kursid][tnid].status;
  let elem=document.getElementById('nachschreibestatus_'+kursid+'_'+tnid);
  const altHTML=elem.innerHTML;
  elem.innerHTML='';
  let ed=document.createElement('select');
  for(let s in nachschreibestatusse) {
    let o=document.createElement('option');
    o.value=s;
    o.innerText=s;
    ed.appendChild(o);
  }
  ed.value=wert;
  ed.onchange=function() {
    speichern(kursid,tnid,'nachschreibestatus',this.value,nachschreibestatus_gespeichert);
  }
  ed.onkeydown=function(event) {
    if(event.key=='Escape') {
      elem.innerHTML=altHTML;
    } else if(event.key=='Enter') {
      speichern(kursid,tnid,'nachschreibestatus',this.value,nachschreibestatus_gespeichert);
    }
  }
  elem.appendChild(ed);
  ed.focus();
}
let tns=<?= json_encode($tns) ?>;
let kurse=<?= json_encode($kurse) ?>;
function nachschreibestatus_gespeichert(kursid,tnid,neuerWert) {
  nachschreibungen[kursid][tnid].status=neuerWert;
  let td=document.getElementById('nachschreibestatus_'+kursid+'_'+tnid);
  td.style.backgroundColor=nachschreibestatusse[neuerWert];
  td.innerHTML='<div onclick="nachschreibestatus_bearbeiten('+kursid+','+tnid+')">&#x270E; '+neuerWert+'</div>';
  if(neuerWert=='genehmigt') {
    let kurs=kurse.byId[kursid];
    let tn=tns.byId[tnid];
    let termin=document.getElementById('termin_'+kursid+'_'+tnid).innerText;
    location.href='mailto:'+tn.email
      +'?subject='+encodeURIComponent('Nachschreiben am '+termin+' Uhr für den Kurs '+kurs.titel+' genehmigt')
      +'&body='+encodeURIComponent('Sehr geehrte'+(tn.anrede=='Herr' ? 'r' : tn.anrede=='Frau' ? '' : '/r')+' '+(tn.anrede ? tn.anrede : tn.vorname)+' '+tn.nachname+',\n'
      +'\n'
      +'Ihr Nachschreibetermin wurde genehmigt:\n'
      +'Kurs: '+kurs.titel+'\n'
      +'Termin: '+termin+' Uhr\n'
      +'Ort: GPB '+kurs.ort+'\n'
      +'\n'
      +'Mit freundlichen Grüßen\n'
      +'Ihr GPB-Team');
  } else if(neuerWert=='abgelehnt') {
    let kurs=kurse.byId[kursid];
    let tn=tns.byId[tnid];
    let termin=document.getElementById('termin_'+kursid+'_'+tnid).innerText;
    location.href='mailto:'+tn.email
      +'?subject='+encodeURIComponent('Nachschreiben für den Kurs '+kurs.titel+' abgelehnt')
      +'&body='+encodeURIComponent('Sehr geehrte'+(tn.anrede=='Herr' ? 'r' : tn.anrede=='Frau' ? '' : '/r')+' '+(tn.anrede ? tn.anrede : tn.vorname)+' '+tn.nachname+',\n'
      +'\n'
      +'Ihrem Antrag auf eine Nachklausur für den Kurs\n'
      +kurs.titel+'\n'
      +'können wir leider nicht zustimmen, da uns keine Krankschreibung vorliegt.\n'
      +'Sollten Sie einen wichtigen Grund für die Nicht-Teilnahme haben, dann melden Sie sich bitte beim Kundenservice.\n'
      +'\n'
      +'Mit freundlichen Grüßen\n'
      +'Ihr GPB-Team');
  }
}
function speichern(kursid,tnid,spalte,wert,callback) {
  let req=new XMLHttpRequest();
  req.onreadystatechange = function () {
    if(this.readyState != 4) return;
    if(this.responseText.startsWith('OK')) {
      let r=this.responseText.substring(2);
      let elem=document.getElementById(spalte+'_'+kursid+'_'+tnid);
      if(elem.tagName.toLowerCase()=='input' && elem.type.toLowerCase()=='checkbox') {
        elem.checked=wert==1;
      } else if(callback) {
        callback(kursid,tnid,r);
      } else {
        elem.innerHTML='&#x270E; '+r;
      }
    } else {
      let elem=document.getElementById(spalte+'_'+kursid+'_'+tnid);
      if(elem.tagName.toLowerCase()=='input' && elem.type.toLowerCase()=='checkbox') {
        elem.checked=wert!=1;
      }
      alert(this.responseText);
    }
  };
  req.open('GET','kurs_notenteil_speichern.php?kursid='+kursid+'&tnid='+tnid+'&spalte='+spalte+'&wert='+encodeURIComponent(wert));
  req.send();
}
function aufgabe_erstellen(kursid,kursmoodleid) {
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
    document.getElementById('nachklausurtd_'+kursid).innerHTML='<a href="'+(aufgabe.url.startsWith('http') ? '' : '<?= $moodleurl ?>')+aufgabe.url+'" target="moodle">'+aufgabe.titel+' (kein Inhalt)'+(aufgabe.sichtbar ? '' : ' (verborgen)')+'</a>';
  };
  let daten={
    'courseid':kursmoodleid,
    'kapitelnummer':-1,
    'kapiteltitel':'Klausur und Nachklausur',
    'aufgabentitel':'Nachklausur',
    'benachrichtigungen':1,
    'sichtbar':0
  };
  req.open('GET','<?= $moodleurl ?>webservice/rest/server.php?wstoken=<?= $moodletoken ?>&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=aufgabe_erstellen&daten='+encodeURIComponent(JSON.stringify(daten)));
  req.send();
}
function aufgabe_freischalten(kursid,tnid,kursmoodleid,modname,cmid,cminstance) {
  let req=new XMLHttpRequest();
  req.onreadystatechange = function () {
    if(this.readyState != 4) return;
    let ergebnis=JSON.parse(this.responseText);
    if(ergebnis=='ok' || ergebnis=='"ok"') {
      alert('Nachklausur sichtbar gemacht!'
        +'\nTermin:'+nachschreibungen[kursid][tnid].termin);
      location.href=location.href;
    } else {
      alert(this.responseText);
    }
  };
  let daten={
    'courseid':kursmoodleid,
    'modname':modname,
    'cmid':cmid,
    'cminstance':cminstance,
    'termin':nachschreibungen[kursid][tnid].termin,
    'dauer':120
  };
  req.open('GET','<?= $moodleurl ?>webservice/rest/server.php?wstoken=<?= $moodletoken ?>&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=aufgabe_freischalten&daten='+encodeURIComponent(JSON.stringify(daten)));
  req.send();
}
</script>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Kurs<br /><a href="nachschreiber_sortieren.php?kriterium=k.titel">sortieren</a></th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Nachklausur</th>
    <th>Dozenten</th>
    <th>TN<br /><a href="nachschreiber_sortieren.php?kriterium=tn.nachname">sortieren</a></th>
    <th>Fehlt</th>
    <th>Punkte</th>
    <th colspan="2">Nachschreibe-<br />Termin &nbsp; <a href="nachschreiber_sortieren.php?kriterium=n.nachschreibetermin">sortieren</a></th>
    <th>Nachschreiben</th>
    <th>Nachpunkte</th>
  </tr>
<?php
foreach($nachschreiber_offen as $n) {
  makeTr($n);
}
?>
  <tr>
    <td colspan="10">&nbsp;</td>
  </tr>
  <tr>
    <th>Kurs<br /><a href="nachschreiber_sortieren.php?kriterium=k.titel">sortieren</a></th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Nachklausur</th>
    <th>Dozenten</th>
    <th>TN<br /><a href="nachschreiber_sortieren.php?kriterium=tn.nachname">sortieren</a></th>
    <th>Fehlt</th>
    <th>Punkte</th>
    <th colspan="2">Nachschreibe-<br />Termin &nbsp; <a href="nachschreiber_sortieren.php?kriterium=n.nachschreibetermin">sortieren</a></th>
    <th>Nachschreiben</th>
    <th>Nachpunkte</th>
  </tr>
<?php
foreach($nachschreiber_fertig as $n) {
  makeTr($n);
}
?>
</table>
<?php
$seite->endeGenerieren();
?>