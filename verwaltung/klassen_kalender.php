<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once $ich->istleiter ? '../leitung/LeitungKlasse.php' : 'VerwaltungKlasse.php';
require_once 'VerwaltungSeite.php';

$wann=isset($_POST['wann']) ? $_POST['wann'] : (isset($_GET['wann']) ? $_GET['wann'] : false);
if(empty($wann)) {
  $wann=date('Y-m-d');
}
$prak=isset($_POST['prak']) ? $_POST['prak']!='N' : (isset($_GET['prak']) ? $_GET['prak']!='N' : false);

if(empty($ich->ort)) {
  //(beginn is null or beginn='0000-00-00' or beginn<=?) and
  $stmt=$db->prepare("select * from gpb_klasse_view where (ende is null or ende='0000-00-00' or ende>=?) ".($prak ? "" : "and bezeichnung not like '%PRAK%'")." order by ort,familieid,berufkuerzel");
  $stmt->bind_param('s',$wann);
} else {
  //(beginn is null or beginn='0000-00-00' or beginn<=?) and
  $stmt=$db->prepare("select * from gpb_klasse_view where ort=? and (ende is null or ende='0000-00-00' or ende>=?) ".($prak ? "" : "and bezeichnung not like '%PRAK%'")." order by familieid,berufkuerzel");
  $stmt->bind_param('ss',$ich->ort,$wann);
}
$klassen=new Liste($ich->istleiter ? 'LeitungKlasse' : 'VerwaltungKlasse',$stmt);
$von=strtotime($wann);
$bis=$von;
foreach($klassen->alle as $k) {
  if($k->von>0 && $k->von<$von) {
    $von=$k->von;
  }
  if($k->bis>0 && $k->bis>$bis) {
    $bis=$k->bis;
  }
}
if(date('w',$von)!=1) $von=strtotime('last monday',$von);
$heute=strtotime('today');
if(date('w',$heute)!=1) $heute=strtotime('last monday',$heute);
$seite=new VerwaltungSeite('Klassenkalender '.(empty($ich->ort) ? '' : $ich->ort.' ').date('d.m.Y',strtotime($wann)));
$seite->anfangGenerieren();
?>
<form id="wann_form" action="klassen_kalender.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="date" name="wann" value="<?= $wann ?>" onchange="document.getElementById('wann_form').submit()" />
  PRAK-Klassen&nbsp;<input type="checkbox" name="prak" value="J" <?= $prak ? 'checked' : '' ?> onchange="document.getElementById('wann_form').submit()" />
</form>
<style>
body {
  width:fit-content;
}
#inhalt {
  display:block;
}
.klasse {
  background-color:rgb(220,220,220);
}
tr:hover .klasse {
  background-color:rgb(255,144,100);
}
.klasse div {
  width:100%;
  display:flex;
  flex-direction:row;
  justify-content:space-between;
  gap:2px;
}
.klasse a {
  color:black;
}
</style>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
  <tr style="position:sticky;top:0px;">
    <th>&nbsp;</th>
<?php
for($t=$von;$t<=$bis;$t=strtotime('+1 week',$t)) {
?>
    <th><div style="width:100%;<?= $t==$heute ? 'background-color:rgb(255,144,100);' : 'background-color:white;' ?>">KW&nbsp;<?= date('W',$t) ?><br /><?= date('d.m.',$t) ?><br /><?= date('d.m.',strtotime('+4 days',$t)) ?></div></th>
<?php
}
?>
  </tr>
<?php
foreach($klassen->alle as $k) {
  $wovor=0;
  $wo=0;
  $wonach=0;
  for($t=$von;$t<=$bis;$t=strtotime('+1 week',$t)) {
    if($k->von>0 && $t<$k->von) ++$wovor;
    else if($k->bis>0 && $t>$k->bis) ++$wonach;
    else ++$wo;
  }
?>
  <tr>
    <th style="position:sticky;left:0px;background-color:white;" align="left"><a href="../verwaltung/klasse_sehen.php?klasseid=<?= $k->id ?>"><?= $k->bezeichnung ?> (<?= empty($k->anzahlTN) ? $k->anzahlAnmeldungen : $k->anzahlTN ?> TN)</a></th>
<?php
  for($i=0;$i<$wovor;++$i) {
?>
    <td>&nbsp;</td>
<?php
  }
  if($wo>0) {
?>
    <td colspan="<?= $wo ?>" class="klasse"><div>
      <span>KW&nbsp;<?= date('W',$k->von) ?></span>
      <a href="../verwaltung/klasse_sehen.php?klasseid=<?= $k->id ?>"><?= $k->bezeichnung ?> (<?= empty($k->anzahlTN) ? $k->anzahlAnmeldungen : $k->anzahlTN ?> TN)</a>
      <span>KW&nbsp;<?= date('W',$k->bis) ?></span>
    </div></td>
<?php
  }
  for($i=0;$i<$wonach;++$i) {
?>
    <td>&nbsp;</td>
<?php
  }
?>
  </tr>
<?php
}
?>
</table>
<a href="../verwaltung/klassen.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>