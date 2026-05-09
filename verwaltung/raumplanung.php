<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungRaum.php';
require_once 'VerwaltungKurs.php';

$ort=isset($_SESSION['raumplanung_ort']) ? $_SESSION['raumplanung_ort'] : (isset($ich->ort) ? $ich->ort : '');
$zusatz=isset($_SESSION['raumplanung_zusatz']) ? $_SESSION['raumplanung_zusatz'] : '';
$datum=isset($_SESSION['raumplanung_datum']) ? $_SESSION['raumplanung_datum'] : '';
if(empty($datum)) {
  $datum=date('Y-m-d',strtotime('next monday'));
}
$wann=strtotime($datum);


$stmt=$db->prepare("select * 
  from gpb_kurs_view 
  where beginn<=? and ende>=?"
  .(empty($ort) ? "" : " and ort=?")
  ." order by titel");
if(empty($ort)) {
  $stmt->bind_param('ss',$datum,$datum);
} else {
  $stmt->bind_param('sss',$datum,$datum,$ort);
}
$kurse=new Liste('VerwaltungKurs',$stmt);
Kurs::klassenLaden($kurse);
Kurs::anzahlTNLaden($kurse);


$raeume=new Liste('VerwaltungRaum',$db->prepare("select * from gpb_raum order by ort,tuer"));
$zusaetze=array();
foreach($raeume->alle as $raum) {
  $raum->kursids=array();
  $raum->anzahlTN=0;
  if(!empty($ort->zusatz)) {
    $zusaetze[$ort->zusatz]=true;
  }
}

foreach($kurse->alle as $kurs) {
  if(!empty($kurs->raumid)) {
    if(isset($raeume->byId[$kurs->raumid])) {
      $raeume->byId[$kurs->raumid]->kursids[]=$kurs->id;
      $raeume->byId[$kurs->raumid]->anzahlTN+=$kurs->anzahlTN;
    }
  }
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$titel='Raumplanung '.$ort.' '.date('d.m.Y',$wann).' (KW '.date('W',$wann).')';
$seite=$ich->istleiter ? new LeitungSeite($titel) : new VerwaltungSeite($titel);
$seite->anfangGenerieren();
?>
<style>
.nok {
  color:black;
  background-color:hsl(0,100%,90%);
}
.todo {
  color:black;
  background-color:hsl(30,100%,90%);
}
.ok {
  background-color:hsl(120,100%,90%);
}
</style>
<div>
<script>
function ort_ausgewaehlt() {
  let ort=document.getElementById('ort_select').value;
  location.href='raumplanung_ort_merken.php?ort='+encodeURIComponent(ort);
}
</script>
Ort: <select id="ort_select" onchange="ort_ausgewaehlt()">
  <option value="">(alle)</option>
  <option value="Mitte" <?= $ort=='Mitte' ? 'selected' : '' ?>>Mitte</option>
  <option value="Neukölln" <?= $ort=='Neukölln' ? 'selected' : '' ?>>Neukölln</option>
</select><br />
<script>
function zusatz_ausgewaehlt() {
  let zusatz=document.getElementById('zusatz_select').value;
  location.href='raumplanung_zusatz_merken.php?zusatz='+encodeURIComponent(zusatz);
}
</script>
Zusatz: <select id="zusatz_select" onchange="zusatz_ausgewaehlt()">
  <option value="">(alle)</option>
<?php
foreach($zusaetze as $z=>$dummy) {
?>
  <option value="<?= htmlentities($z,ENT_COMPAT) ?>" <?= $zusatz==$z ? 'selected' : '' ?>><?= $z ?></option>
<?php
}
?>
</select><br />
<script>
function datum_ausgewaehlt() {
  let datum=document.getElementById('datum_input').value;
  location.href='raumplanung_datum_merken.php?datum='+datum;
}
</script>
<input type="date" id="datum_input" value="<?= $datum ?>" onchange="datum_ausgewaehlt()" /> (KW <?= date('W',$wann) ?>)
</div>

<div style="margin-top:1em;">
<script>
const orte={
  '':'(keiner)'
  ,'Mitte':'Mitte'
  ,'Neukölln':'Neukölln'
};
const kurse=<?= json_encode($kurse->byId) ?>;
const raeume=<?= json_encode($raeume->byId) ?>;
var editoren=[];
var versteckte=[];
function stop_bearbeiten() {
  if(editoren.length>0) {
    for(let e of editoren) e.remove();
    editoren=[];
  }
  if(versteckte.length>0) {
    for(let v of versteckte) v.style.display='';
    versteckte=[];
  }
}
function kurs_ort_bearbeiten(kursid) {
  stop_bearbeiten();
  let kurs=kurse[kursid];
  let origtd=document.getElementById('ort_td_'+kursid);
  let td=document.createElement('td');
  let sel=document.createElement('select');
  for(let k in orte) {
    let o=document.createElement('option');
    o.value=k;
    o.innerText=orte[k];
    sel.appendChild(o);
  }  
  td.appendChild(sel);
  sel.value=kurs.ort;
  sel.onchange=()=>{
    kurs_ort_ausgewaehlt(kursid,sel.value);
  };
  let b=document.createElement('button');
  b.type='button';
  b.innerText='Abbrechen';
  b.onclick=stop_bearbeiten;
  td.appendChild(b);
  origtd.parentNode.insertBefore(td,origtd);
  editoren.push(td);
  origtd.style.display='none';
  versteckte.push(origtd);
}
function kurs_ort_ausgewaehlt(kursid,ort) {
  stop_bearbeiten();
  kurse[kursid].ort=ort;
  document.getElementById('ort_span_'+kursid).innerText=ort;
  kurs_raum_bearbeiten(kursid);
}
function kurs_raum_bearbeiten(kursid) {
  stop_bearbeiten();
  let kurs=kurse[kursid];
  let ort=kurs.ort;
  let origtd=document.getElementById('raum_td_'+kursid);
  let td=document.createElement('td');
  let sel=document.createElement('select');
  for(let raumid in raeume) {
    let raum=raeume[raumid];
    if(raum.ort!=ort) continue;
    let o=document.createElement('option');
    o.value=raum.id;
    o.innerText=(raum.kursids.length>0 ? '(besetzt) ' : '')+raum.tuer+' '+raum.art+(raum.anzahlPlaetze<0 ? '' : ' '+raum.anzahlPlaetze+' Plätze')+(raum.anzahlComputer<0 ? '' : ' '+raum.anzahlComputer+' Computer');
    sel.appendChild(o);
  }  
  td.appendChild(sel);
  sel.value=kurs.raumid;
  sel.onchange=()=>{
    kurs_raum_ausgewaehlt(kursid,sel.value);
  };
  let b=document.createElement('button');
  b.type='button';
  b.innerText='Abbrechen';
  b.onclick=stop_bearbeiten;
  td.appendChild(b);
  origtd.parentNode.insertBefore(td,origtd);
  editoren.push(td);
  origtd.style.display='none';
  versteckte.push(origtd);
}
async function kurs_raum_ausgewaehlt(kursid,raumid) {
  const response = await fetch('kurs_raum_speichern.php?kursid='+kursid+'&raumid='+raumid);
  if(!response.ok) {
    throw new Error(`Response status: ${response.status}`);
  }
  let txt=await response.text();
  if(txt!='OK') {
    console.log(response);
    alert(txt);
    return;
  }
  stop_bearbeiten();
  let kurs=kurse[kursid];
  if(kurs.raumid) {
    let origraum=raeume[kurs.raumid];
    let i=origraum.kursids.indexOf(kursid);
    if(i>=0) {
      origraum.kursids.splice(i,1);
    }
    let e=document.getElementById('kurs_a_'+kurs.raumid+'_'+kurs.id);
    if(e) e.remove();
    e=document.getElementById('kurs_klassen_'+kurs.raumid+'_'+kurs.id);
    if(e) e.remove();
    origraum.anzahlTN=parseInt(origraum.anzahlTN)-parseInt(kurs.anzahlTN);
    document.getElementById('anzahlTN_td_'+kurs.raumid).innerText=origraum.anzahlTN<=0 ? '' : origraum.anzahlTN;
    origraum.anzahlPlaetze=parseInt(origraum.anzahlPlaetze);
    if(origraum.anzahlPlaetze>=0 && origraum.anzahlPlaetze<origraum.anzahlTN) {
      document.getElementById('raum_tr_'+kurs.raumid).classList.add('nok');
    } else {
      document.getElementById('raum_tr_'+kurs.raumid).classList.remove('nok');
    }
  }
  let raum=raeume[raumid];
  kurs.raumid=raumid;
  kurs.ort=raum.ort;
  if(raum) {
    document.getElementById('raum_span_'+kursid).innerText=raum.tuer;
    document.getElementById('anzahlPlaetze_td_'+kursid).innerText=raum.anzahlPlaetze<0 ? '' : raum.anzahlPlaetze;
    document.getElementById('anzahlComputer_td_'+kursid).innerText=raum.anzahlComputer<0 ? '' : raum.anzahlComputer;
    raum.kursids.push(kursid);
    let e=document.createElement('a');
    e.id='kurs_a_'+kurs.raumid+'_'+kurs.id;
    e.style.display='block';
    e.href='kurs_sehen.php?kursid='+kursid;
    e.innerText=kurs.titel+'\n'+(kurs.modulid>0 ? kurs.modulkuerzel+' - '+kurs.modultitel : '(Kein Modul)');
    document.getElementById('kurse_td_'+raumid).appendChild(e);
    if(kurs.klassen.length>0) {
      e=document.createElement('div');
      e.id='kurs_klassen_'+kurs.raumid+'_'+kurs.id;
      for(let klasse of kurs.klassen) {
        let a=document.createElement('a');
        a.style.display='block';
        a.href='klasse_sehen.php?klasseid='+klasse.id;
        a.innerText=klasse.bezeichnung+' ('+klasse.anzahlTN+' TN)';
        e.appendChild(a);
      }
      document.getElementById('klassen_td_'+raumid).appendChild(e);
    }
    raum.anzahlTN=parseInt(raum.anzahlTN)+parseInt(kurs.anzahlTN);
    document.getElementById('anzahlTN_td_'+raumid).innerText=raum.anzahlTN<=0 ? '' : raum.anzahlTN;
    raum.anzahlPlaetze=parseInt(raum.anzahlPlaetze);
    if(raum.anzahlPlaetze>=0 && raum.anzahlPlaetze<raum.anzahlTN) {
      document.getElementById('kurs_tr_'+kursid).classList.add('nok');
      document.getElementById('raum_tr_'+raumid).classList.add('nok');
    } else {
      document.getElementById('kurs_tr_'+kursid).classList.remove('nok');
      document.getElementById('raum_tr_'+raumid).classList.remove('nok');
    }
    document.getElementById('kurs_tr_'+kursid).classList.remove('todo');
  } else {
    document.getElementById('kurs_tr_'+kursid).classList.remove('nok');
    document.getElementById('kurs_tr_'+kursid).classList.add('todo');
    document.getElementById('raum_span_'+kursid).innerText='';
    document.getElementById('anzahlPlaetze_td_'+kursid).innerText='';
    document.getElementById('anzahlComputer_td_'+kursid).innerText='';
  }
}
</script>
<h2>Kurse</h2>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>KW</th>
    <th>Beginn</th>
    <th>Ende</th>
    <th>Titel</th>
    <th>Klassen</th>
    <th>Anz. TN</th>
    <th>Ort</th>
    <th>Raum</th>
    <th>Anz. Plätze</th>
    <th>Anz. Computer</th>
    <th></th>
  </tr>
<?php
foreach($kurse->alle as $kurs) {
  $raum=isset($raeume->byId[$kurs->raumid]) ? $raeume->byId[$kurs->raumid] : null;
  $voll=$raum && $raum->anzahlPlaetze>=0 && $raum->anzahlTN>$raum->anzahlPlaetze;
?>
  <tr <?= $voll ? 'class="nok"' : (empty($kurs->raum) ? 'class="todo"' : '') ?> id="kurs_tr_<?= $kurs->id ?>">
<?php
    $kurs->makeZeitraumTds();
    $kurs->makeTitelTd();
    $kurs->makeKlassenTd();
    $kurs->makeAnzahlTNTd(true);
?>
    <td class="ort" id="ort_td_<?= $kurs->id ?>" onclick="kurs_ort_bearbeiten(<?= $kurs->id ?>)" style="cursor:pointer;">✎ <span id="ort_span_<?= $kurs->id ?>"><?= $kurs->ort ?></span></td>
    <td class="raum" id="raum_td_<?= $kurs->id ?>" onclick="kurs_raum_bearbeiten(<?= $kurs->id ?>)" style="cursor:pointer;">✎ <span id="raum_span_<?= $kurs->id ?>"><?= $kurs->raum ?></span><input type="hidden" id="raum_id_<?= $kurs->id ?>" value="<?= $kurs->raumid ?>" /></td>
<?php
    if($raum) {
?>
    <td align="center" id="anzahlPlaetze_td_<?= $kurs->id ?>"><?= $raum->anzahlPlaetze<0 ? '' : $raum->anzahlPlaetze ?></td>
    <td align="center" id="anzahlComputer_td_<?= $kurs->id ?>"><?= $raum->anzahlComputer<0 ? '' : $raum->anzahlComputer ?></td>
<?php
    } else {
?>
    <td align="center" id="anzahlPlaetze_td_<?= $kurs->id ?>"></td>
    <td align="center" id="anzahlComputer_td_<?= $kurs->id ?>"></td>
<?php
    }
?>
    <td>
      <a href="kurs_sehen.php?kursid=<?= $kurs->id ?>">Sehen</a>
      <a href="kurs_bearbeiten.php?kursid=<?= $kurs->id ?>">Bearbeiten</a>
      <a href="kurs_tn.php?kursid=<?= $kurs->id ?>">TN</a>
    </td>
  </tr>
<?php
}
?>
</table>
</div> <!-- Kurse -->

<div style="margin-top:1em;margin-bottom:1em;">
<h2>Räume</h2>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Ort</th>
    <th>Raum</th>
    <th>Zusatz</th>
    <th>Art</th>
    <th>Anz. Plätze</th>
    <th>Anz. Computer</th>
    <th>Kurs</th>
    <th>Klassen</th>
    <th>Anz. TN</th>
    <th></th>
  </tr>
<?php
foreach($raeume->alle as $raum) {
  if(!empty($ort) && $raum->ort!=$ort) continue;
  if(!empty($raum->zusatz) && !empty($zusatz) && $raum->zusatz!=$zusatz) continue;
  $voll=$raum->anzahlPlaetze>=0 && $raum->anzahlTN>$raum->anzahlPlaetze;
?>
  <tr id="raum_tr_<?= $raum->id ?>" <?= $voll ? 'class="nok"' : (empty($raum->kursids) ? '' : 'class="ok"') ?>>
    <td><?= $raum->ort ?></td>
    <td><?= $raum->tuer ?></td>
    <td><?= $raum->zusatz ?></td>
    <td><?= $raum->art ?></td>
    <td align="center"><?= $raum->anzahlPlaetze<0 ? '' : $raum->anzahlPlaetze ?></td>
    <td align="center"><?= $raum->anzahlComputer<0 ? '' : $raum->anzahlComputer ?></td>
    <td id="kurse_td_<?= $raum->id ?>" style="max-width:400px;">
<?php
  foreach($raum->kursids as $kursid) {
    $kurs=$kurse->byId[$kursid];
?>
      <a id="kurs_a_<?= $raum->id ?>_<?= $kurs->id ?>" href="kurs_sehen.php?kursid=<?= $kurs->id ?>" style="display:block;"><?= $kurs->titel."<br />".($kurs->modulid>0 ? $kurs->modulkuerzel.' - '.$kurs->modultitel : '(Kein Modul)') ?></a>
<?php
  }
?>
    </td>
    <td id="klassen_td_<?= $raum->id ?>">
<?php
  foreach($raum->kursids as $kursid) {
    $kurs=$kurse->byId[$kursid];
    if(!empty($kurs->klassen)) {
?>
      <div id="kurs_klassen_<?= $raum->id ?>_<?= $kurs->id ?>">
<?php
      foreach($kurs->klassen as $klasse) {
?>
        <a href="klasse_sehen.php?klasseid=<?= $klasse->id ?>" style="display:block;"><?= $klasse->bezeichnung ?> (<?= $klasse->anzahlTN ?> TN)</a>
<?php
      }
?>
      </div>
<?php
    }
  }
?>
    </td>
    <td id="anzahlTN_td_<?= $raum->id ?>" align="center"><?= $raum->anzahlTN<=0 ? '' : $raum->anzahlTN ?></td>
<?php
  $raum->makeBearbeitenTd();
?>
  </tr>
<?php
}
?>
</table>
</div> <!-- Räume -->
<a href="raeume.php">Zurück zur Räume-Seite</a>
<?php
$seite->endeGenerieren();
?>