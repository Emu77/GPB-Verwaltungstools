<?php
require_once 'check_login.php';

$fragen=array();
$fragenById=array();
$result=$db->query("select * from rm_klausur_frage order by id");
while($row=$result->fetch_object()) {
  $fragen[]=$row;
  $fragenById[$row->id]=$row;
}
$result->free();

$result=$db->query("select max(datum) as wann from rm_klausur");
$row=$result->fetch_object();
$result->free();
$datum=$row->wann;

$tns=array();
$tnsById=array();
$result=$db->query("select id,vorname,nachname from gpb_tn where id in(select distinct tnid from rm_klausur where datum='".$datum."') order by vorname,nachname");
while($row=$result->fetch_object()) {
  $row->antworten=array();
  $row->punkte=array();
  $tns[]=$row;
  $tnsById[$row->id]=$row;
}
$result->free();

$result=$db->query("select * from rm_klausur where datum='".$datum."' order by tnid");
while($row=$result->fetch_object()) {
  $tnsById[$row->tnid]->antworten[$row->fragenid]=$row->antwort;
  $tnsById[$row->tnid]->punkte[$row->fragenid]=$row->punkte;
}
$result->free();

require_once 'DozentSeite.php';
$seite=new DozentSeite('Klausur IT-Sicherheit '.date('d.m.Y',strtotime($datum)));
$seite->anfangGenerieren();
?>
<script>
var fragenids=<?= json_encode(array_keys($fragenById)) ?>;
var tns=<?= json_encode($tnsById) ?>;
var summe_maxpunkte=0;
var summe_punkte=0;
function tn_ausgewaehlt() {
  for(let fragenid of fragenids) {
    document.getElementById('antwort_'+fragenid).innerText=''; 
    document.getElementById('punkte_'+fragenid).value='0'; 
  }
  let tnid=document.getElementById('tn_sel').value;
  if(!tnid) return;
  let tn=tns[tnid];
  for(let fragenid in tn.antworten) {
    document.getElementById('antwort_'+fragenid).innerText=tn.antworten[fragenid]; 
  }
  let s=0.0;
  for(let fragenid in tn.punkte) {
    document.getElementById('punkte_'+fragenid).value=tn.punkte[fragenid];
    s+=parseFloat(tn.punkte[fragenid]);
  }
  summe_punkte=s;
  document.getElementById('summe_punkte').innerText=''+summe_punkte+' / '+summe_maxpunkte;
  let note=Math.round(100*s/summe_maxpunkte);
  document.getElementById('note').innerText=''+note+' / 100';
}
function maxpunkte_speichern(fragenid) {
  let maxpunkte=document.getElementById('maxpunkte_'+fragenid).value;
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if(req.readyState != 4) return;
    if(req.responseText.startsWith('OK')) {
      calc_maxpunkte();
      let tnid=document.getElementById('tn_sel').value;
      if(tnid) {
        document.getElementById('summe_punkte').innerText=''+summe_punkte+' / '+summe_maxpunkte;
        let note=Math.round(100*summe_punkte/summe_maxpunkte);
        document.getElementById('note').innerText=''+note+' / 100';
      }
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','rm_klausur_maxpunkte_speichern.php?fragenid='+fragenid+'&maxpunkte='+maxpunkte);
  req.send();
}
function calc_maxpunkte() {
  let s=0;
  for(let fragenid of fragenids) {
    s+=parseInt(document.getElementById('maxpunkte_'+fragenid).value);
  }
  summe_maxpunkte=s;
  document.getElementById('summe_maxpunkte').innerText=s;
}
function punkte_speichern(fragenid) {
  let tnid=document.getElementById('tn_sel').value;
  if(!tnid) return;
  let punkte=parseFloat(document.getElementById('punkte_'+fragenid).value);
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if(req.readyState != 4) return;
    if(req.responseText.startsWith('OK')) {
      summe_punkte+=punkte-(typeof tns[tnid].punkte[fragenid]=='undefined' ? 0 : tns[tnid].punkte[fragenid]);
      document.getElementById('summe_punkte').innerText=''+summe_punkte+' / '+summe_maxpunkte;
      let note=Math.round(100*summe_punkte/summe_maxpunkte);
      document.getElementById('note').innerText=''+note+' / 100';
      tns[tnid].punkte[fragenid]=punkte;
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','rm_klausur_punkte_speichern.php?tnid='+tnid+'&fragenid='+fragenid+'&punkte='+punkte);
  req.send();
}
</script>
<select id="tn_sel" onchange="tn_ausgewaehlt()">
  <option value="">-</option>
<?php
foreach($tns as $tn) {
?>
  <option value="<?= $tn->id ?>"><?= $tn->vorname ?> <?= $tn->nachname ?></option>
<?php
}
?>
</select>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
foreach($fragen as $f) {
?>
  <tr>
    <td><?= nl2br($f->frage) ?></td>
    <td><input type="number" id="maxpunkte_<?= $f->id ?>" value="<?= $f->maxpunkte ?>" style="width:50px;" onchange="maxpunkte_speichern(<?= $f->id ?>)" /></td>
    <td id="antwort_<?= $f->id ?>"></td>
    <td><input type="number" id="punkte_<?= $f->id ?>" value="" style="width:50px;" step="0.5" onchange="punkte_speichern(<?= $f->id ?>)" /></td>
  </tr>
<?php
}
?>
  <tr>
    <th>Summe</th>
    <td id="summe_maxpunkte"></td>
    <th id="note"> / 100</th>
    <td id="summe_punkte"></td>
  </tr>
</table>
<script>
calc_maxpunkte();
</script>
<?php
$seite->endeGenerieren();
?>