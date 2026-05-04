<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungMassnahme.php';
require_once 'VerwaltungKurs.php';

$massnahme=VerwaltungMassnahme::eineLaden(isset($_GET['massnahmeid']) ? (int)$_GET['massnahmeid'] : 0,'VerwaltungMassnahme');
if(empty($massnahme)) {
  header('Location:massnahmen.php');
  exit;
}
$massnahme->ferienLaden();
$ferienByTag=array();
foreach($massnahme->ferien as $fer) {
  for($d=max($fer->von,$massnahme->von);$d<=min($fer->bis,$massnahme->bis);$d=strtotime('+1 day',$d)) {
    $tag=date('Y-m-d',$d);
    if(isset($ferienByTag[$tag])) {
      $ferienByTag[$tag][]=$fer;
    } else {
      $ferienByTag[$tag]=array($fer);
    }
  }
}

$stmt=$db->prepare("
select distinct k.*
  from gpb_klasse_tn ktn 
  join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
  join gpb_kurs k on k.id=kk.kursid
  where ktn.tnid in(select tnid from gpb_massnahme_tn where massnahmeid=".$massnahme->id.")
   and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
   and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)
  order by beginn,ende,titel");
$kurse=new Liste('VerwaltungKurs',$stmt);
Kurs::refsLaden($kurse);

$von=0;
$bis=0;
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

$tagnamen=array('Mon'=>'Mo','Tue'=>'Di','Wed'=>'Mi','Thu'=>'Do','Fri'=>'Fr','Sat'=>'Sa','Sun'=>'So');

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Tagesbericht der Kurse der TN der Maßnahme '.$massnahme->kuerzel);
$seite->anfangGenerieren();
$massnahme->makeSehen();
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
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Datum</th>
    <th>Themen</th>
  </tr>
<?php
  for($wann=$kurs->von;$wann<=$kurs->bis;$wann=strtotime('+1 day',$wann)) {
    $tagname=$tagnamen[date('D',$wann)];
    if($tagname=='Sa' || $tagname=='So') continue;
    $tag=date('Y-m-d',$wann);
    if(isset($ferienByTag[$tag])) {
?>
  <tr>
    <td align="right"><?= $tagname ?> <?= date('d.m.Y',$wann) ?></td>
    <td class="ferien">
<?php
      foreach($ferienByTag[$tag] as $fer) {
?>
        <a href="ferien_sehen.php?ferienid=<?= $fer->id ?>"><?= $fer->anlass ?></a><br />
<?php
      }
?>
    </td>
  </tr>
<?php
    } else {
      $bericht=isset($kurs->berichteByTag[$tag]) ? $kurs->berichteByTag[$tag] : $leererBericht;
?>
  <tr>
    <td align="right"><?= $tagname ?> <?= date('d.m.Y',$wann) ?></td>
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