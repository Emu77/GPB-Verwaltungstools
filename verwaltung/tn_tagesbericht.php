<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungTn.php';
require_once 'VerwaltungKurs.php';
require_once 'VerwaltungFerien.php';

$tn=VerwaltungTn::einenLaden(isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0,'VerwaltungTn');
if(empty($tn)) {
  header('Location:tn.php');
  exit;
}
$tn->ferienLaden('VerwaltungFerien');
$ferienByTag=array();
foreach($tn->ferien as $fer) {
  for($d=max($fer->von,$tn->von);$d<=min($fer->bis,$tn->bis);$d=strtotime('+1 day',$d)) {
    $tag=date('Y-m-d',$d);
    if(!isset($ferienByTag[$tag]) || $fer->istAllgemeiner($ferienByTag[$tag])) {
      $ferienByTag[$tag]=$fer;
    }
  }
}

$von=0;
$bis=0;

$kurse=new Liste('VerwaltungKurs',$db->prepare("
  select distinct k.*,n.*
    from gpb_klasse_tn ktn 
    join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
    join gpb_kurs k on k.id=kk.kursid
    left outer join gpb_note n on n.kursid=k.id and n.tnid=".$tn->id."
    where ktn.tnid=".$tn->id."
    and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
    and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)
    order by beginn,ende,titel"));
Kurs::refsLaden($kurse);

if(!empty($kurse->byId)) {
  foreach($kurse->alle as $kurs) {
    if($von==0 || $kurs->von<$von) $von=$kurs->von;
    if($bis==0 || $kurs->bis>$bis) $bis=$kurs->bis;
    $kurs->berichteByTag=array();
  }
  $result=$db->query("select * from gpb_kurs_tagesbericht where kursid in(".implode(',',array_keys($kurse->byId)).") order by tag");
  while($row=$result->fetch_object()) {
    $kurse->byId[$row->kursid]->berichteByTag[$row->tag]=$row;
  }
  $result->free();
}
$leererBericht=(object)array(
  'themen'=>'',
  'kguil'=>'',
  'bemerkungen'=>''
);

$anwesenheitByTag=array();
$result=$db->query("select * from gpb_anwesenheit where tnid=".$tn->id." order by tag");
while($row=$result->fetch_object()) {
  $row->wann=strtotime($row->tag);
  if($von==0 || $row->wann<$von) $von=$row->wann;
  if($bis==0 || $row->wann>$bis) $bis=$row->wann;
  $anwesenheitByTag[$row->tag]=$row;
}
$result->free();
$leereAnwesenheit=(object)array(
  'anfang'=>'',
  'ende'=>'',
  'kgu'=>''
);

$tagnamen=array('Mon'=>'Mo','Tue'=>'Di','Wed'=>'Mi','Thu'=>'Do','Fri'=>'Fr','Sat'=>'Sa','Sun'=>'So');

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Tagesbericht von TN '.$tn->tnname);
$seite->anfangGenerieren();
$tn->makeSehen('sehen');
foreach($kurse->alle as $kurs) {
  echo "<h2>".$kurs->titel."</h2>\n";
  if(!empty($kurs->modultitel)) {
    echo "<b>Zeugnis-Modul:</b> ".$kurs->modultitel."<br />\n";
  }
  echo "<b>Zeitraum:</b> ".date('d.m.Y',$kurs->von)." - ". date('d.m.Y',$kurs->bis)."<br />\n";
  if(count($kurs->dozenten)==1) {
    $doz=$kurs->dozenten[0];
    echo $doz->anrede=='Frau' ? "<b>Dozentin:</b> " : "<b>Dozent:</b> ";
    echo $doz->anrede." ".$doz->vorname." ".$doz->nachname."<br />\n";
  } else if(count($kurs->dozenten)>1) {
    echo "<b>DozentINNen:</b> ";
    $erst=true;
    foreach($kurs->dozenten as $doz) {
      if($erst) $erst=false; else echo ", ";
      echo $doz->anrede." ".$doz->vorname." ".$doz->nachname;
    }
    echo "<br />\n";
  }
  if($kurs->notenstatus=='keine') {
    echo "Kurs ohne Noten<br />\n";
  } else if($kurs->notenstatus=='todo') {
    echo "Noten noch nicht eingetragen<br />\n";
  } else if($kurs->nachnote!==null) {
    echo "<b>Note:</b> ".$kurs->nachnote."<br />\n";
  } else if($kurs->note!==null) {
    echo "<b>Note:</b> ".$kurs->note."<br />\n";
  } else {
    echo "Note noch nicht eingetragen<br />\n";
  }
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Datum</th>
    <th>Anfang</th>
    <th>Ende</th>
    <th>KGU</th>
    <th>Themen</th>
  </tr>
<?php
  for($wann=$kurs->von;$wann<=$kurs->bis;$wann=strtotime('+1 day',$wann)) {
    $tagname=$tagnamen[date('D',$wann)];
    if($tagname=='Sa' || $tagname=='So') continue;
    $tag=date('Y-m-d',$wann);
    if(isset($ferienByTag[$tag])) {
      $fer=$ferienByTag[$tag];
?>
  <tr>
    <td align="right"><?= $tagname ?> <?= date('d.m.Y',$wann) ?></td>
    <td colspan="4" class="ferien"><a href="ferien_sehen.php?ferienid=<?= $fer->id ?>"><?= $fer->anlass ?></a></td>
  </tr>
<?php
    } else {
      $anw=isset($anwesenheitByTag[$tag]) ? $anwesenheitByTag[$tag] : $leereAnwesenheit;
      $bericht=isset($kurs->berichteByTag[$tag]) ? $kurs->berichteByTag[$tag] : $leererBericht;
?>
  <tr>
    <td align="right"><?= $tagname ?> <?= date('d.m.Y',$wann) ?></td>
    <td align="center"><?= $anw->anfang ?></td>
    <td align="center"><?= $anw->ende ?></td>
    <td align="center"><?= $anw->kgu ?></td>
    <td><?= nl2br($bericht->themen) ?></td>
  </tr>
<?php
    }
  }
?>
</table>
<?php
}
$seite->endeGenerieren();
?>