<?php
require_once 'check_login.php';

$startgruppen=array();
$result=$db->query("select distinct startgruppe from gpb_vertragszahlen order by startgruppe desc limit 20");
while($row=$result->fetch_object()) {
  $startgruppen[]=$row->startgruppe;
}
$result->free();

$startgruppe=isset($_GET['startgruppe']) ? $_GET['startgruppe'] : (empty($startgruppen) ? '' : $startgruppen[0]);

$props=array('interessenten','vertraege','offenevertraege','beratungen');

$massnahmen=array();
$stmt=$db->prepare("select vz.*
    ,ifnull(f.bezeichnung,'InTrain / Spezialisten / Fachkräfte') as familie
  from gpb_vertragszahlen vz
  left outer join gpb_beruf b on b.bkz=vz.bkz
  left outer join gpb_berufsfamilie f on f.id=b.familieid
  where vz.startgruppe=? 
  order by vz.ort,f.bezeichnung,vz.beginn");
$stmt->bind_param('s',$startgruppe);
$stmt->execute();
$result=$stmt->get_result();
while($row=$result->fetch_object()) {
  foreach($props as $p) {
    $row->$p=(int)$row->$p;
  }
  $massnahmen[]=$row;
}
$result->free();

function init(&$obj) {
  global $props;
  $obj->eab=(object)array();
  $obj->us=(object)array();
  foreach($props as $p) {
    $obj->$p=0;
    $obj->eab->$p=0;
    $obj->us->$p=0;
  }
}
function add(&$obj,$m) {
  global $props;
  foreach($props as $p) {
    $obj->$p+=$m->$p;
  }
  if($m->typ=='EAB') {
    foreach($props as $p) {
      $obj->eab->$p+=$m->$p;
    }
  } else {
    foreach($props as $p) {
      $obj->us->$p+=$m->$p;
    }
  }
}

$gesamt=(object)array('beginne'=>array());
init($gesamt);
$orte=array();
foreach($massnahmen as $m) {
  add($gesamt,$m);
  if(!isset($gesamt->beginne[$m->beginn])) {
    $gesamt->beginne[$m->beginn]=(object)array();
    init($gesamt->beginne[$m->beginn]);
  }
  $beginn=$gesamt->beginne[$m->beginn];
  add($beginn,$m);
  if(!isset($orte[$m->ort])) {
    $orte[$m->ort]=(object)array('beginne'=>array(),'familien'=>array());
    init($orte[$m->ort]);
  }
  $ort=$orte[$m->ort];
  add($ort,$m);
  if(!isset($ort->beginne[$m->beginn])) {
    $ort->beginne[$m->beginn]=(object)array();
    init($ort->beginne[$m->beginn]);
  }
  $beginn=$ort->beginne[$m->beginn];
  add($beginn,$m);
  if(!isset($ort->familien[$m->familie])) {
    $ort->familien[$m->familie]=(object)array('beginne'=>array());
    init($ort->familien[$m->familie]);
  }
  $familie=$ort->familien[$m->familie];
  add($familie,$m);
  if(!isset($familie->beginne[$m->beginn])) {
    $familie->beginne[$m->beginn]=(object)array();
    init($familie->beginne[$m->beginn]);
  }
  $beginn=$familie->beginne[$m->beginn];
  add($beginn,$m);
}

$klappid=0;
function makeTds($obj) {
?>
  <td align="center"><?= $obj->interessenten ?></td>
  <td align="center"><?= $obj->beratungen ?></td>
  <td align="center"><?= $obj->vertraege ?> (<?= $obj->offenevertraege ?>)</td>
  <td align="center"><?= $obj->interessenten<=0 ? '' : round(100*$obj->beratungen/$obj->interessenten).'%' ?></td>
  <td align="center"><?= $obj->beratungen<=0 ? '' : round(100*($obj->vertraege+$obj->offenevertraege)/$obj->beratungen).'%' ?></td>
  <td align="center" style="border-right:2px solid black;"><?= $obj->interessenten<=0 ? '' : round(100*($obj->vertraege+$obj->offenevertraege)/$obj->interessenten).'%' ?></td>
<?php
}
function makeZeile($titel,$istsumme,$zugeklappt,$obj) {
  global $klappid;
?>
  <tr class="<?= $istsumme ? 'summe' : ($zugeklappt ? 'klappbar' : '') ?>" <?= $zugeklappt ? 'style="display:none;"' : '' ?>>
    <td align="right" style="border-right:2px solid black;">
      <?= $istsumme ? 'Summe ' : '' ?><?= $titel ?>
<?php
  if(!$istsumme && !$zugeklappt) {
    ++$klappid;
?>
      <button type="button" id="klapp_<?= $klappid ?>" onclick="klapp(<?= $klappid ?>)">Einzelne Starttermine</button>
<?php
  }
?>
    </td>
<?php
  makeTds($obj->eab);
  makeTds($obj->us);
  makeTds($obj);
?>
  </tr>
<?php
}

require_once 'BeratungSeite.php';
$seite=BeratungSeite::$menueByUrl['vertragszahlen.php'];
$seite->anfangGenerieren();
?>
Starttermin: <select onchange="location.href='vertragszahlen.php?startgruppe='+this.value;">
<?php
foreach($startgruppen as $g) {
?>
  <option value="<?= htmlentities($g,ENT_COMPAT) ?>" <?= $g==$startgruppe ? 'selected' : '' ?>><?= str_replace("_"," ",$g) ?></option>
<?php
}
?>
</select><br />
<br />
<style>
tr.summe td {
  font-weight:bold;
}
</style>
<script>
function klapp(id) {
  let btn=document.getElementById('klapp_'+id);
  let tr=btn.parentNode.parentNode;
  let table=tr.parentNode;
  while(table.tagName.toUpperCase()!='TABLE') table=table.parentNode;
  let idx=0;
  while(idx<table.rows.length && table.rows[idx]!=tr) ++idx;
  if(btn.innerText=='Einzelne Starttermine') {
    for(++idx;idx<table.rows.length && table.rows[idx].className=='klappbar';++idx) {
      table.rows[idx].style.display='';
    }
    btn.innerText='Einzelne Starttermine verbergen';
  } else {
    for(++idx;idx<table.rows.length && table.rows[idx].className=='klappbar';++idx) {
      table.rows[idx].style.display='none';
    }
    btn.innerText='Einzelne Starttermine';
  }
}
</script>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
foreach($orte as $ortsname=>$ort) {
  ++$klappid;
?>
  <tr>
    <th align="left" style="border-right:2px solid black;"><?= $ortsname ?></th>
    <th align="center" colspan="6" style="border-right:2px solid black;">EAB</th>
    <th align="center" colspan="6" style="border-right:2px solid black;">US</th>
    <th align="center" colspan="6" style="border-right:2px solid black;">EAB + US</th>
  </tr>
  <tr>
    <th style="border-right:2px solid black;"><button type="button" id="klapp_<?= $klappid ?>" onclick="klapp(<?= $klappid ?>)">Einzelne Starttermine</button></th>
    <th>Interessenten</th>
    <th>Beratungen</th>
    <th>Verträge (offen)</th>
    <th>Quote Int/B</th>
    <th>Quote B/V</th>
    <th style="border-right:2px solid black;">Quote Int/V</th>
    <th>Interessenten</th>
    <th>Beratungen</th>
    <th>Verträge (offen)</th>
    <th>Quote Int/B</th>
    <th>Quote B/V</th>
    <th style="border-right:2px solid black;">Quote Int/V</th>
    <th>Interessenten</th>
    <th>Beratungen</th>
    <th>Verträge (offen)</th>
    <th>Quote Int/B</th>
    <th>Quote B/V</th>
    <th style="border-right:2px solid black;">Quote Int/V</th>
  </tr>
<?php
  foreach($ort->beginne as $datum=>$beginn) {
    makeZeile(date('d.m.Y',strtotime($datum)),false,true,$beginn);
  }
  foreach($ort->familien as $famname=>$familie) {
    makeZeile($famname,false,false,$familie);
    foreach($familie->beginne as $datum=>$beginn) {
      makeZeile(date('d.m.Y',strtotime($datum)),false,true,$beginn);
    }
  }
  makeZeile($ortsname,true,false,$ort);
}
++$klappid;
?>
  <tr>
    <th align="left" style="border-right:2px solid black;">GPB</th>
    <th align="center" colspan="6" style="border-right:2px solid black;">EAB</th>
    <th align="center" colspan="6" style="border-right:2px solid black;">US</th>
    <th align="center" colspan="6" style="border-right:2px solid black;">EAB + US</th>
  </tr>
  <tr>
    <th style="border-right:2px solid black;"><button type="button" id="klapp_<?= $klappid ?>" onclick="klapp(<?= $klappid ?>)">Einzelne Starttermine</button></th>
    <th>Interessenten</th>
    <th>Beratungen</th>
    <th>Verträge  (offen)</th>
    <th>Quote Int/B</th>
    <th>Quote B/V</th>
    <th style="border-right:2px solid black;">Quote Int/V</th>
    <th>Interessenten</th>
    <th>Beratungen</th>
    <th>Verträge  (offen)</th>
    <th>Quote Int/B</th>
    <th>Quote B/V</th>
    <th style="border-right:2px solid black;">Quote Int/V</th>
    <th>Interessenten</th>
    <th>Beratungen</th>
    <th>Verträge  (offen)</th>
    <th>Quote Int/B</th>
    <th>Quote B/V</th>
    <th style="border-right:2px solid black;">Quote Int/V</th>
  </tr>
<?php
  foreach($gesamt->beginne as $datum=>$beginn) {
    makeZeile(date('d.m.Y',strtotime($datum)),false,true,$beginn);
  }
  makeZeile('GPB',true,false,$gesamt);
?>
</table>
<?php
$seite->endeGenerieren();
?>