<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungKurs.php';
require_once 'VerwaltungKlasse.php';
require_once 'VerwaltungTn.php';
require_once 'anwesenheitsArten.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'VerwaltungKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}

$klassenById=array();
foreach($kurs->klassen as $k) {
  $klassenById[$k->id]=$k;
}
$stmt=empty($kurs->klassenids) ? null : $db->prepare("select distinct tn.*,ktn.klasseid
  from gpb_klasse_tn ktn
  join gpb_tn tn on tn.id=ktn.tnid
  where ktn.klasseid in(".implode(',',$kurs->klassenids).")
  and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=curdate())
  and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=curdate()) order by tn.nachname,tn.vorname");
$tns=new Liste('VerwaltungTn',$stmt);
foreach($tns->alle as $tn) {
  $tn->klassen[]=$klassenById[$tn->klasseid];
}

$heute=strtotime('today');
$von=empty($kurs->von) ? (date('D',$heute)=='Mon' ? $heute : strtotime('last monday',$heute)) : $kurs->von;
$bis=min($heute,empty($kurs->bis) ? strtotime('+4 days',$von) : $kurs->bis);
$tagnamen=array('Mon'=>'Mo','Tue'=>'Di','Wed'=>'Mi','Thu'=>'Do','Fri'=>'Fr','Sat'=>'Sa','Sun'=>'So');
$tage=array();
for($wann=$von;$wann<=$bis;$wann=strtotime('+1 day',$wann)) {
  $t=$tagnamen[date('D',$wann)];
  if($t!='Sa' && $t!='So') {
    $tage[date('Y-m-d',$wann)]=$t.' '.date('d.m.Y',$wann);
  }
}

if(!empty($tns->byId)) {
  $result=$db->query("select * from gpb_anwesenheit where tag>='".date('Y-m-d',$von)."' and tag<='".date('Y-m-d',$bis)."' and tnid in(".implode(',',array_keys($tns->byId)).")");
  while($row=$result->fetch_object()) {
    $row->anfangTitel=$anwesenheitsArten[$row->anfang];
    $row->endeTitel=$anwesenheitsArten[$row->ende];
    $row->kguTitel=$anwesenheitsArten[$row->kgu];
    $tns->byId[$row->tnid]->anwesenheiten[$row->tag]=$row;
  }
  $result->free();
}
$leereAnwesenheit=(object)array('anfang'=>'','ende'=>'','kgu'=>'');

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Kurs-Anwesenheit');
$seite->anfangGenerieren('');
$kurs->makeSehen();
if(empty($kurs->klassen)) {
  echo 'Keine Klassen.';
} else if(empty($tns->alle)) {
  echo 'Keine TN.';
} else {
?>
<script type="module">
const anwesenheitsArten=<?= json_encode($anwesenheitsArten) ?>;
const buchstaben=<?= json_encode(array_keys($anwesenheitsArten)) ?>;
const table=document.getElementById('anw_table');
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
document.body.onkeyup=function(event) {
  if(!markiertesTD) return;
  let k=event.key.toUpperCase();
  if(k=='DELETE' || k=='BACKSPACE' || k=='*' || k=='-' || k=='+' || k==' ' || buchstaben.indexOf(k)>=0) {
    const nval=k=='*' ? 'A' : k=='+' ? 'O' : k=='-' ? 'F' : k=='X' ? event.key : k.length==1 ? k : ' ';
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
      if(itr>=0 && itr-1>=2) {
        setMarkiertesTD(table.rows[itr-1].cells[markiertesTD.cellIndex]);
      }
    } else {
      if(itr>=0 && itr+1<table.rows.length) {
        setMarkiertesTD(table.rows[itr+1].cells[markiertesTD.cellIndex]);
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
    if(itr>=0 && itr+1<table.rows.length) {
      setMarkiertesTD(table.rows[itr+1].cells[markiertesTD.cellIndex]);
    }
  } else if(k=='ARROWUP') {
    let itr=markiertesTD.closest('tr').rowIndex;
    if(itr>=0 && itr-1>=2) {
      setMarkiertesTD(table.rows[itr-1].cells[markiertesTD.cellIndex]);
    }
//  } else {
//    alert(event.key);
  }
}
window.anwesenheit_aendern=function(tnid,tag,moment) {
  const td=document.getElementById('anw_'+tnid+'_'+tag+'_'+moment);
  setMarkiertesTD(td);
  const val=td.innerText;
  let idx=buchstaben.indexOf(val);
  const nval=buchstaben[(idx+1)%buchstaben.length];
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText.startsWith('OK')) {
       td.innerText=nval;
        td.title=anwesenheitsArten[nval];  
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
</style>
<table id="anw_table" border="1" cellspacing="0" style="border-collapse:collapse;margin-top:1em;">
  <tr>
    <th rowspan="3">Teilnehmer</th>
<?php
foreach($tage as $datum=>$tag) {
?>
    <th colspan="<?= substr($tag,0,2)=='Fr' ? 2 : 3 ?>"><?= substr($tag,0,2)=='Mo' ? 'KW '.date('W',strtotime($datum)) : '' ?></th>
<?php
}
?>
  </tr>
  <tr>
<?php
foreach($tage as $datum=>$tag) {
?>
    <th colspan="<?= substr($tag,0,2)=='Fr' ? 2 : 3 ?>"><?= $tag ?></th>
<?php
}
?>
  </tr>
  <tr>
<?php
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
  <tr>
    <td><a href="tn_sehen.php?tnid=<?= $tn->id ?>"><?= $tn->anrede ?> <?= $tn->vorname ?> <?= $tn->nachname ?></a></td>
<?php
  foreach($tage as $datum=>$tag) {
    $anw=isset($tn->anwesenheiten[$datum]) ? $tn->anwesenheiten[$datum] : $leereAnwesenheit;
?>
    <td class="anwesenheit" id="anw_<?= $tn->id ?>_<?= $datum ?>_anfang" onclick="anwesenheit_aendern(<?= $tn->id ?>,'<?= $datum ?>','anfang')" title="<?= htmlspecialchars($anw->anfangTitel,ENT_QUOTES) ?>"><?= $anw->anfang ?></td>
    <td class="anwesenheit" id="anw_<?= $tn->id ?>_<?= $datum ?>_ende" onclick="anwesenheit_aendern(<?= $tn->id ?>,'<?= $datum ?>','ende')" title="<?= htmlspecialchars($anw->endeTitel,ENT_QUOTES) ?>"><?= $anw->ende ?></td>
<?php
    if(substr($tag,0,2)!='Fr') {
?>
    <td class="anwesenheit" id="anw_<?= $tn->id ?>_<?= $datum ?>_kgu" onclick="anwesenheit_aendern(<?= $tn->id ?>,'<?= $datum ?>','kgu')" title="<?= htmlspecialchars($anw->kguTitel,ENT_QUOTES) ?>"><?= $anw->kgu ?></td>
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