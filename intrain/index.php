<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'IntrainTn.php';

$heute=strtotime('today');
$von=date('D',$heute)=='Mon' ? $heute : strtotime('last monday',$heute);
$bis=min($heute,strtotime('+4 days',$von));
$tagnamen=array('Mon'=>'Mo','Tue'=>'Di','Wed'=>'Mi','Thu'=>'Do','Fri'=>'Fr','Sat'=>'Sa','Sun'=>'So');
$tage=array();
for($wann=$von;$wann<=$bis;$wann=strtotime('+1 day',$wann)) {
  $tage[date('Y-m-d',$wann)]=$tagnamen[date('D',$wann)].' '.date('d.m.Y',$wann);
}

$ferien=array();
$result=$db->query("select * from gpb_ferien where beginn<='".date('Y-m-d',$bis)."' and ende>='".date('Y-m-d',$von)."' and art in('Feiertag','GPB Ferien')");
while($row=$result->fetch_object()) {
  $row->von=strtotime($row->beginn);
  $row->bis=strtotime($row->ende);
  for($t=max($row->von,$von);$t<=min($row->bis,$bis);$t=strtotime('+1 day',$t)) {
    $ferien[date('Y-m-d',$t)]=$row;
  }
}
$result->free();

if(empty($ich->intraintnsortierung)) {
  $ich->intraintnsortierung='nachname,vorname';
}
$stmt=$db->prepare("select * 
  from gpb_tn_view 
  where id in(select mtn.tnid 
    from gpb_massnahme_tn mtn
    join gpb_massnahme m on m.id=mtn.massnahmeid and m.kuerzel like '%MODULAR%' and m.titel not like '%Grundkompetenz%'
    where (mtn.einstieg<>'0000-00-00' and mtn.einstieg is not null and mtn.einstieg<=?) and (mtn.ausstieg='0000-00-00' or mtn.ausstieg is null or mtn.ausstieg>=?)) 
  or id in(select sktn.tnid
    from gpb_selfkurs_tn sktn
    where (sktn.einstieg<>'0000-00-00' and sktn.einstieg is not null and sktn.einstieg<=?) and (sktn.ausstieg='0000-00-00' or sktn.ausstieg is null or sktn.ausstieg>=?))
  order by ".$ich->intraintnsortierung);
$beginn=date('Y-m-d',$von);
$ende=date('Y-m-d',$bis);
$stmt->bind_param('ssss',$ende,$beginn,$ende,$beginn);
$tns=new Liste('IntrainTn',$stmt);
IntrainTn::refsLaden($tns);

$tnarten=array();
if(!empty($tns->byId)) {
  foreach($tns->alle as $tn) {
    $tnarten[$tn->id]=(object)array(
      'intrain'=>false,'klassen'=>false,'gruko'=>false,'spezialist'=>false,'andere'=>true,'hatselfkurse'=>(count($tn->selfkurse)>0)
    );
    foreach($tn->massnahmen as $massn) {
      if(mb_strpos($massn->titel,'Kurs')!==false
        || mb_strpos($massn->titel,'inTrain')!==false) {
          $tnarten[$tn->id]->intrain=true;
          $tnarten[$tn->id]->andere=false;
      }
      if($massn->kuerzel!='MODULAR') {
          $tnarten[$tn->id]->klassen=true;
          $tnarten[$tn->id]->andere=false;
      }
      if(mb_strpos($massn->titel,'Grundkompetenzen')!==false) {
          $tnarten[$tn->id]->gruko=true;
          $tnarten[$tn->id]->andere=false;
      }
      if(mb_strpos($massn->titel,'Fachkraft')!==false
        || mb_strpos($massn->titel,'Spezialist')!==false
        || mb_strpos($massn->titel,'Fachkräfte')!==false
        || mb_strpos($massn->titel,'Manager')!==false) {
          $tnarten[$tn->id]->spezialist=true;
          $tnarten[$tn->id]->andere=false;
      }
    }
  }
  
  $result=$db->query("select * from gpb_anwesenheit where tag>='".date('Y-m-d',$von)."' and tag<='".date('Y-m-d',$bis)."' and tnid in(".implode(',',array_keys($tns->byId)).")");
  while($row=$result->fetch_object()) {
    $tns->byId[$row->tnid]->anwesenheiten[$row->tag]=$row;
  }
  $result->free();
}
$leereAnwesenheit=(object)array('anfang'=>'','ende'=>'','kgu'=>'');

require_once 'IntrainSeite.php';
$seite=new IntrainSeite('Anwesenheit KW '.date('W'));
$seite->anfangGenerieren();
if(empty($tns->alle)) {
?>
Keine Teilnehmer gefunden.
<?php
} else {
?>
<script type="module">
const table=document.getElementById('anw_table');
window.spalten_filtern=function() {
  let display=document.getElementById('cb_massnahmen').checked ? '' : 'none';
  for(let i=0,di=1;i<table.rows.length;i+=di) {
    di=parseInt(table.rows[i].cells[1].rowSpan) || 1;
    table.rows[i].cells[1].style.display=display;
  }
  display=document.getElementById('cb_selfkurse').checked ? '' : 'none';
  for(let i=0,di=1;i<table.rows.length;i+=di) {
    di=parseInt(table.rows[i].cells[2].rowSpan) || 1;
    table.rows[i].cells[2].style.display=display;
  }
}
spalten_filtern();
window.tnarten=<?= json_encode($tnarten) ?>;
window.tn_filtern=function() {
  let intrain=document.getElementById('cb_intrain').checked;
  let klassen=document.getElementById('cb_klassen').checked;
  let gruko=document.getElementById('cb_gruko').checked;
  let spezialisten=document.getElementById('cb_spezialisten').checked;
  for(let i=2,di=1;i<table.rows.length;i+=di) {
    let tnid=table.rows[i].id.substring(3);
    let arten=tnarten[tnid];
    let display=arten.andere 
      || (intrain && arten.intrain)
      || (klassen && arten.klassen)
      || (gruko && arten.gruko)
      || (spezialisten && arten.spezialist) ? '' : 'none';
    di=parseInt(table.rows[i].cells[1].rowSpan) || 1;
    for(let j=0;j<di;++j) {
      table.rows[i+j].style.display=display;
    }
  }
}
tn_filtern();
var markiertesTD=null;
function setMarkiertesTD(td) {
  if(markiertesTD) {
    markiertesTD.classList.remove('markiert');
  }
  markiertesTD=td;
  if(markiertesTD) {
    markiertesTD.classList.add('markiert');
  }
}
for(let i=2;i<table.rows.length;++i) {
  if(table.rows[i].style.display) continue;
  let tnid=table.rows[i].id.substring(3);
  setMarkiertesTD(document.getElementById('anw_'+tnid+'_<?= date('Y-m-d') ?>_<?= (int)date('H')<12 ? 'anfang' : 'ende' ?>'));
  break;
}
document.body.onkeyup=function(event) {
  if(!markiertesTD) return;
  let k=event.key.toUpperCase();
  if(k=='DELETE' || k=='BACKSPACE' || k==' ' || k=='A' || k=='*' || k=='F' || k=='-' || k=='O' || k=='+') {
    const nval=k=='*' ? 'A' : k=='+' ? 'O' : k=='-' ? 'F' : k.length==1 ? k : ' ';
    const td=markiertesTD;
    let data=td.id.split('_');
    const req=new XMLHttpRequest();
    req.onreadystatechange = function() {
      if (req.readyState != 4) return;
      if (req.responseText.startsWith('OK')) {
        td.innerText=nval;
      } else {
        alert(req.responseText);
      }
    };
    req.open('GET','anwesenheit_speichern.php?tnid='+data[1]+'&tag='+data[2]+'&moment='+data[3]+'&val='+nval);
    req.send();
    let itr=markiertesTD.closest('tr').rowIndex;
    if(k=='BACKSPACE') {
      --itr;
      while(itr>=2 && table.rows[itr].style.display=='none') --itr;
      if(itr>=2) {
        setMarkiertesTD(table.rows[itr].cells[markiertesTD.cellIndex]);
      }
    } else if(itr>=0) {
      ++itr;
      while(itr<table.rows.length && table.rows[itr].style.display=='none') ++itr;
      if(itr<table.rows.length) {
        setMarkiertesTD(table.rows[itr].cells[markiertesTD.cellIndex]);
      }
    }
  } else if(k=='ARROWRIGHT') {
    let tr=markiertesTD.closest('tr');
    if(markiertesTD.cellIndex+1<tr.cells.length) {
      setMarkiertesTD(tr.cells[markiertesTD.cellIndex+1]);
    }
  } else if(k=='ARROWLEFT') {
    let tr=markiertesTD.closest('tr');
    if(markiertesTD.cellIndex-1>=2) {
      setMarkiertesTD(tr.cells[markiertesTD.cellIndex-1]);
    }
  } else if(k=='ARROWDOWN' || k=='ENTER') {
    let itr=markiertesTD.closest('tr').rowIndex;
    if(itr>=0) {
      ++itr;
      while(itr<table.rows.length && table.rows[itr].style.display=='none') ++itr;
      if(itr<table.rows.length) {
        setMarkiertesTD(table.rows[itr].cells[markiertesTD.cellIndex]);
      }
    }
  } else if(k=='ARROWUP') {
    let itr=markiertesTD.closest('tr').rowIndex;
    --itr;
    while(itr>=2 && table.rows[itr].style.display=='none') --itr;
    if(itr>=2) {
      setMarkiertesTD(table.rows[itr].cells[markiertesTD.cellIndex]);
    }
//  } else {
//    alert(event.key);
  }
}
window.anwesenheit_aendern=function(tnid,tag,moment) {
  const td=document.getElementById('anw_'+tnid+'_'+tag+'_'+moment);
  setMarkiertesTD(td);
  const val=td.innerText;
  const nval=val=='A' ? 'F' : (val=='F' ? 'O' : (val=='O' ? '' : 'A'));
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText.startsWith('OK')) {
       td.innerText=nval;
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','anwesenheit_speichern.php?tnid='+tnid+'&tag='+tag+'&moment='+moment+'&val='+nval);
  req.send();
}
</script>
<style>
.markiert {
  border:2px solid black;
}
tr:has(> .markiert) td {
  background-color:rgb(255,206,127);
}
tr:has(> td:hover) td {
  background-color:rgb(255,231,192);
}
</style>
<b>Spalten:</b>
<input type="checkbox" id="cb_massnahmen" onchange="spalten_filtern()" />&nbsp;Maßnahmen
<input type="checkbox" id="cb_selfkurse" onchange="spalten_filtern()" />&nbsp;inTrain-Kurse<br />
<b>Teilnehmer:</b>
<input type="checkbox" id="cb_intrain" checked onchange="tn_filtern()" />&nbsp;inTrain-TN
<input type="checkbox" id="cb_klassen" checked onchange="tn_filtern()" />&nbsp;Klassen-TN
<input type="checkbox" id="cb_gruko" onchange="tn_filtern()" />&nbsp;Gruko-TN
<input type="checkbox" id="cb_spezialisten" onchange="tn_filtern()" />&nbsp;Fachkräfte und Spezialisten
<table id="anw_table" border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Teilnehmer</th>
    <th rowspan="2">Maßnahmen</th>
    <th rowspan="2">inTrain-Kurse</th>
<?php
foreach($tage as $datum=>$tag) {
  $f=isset($ferien[$datum]) ? $ferien[$datum] : false;
?>
    <th colspan="<?= substr($tag,0,2)=='Fr' ? 2 : 3 ?>" <?= $f ? 'class="ferien"' : '' ?>><?= $tag ?><?= $f ? "<br />\n".$f->anlass : '' ?></th>
<?php
}
?>
  </tr>
  <tr>
    <td><a href="intraintnsortierung_aendern.php?url=index.php">Sortierung: <?= $ich->intraintnsortierung=='vorname,nachname' ? 'VN,NN' : 'NN,VN' ?></a></td>
<?php
foreach($tage as $datum=>$tag) {
  $f=isset($ferien[$datum]) ? $ferien[$datum] : false;
?>
    <th <?= $f ? 'class="ferien"' : '' ?>>Anfang</th>
    <th <?= $f ? 'class="ferien"' : '' ?>>Ende</th>
<?php
  if(substr($tag,0,2)!='Fr') {
?>
    <th <?= $f ? 'class="ferien"' : '' ?>>KGU</th>
<?php
  }
}
?>
  </tr>
<?php
foreach($tns->alle as $tn) {
?>
  <tr id="tr_<?= $tn->id ?>">
    <td><a href="tn_sehen.php?tnid=<?= $tn->id ?>"><?= $tn->anrede ?> <?=$tn->vorname ?> <?= $tn->nachname ?></a></td>
<?php
  $tn->makeMassnahmenTd();
  $tn->makeSelfkurseTd();
  foreach($tage as $datum=>$tag) {
    $f=isset($ferien[$datum]) ? $ferien[$datum] : false;
    $anw=isset($tn->anwesenheiten[$datum]) ? $tn->anwesenheiten[$datum] : $leereAnwesenheit;
?>
    <td class="anwesenheit<?= $f ? ' ferien' : '' ?>" id="anw_<?= $tn->id ?>_<?= $datum ?>_anfang" onclick="anwesenheit_aendern(<?= $tn->id ?>,'<?= $datum ?>','anfang')"><?= $anw->anfang ?></td>
    <td class="anwesenheit<?= $f ? ' ferien' : '' ?>" id="anw_<?= $tn->id ?>_<?= $datum ?>_ende" onclick="anwesenheit_aendern(<?= $tn->id ?>,'<?= $datum ?>','ende')"><?= $anw->ende ?></td>
<?php
    if(substr($tag,0,2)!='Fr') {
?>
    <td class="anwesenheit<?= $f ? ' ferien' : '' ?>" id="anw_<?= $tn->id ?>_<?= $datum ?>_kgu" onclick="anwesenheit_aendern(<?= $tn->id ?>,'<?= $datum ?>','kgu')"><?= $anw->kgu ?></td>
<?php
    }
  }
?>
  </tr>
<?php
}
?>
</table>
<?php  
}
?>
<?php
$seite->endeGenerieren();
?>