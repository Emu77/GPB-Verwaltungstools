<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'DozentKurs.php';
require_once 'DozentKlasse.php';
require_once 'DozentTn.php';
require_once 'DozentPVTermin.php';

$stmt=$db->prepare("select * from gpb_kurs_view where beginn<=curdate() and ende>=curdate() and (moodleid>0 or sichtbar) and id in(select kursid from gpb_kurs_dozent where dozentid=".$ich->id.")");
$kurse=new Liste('DozentKurs',$stmt);
Kurs::refsLaden($kurse);

$stmt=empty($kurse->byId) ? null : $db->prepare("select * from gpb_klasse where id in(select klasseid from gpb_kurs_klasse where kursid in(".implode(',',array_keys($kurse->byId)).")) order by bezeichnung");
$klassen=new Liste('DozentKlasse',$stmt);
foreach($klassen->alle as $klasse) {
  $klasse->tnids=array();
}

$stmt=empty($klassen->byId) ? null : $db->prepare("select distinct tn.*,ktn.klasseid
  from gpb_klasse_tn ktn
  join gpb_tn tn on tn.id=ktn.tnid
  where ktn.klasseid in(".implode(',',array_keys($klassen->byId)).")
  and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=curdate())
  and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=curdate()) order by ".$ich->tnsortierung);
$tns=new Liste('DozentTn',$stmt);
foreach($tns->alle as $tn) {
  $klasse=$klassen->byId[$tn->klasseid];
  $tn->klassen[]=$klasse;
  $klasse->tnids[]=$tn->id;
}
$kurstnids=array();
if(count($kurse->alle)>1) {
  foreach($kurse->alle as $kurs) {
    $kurstnids[$kurs->id]=array();
    foreach($kurs->klassen as $klasse) {
      $kurstnids[$kurs->id]=array_merge($kurstnids[$kurs->id],$klassen->byId[$klasse->id]->tnids);
    }
  }
}

$heute=strtotime('today');
$von=date('D',$heute)=='Mon' ? $heute : strtotime('last monday',$heute);
$bis=min($heute,strtotime('+4 days',$von));
$tagnamen=array('Mon'=>'Mo','Tue'=>'Di','Wed'=>'Mi','Thu'=>'Do','Fri'=>'Fr','Sat'=>'Sa','Sun'=>'So');
$tage=array();
for($wann=$von;$wann<=$bis;$wann=strtotime('+1 day',$wann)) {
  $tage[date('Y-m-d',$wann)]=$tagnamen[date('D',$wann)].' '.date('d.m.Y',$wann);
}

if(!empty($tns->byId)) {
  $result=$db->query("select * from gpb_anwesenheit where tag>='".date('Y-m-d',$von)."' and tag<='".date('Y-m-d',$bis)."' and tnid in(".implode(',',array_keys($tns->byId)).")");
  while($row=$result->fetch_object()) {
    $tns->byId[$row->tnid]->anwesenheiten[$row->tag]=$row;
  }
  $result->free();
}
$leereAnwesenheit=(object)array('anfang'=>'','ende'=>'','kgu'=>'');

$pvtermine=new Liste('DozentPVTermin',$db->prepare("select t.*,k.moodleid,k.hatProjektantrag
  from gpb_pruefungsvorbereitung_termin t
  join gpb_kurs_view k on k.id=t.kursid and k.id in(select kursid from gpb_kurs_dozent where dozentid=".$ich->id.") and (k.moodleid>0 or k.sichtbar)
  where ifnull(t.ende,t.beginn)>=current_date() and ifnull(t.ende,t.beginn)<=date_add(current_date(),interval 3 month)
  order by t.beginn,t.beginn_uhrzeit,t.ende,t.ende_uhrzeit"));
DozentPVTermin::refsLaden($pvtermine);

require_once 'DozentSeite.php';
$seite=Seite::$menueByUrl['index.php'];
$seite->anfangGenerieren();
if(!empty($kurse->alle)) {
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
<?php
DozentKurs::makeHeaderTr(false);
foreach($kurse->alle as $kurs) {
  $kurs->makeTr(false);
}
?>
</table>
<?php
}
if(!empty($pvtermine->alle)) {
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
<?php
DozentPVTermin::makeHeaderTr();
foreach($pvtermine->alle as $termin) {
  $termin->makeTr();
}
?>
</table>
<?php
}
if(empty($tns->alle)) {
?>
Keine Teilnehmer für Anwesenheit.
<?php
} else {
?>
<div style="font-weight:bold;margin:1em 0;">
  <?= count($klassen->alle)>1 ? 'Klassen' : 'Klasse' ?>: 
<?php
$erst=true;
foreach($klassen->alle as $k) {
  if($erst) $erst=false; else echo ' + ';
  echo $k->bezeichnung;
}
?>
  <br />
  KW <?= date('W') ?>
</div>
<script type="module">
const table=document.getElementById('anw_table');

<?php
if(count($kurse->alle)>1) {
?>
const kurstnids=<?= json_encode($kurstnids) ?>;
window.filter_tn=function() {
  for(let i=2;i<table.rows.length;++i) {
    table.rows[i].style.display='none';
  }
  for(let kursid in kurstnids) {
    if(document.getElementById('filter_'+kursid).checked) {
      for(let tnid of kurstnids[kursid]) {
        document.getElementById('tr_'+tnid).style.display='';
      }
    }
  }
}
<?php
}
?>

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
setMarkiertesTD(document.getElementById('anw_<?= $tns->alle[0]->id ?>_<?= date('Y-m-d') ?>_<?= (int)date('H')<12 ? 'anfang' : 'ende' ?>'));
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
      while(itr>=2 && table.rows[itr].style.display.toLowerCase()=='none') --itr;
      if(itr>=2) {
        setMarkiertesTD(table.rows[itr].cells[markiertesTD.cellIndex]);
      }
    } else if(itr>=0) {
      ++itr;
      while(itr<table.rows.length && table.rows[itr].style.display.toLowerCase()=='none') ++itr;
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
      while(itr<table.rows.length && table.rows[itr].style.display.toLowerCase()=='none') ++itr;
      if(itr<table.rows.length) {
        setMarkiertesTD(table.rows[itr].cells[markiertesTD.cellIndex]);
      }
    }
  } else if(k=='ARROWUP') {
    let itr=markiertesTD.closest('tr').rowIndex;
    --itr;
    while(itr>=2 && table.rows[itr].style.display.toLowerCase()=='none') --itr;
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
<?php
if(count($kurse->alle)>1) {
?>
<b>TN filtern</b><br />
<?php
  foreach($kurstnids as $kursid=>$tnids) {
?>
<input type="checkbox" id="filter_<?= $kursid ?>" checked onchange="filter_tn()" /> <?= $kurse->byId[$kursid]->titel ?><br />
<?php
  }
}
?>
<table id="anw_table" border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>TN</th>
<?php
if(count($klassen->alle)>1) {
?>
    <th>Klasse</th>
<?php
}
foreach($tage as $datum=>$tag) {
?>
    <th colspan="<?= substr($tag,0,2)=='Fr' ? 2 : 3 ?>"><?= $tag ?></th>
<?php
}
?>
  </tr>
  <tr>
    <td>Sortierung: <a href="tnsortierung_aendern.php?url=index.php&kriterium=vorname">VN</a> <a href="tnsortierung_aendern.php?url=index.php&kriterium=nachname">NN</a></td>
<?php
if(count($klassen->alle)>1) {
?>
    <td>Sortierung: <a href="tnsortierung_aendern.php?url=index.php&kriterium=ktn.klasseid">Klasse</a></td>
<?php
}
foreach($tage as $datum=>$tag) {
?>
    <th>Anfang</th>
    <th>Ende</th>
<?php
  if(substr($tag,0,2)!='Fr') {
?>
    <th>KGU</th>
<?php
  }
}
?>
  </tr>
<?php
foreach($tns->alle as $tn) {
?>
  <tr id="tr_<?= $tn->id ?>">
    <td><?= $tn->anrede ?> <?= $tn->vorname ?> <?= $tn->nachname ?></td>
<?php
  if(count($klassen->alle)>1) {
    $tn->makeKlassenTd();
  }
  foreach($tage as $datum=>$tag) {
    $anw=isset($tn->anwesenheiten[$datum]) ? $tn->anwesenheiten[$datum] : $leereAnwesenheit;
?>
    <td class="anwesenheit" id="anw_<?= $tn->id ?>_<?= $datum ?>_anfang" onclick="anwesenheit_aendern(<?= $tn->id ?>,'<?= $datum ?>','anfang')"><?= $anw->anfang ?></td>
    <td class="anwesenheit" id="anw_<?= $tn->id ?>_<?= $datum ?>_ende" onclick="anwesenheit_aendern(<?= $tn->id ?>,'<?= $datum ?>','ende')"><?= $anw->ende ?></td>
<?php
    if(substr($tag,0,2)!='Fr') {
?>
    <td class="anwesenheit" id="anw_<?= $tn->id ?>_<?= $datum ?>_kgu" onclick="anwesenheit_aendern(<?= $tn->id ?>,'<?= $datum ?>','kgu')"><?= $anw->kgu ?></td>
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
$seite->endeGenerieren();
exit;
?>