<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'IntrainTn.php';
require_once 'IntrainSelfkurs.php';
require_once '../Ferien.php';
require_once 'anwesenheitsArten.php';

$tn=IntrainTn::einenLaden(isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0,'IntrainTn');
if(empty($tn)) {
  header('Location:tn.php');
  exit;
}
$tn->ferienLaden();
$ferienByTag=array();
foreach($tn->ferien as $fer) {
  for($d=max($fer->von,$tn->von);$d<=min($fer->bis,$tn->bis);$d=strtotime('+1 day',$d)) {
    $ferienByTag[date('Y-m-d',$d)]=$fer;
  }
}

$selfkurseByKW=array();
$heute=strtotime('today');
$defvon=strtotime('+10 years');
$von=$defvon;
$bis=0;
foreach($tn->selfkurse as $k) {
  if(!empty($k->von) && !empty($k->bis)) {
    for($wann=$k->von;$wann<=$k->bis;$wann=strtotime('+1 week',$wann)) {
      $kw=date('Y-W',$wann);
      if(isset($selfkurseByKW[$kw])) {
        $selfkurseByKW[$kw][]=$k;
      } else {
        $selfkurseByKW[$kw]=array($k);
      }
    }
  }
  if(!empty($k->von) && $k->von<$von) {
    $von=$k->von;
  }
  if(!empty($k->bis) && $k->bis>$bis) {
    $bis=$k->bis;
  }
}
if($von==$defvon) {
  $von=$heute;
}
if(date('D',$von)!='Mon') {
  $von=strtotime('last monday',$von);
}
$bis=min($heute,$bis==0 ? strtotime('+4 days',$von) : $bis);

$tagnamen=array('Mon'=>'Mo','Tue'=>'Di','Wed'=>'Mi','Thu'=>'Do','Fri'=>'Fr','Sat'=>'Sa','Sun'=>'So');
$tage=array();
for($wann=$von;$wann<=$bis;$wann=strtotime('+1 day',$wann)) {
  $t=$tagnamen[date('D',$wann)];
  if($t!='Sa' && $t!='So') {
    $tage[date('Y-m-d',$wann)]=$t.' '.date('d.m.Y',$wann);
  }
}

$anwesenheiten=array();
$result=$db->query("select * from gpb_anwesenheit where tag>='".date('Y-m-d',$von)."' and tag<='".date('Y-m-d',$bis)."' and tnid=".$tn->id);
while($row=$result->fetch_object()) {
  $row->anfangTitel=$anwesenheitsArten[$row->anfang];
  $row->endeTitel=$anwesenheitsArten[$row->ende];
  $row->kguTitel=$anwesenheitsArten[$row->kgu];
  $anwesenheiten[$row->tag]=$row;
}
$result->free();
$leereAnwesenheit=(object)array('anfang'=>'&nbsp;','ende'=>'&nbsp;','kgu'=>'&nbsp;',
                                'anfangTitel'=>'','endeTitel'=>'','kguTitel'=>'');

require_once 'IntrainSeite.php';
$seite=new IntrainSeite('Anwesenheit von TN '.$tn->tnname);
$seite->anfangGenerieren();
$tn->makeSehen('sehen');
if(empty($tn->selfkurse)) {
  echo 'Keine inTrain-Kurse.';
} else if($von>$bis) {
  echo 'inTrain-Kurse nur in der Zukunft.';
}
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
    <th rowspan="2">KW</th>
    <th rowspan="2">inTrain-Kurse</th>
    <th colspan="3">Mo</th>
    <th colspan="3">Di</th>
    <th colspan="3">Mi</th>
    <th colspan="3">Do</th>
    <th colspan="2">Fr</th>
  </tr>
  <tr>
    <th>Anfang</th>
    <th>Ende</th>
    <th>KGU</th>
    <th>Anfang</th>
    <th>Ende</th>
    <th>KGU</th>
    <th>Anfang</th>
    <th>Ende</th>
    <th>KGU</th>
    <th>Anfang</th>
    <th>Ende</th>
    <th>KGU</th>
    <th>Anfang</th>
    <th>Ende</th>
  </tr>
<?php
for($montag=$von;$montag<=$bis;$montag=strtotime('+1 week',$montag)) {
  $kw=date('Y-W',$montag);
?>
  <tr>
    <th rowspan="2">KW <?= substr($kw,5) ?></th>
    <td rowspan="2">
<?php
  if(isset($selfkurseByKW[$kw])) {
    foreach($selfkurseByKW[$kw] as $k) {
?>
      <a href="kurs_sehen.php?kursid=<?= $k->id ?>"><?= $k->titel ?></a><br />
<?php
    }
  }
?>
    </td>
<?php
  for($d=0;$d<5;++$d) {
    $datum=strtotime('+'.$d.' days',$montag);
?>
    <th colspan="<?= $d==4 ? 2 : 3 ?>"><?= date('d.m.Y',$datum) ?></th>
<?php
  }
?>
  </tr>
  <tr>
<?php
  for($d=0;$d<5;++$d) {
    $wann=strtotime('+'.$d.' days',$montag);
    $datum=date('Y-m-d',$wann);
    if(isset($ferienByTag[$datum])) {
      $fer=$ferienByTag[$datum];
?>
    <td colspan="<?= $d==4 ? 2 : 3 ?>" class="ferien"><a href="ferien_sehen.php?ferienid=<?= $fer->id ?>"><?= $fer->anlass ?></a></td>
<?php
    } else {
      $anw=isset($anwesenheiten[$datum]) ? $anwesenheiten[$datum] : $leereAnwesenheit;
?>
    <td class="anwesenheit" id="anw_<?= $tn->id ?>_<?= $datum ?>_anfang" onclick="anwesenheit_aendern(<?= $tn->id ?>,'<?= $datum ?>','anfang')" title="<?= htmlspecialchars($anw->anfangTitel,ENT_QUOTES) ?>"><?= $anw->anfang ?></td>
    <td class="anwesenheit" id="anw_<?= $tn->id ?>_<?= $datum ?>_ende" onclick="anwesenheit_aendern(<?= $tn->id ?>,'<?= $datum ?>','ende')" title="<?= htmlspecialchars($anw->endeTitel,ENT_QUOTES) ?>"><?= $anw->ende ?></td>
<?php
      if($d!=4) {
?>
    <td class="anwesenheit" id="anw_<?= $tn->id ?>_<?= $datum ?>_kgu" onclick="anwesenheit_aendern(<?= $tn->id ?>,'<?= $datum ?>','kgu')" title="<?= htmlspecialchars($anw->kguTitel,ENT_QUOTES) ?>"><?= $anw->kgu ?></td>
<?php
      }
    }
  }
?>
  </tr>
<?php
}
?>
</table>
<?php
$seite->endeGenerieren();
?>