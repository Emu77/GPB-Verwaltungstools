<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'LeitungKurs.php';
require_once 'LeitungMassnahme.php';
require_once '../Bewertung.php';

$massnahme=LeitungMassnahme::eineLaden(isset($_GET['massnahmeid']) ? (int)$_GET['massnahmeid'] : 0,'LeitungMassnahme');
if(empty($massnahme)) {
  header('Location:massnahmen.php');
  exit;
}

$stmt=$db->prepare("
select distinct k.*
  from gpb_klasse_tn ktn 
  join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
  join gpb_kurs_view k on k.id=kk.kursid
  where ktn.tnid in(select tnid from gpb_massnahme_tn where massnahmeid=".$massnahme->id.")
   and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
   and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)
  order by beginn,ende,titel");
$kurse=new Liste('LeitungKurs',$stmt);
Kurs::refsLaden($kurse);
LeitungKurs::massnahmenLaden($kurse);

$werte=array('summe'=>0);
$anzahlen=array('summe'=>0);
if(!empty($kurse->byId)) {
  $result=$db->query("select * from gpb_bewertung where not stumm and kursid in(".implode(',',array_keys($kurse->byId)).")
    and tnid in(select mtn.tnid from gpb_massnahme_tn mtn where mtn.massnahmeid=".$massnahme->id.")");
  while($row=$result->fetch_object()) {
    $kurs=$kurse->byId[$row->kursid];
    if($row->wert>0 || !empty($row->feedback)) {
      if(!isset($kurs->anzahlen)) {
        $kurs->anzahlen=array('summe'=>0);
      }
      if(isset($kurs->anzahlen[$row->frage])) {
        $kurs->anzahlen[$row->frage]++;
      } else {
        $kurs->anzahlen[$row->frage]=1;
      }
      if(isset($anzahlen[$row->frage])) {
        $anzahlen[$row->frage]++;
      } else {
        $anzahlen[$row->frage]=1;
      }
    }
    if($row->frage=='info' && !empty($row->feedback)) {
      if(isset($kurs->feedbacks)) {
        $kurs->feedbacks[]=$row->feedback;
      } else {
        $kurs->feedbacks=array($row->feedback);
      }
    } else if($row->frage!='info' && $row->wert>0) {
      if(!isset($kurs->bewerterids)) {
        $kurs->bewerterids=array();
      }
      $kurs->bewerterids[$row->tnid]=true;
      $kurs->anzahlen['summe']++;
      $anzahlen['summe']++;
      if(!isset($kurs->werte)) {
        $kurs->werte=array('summe'=>0);
      }
      if(isset($kurs->werte[$row->frage])) {
        $kurs->werte[$row->frage]+=$row->wert;
      } else {
        $kurs->werte[$row->frage]=$row->wert;
      }
      $kurs->werte['summe']+=$row->wert;
      if(isset($werte[$row->frage])) {
        $werte[$row->frage]+=$row->wert;
      } else {
        $werte[$row->frage]=$row->wert;
      }
      $werte['summe']+=$row->wert;
    }
  }
  $result->free();
}
$anzahlBewerter=0;
foreach($kurse->alle as $kurs) {
  if(isset($kurs->bewerterids)) {
    $anzahlBewerter+=count($kurs->bewerterids);
  }
}

require_once 'LeitungSeite.php';
$seite=new LeitungSeite('Maßnahme '.$massnahme->kuerzel.' - Modulbewertung');
$seite->anfangGenerieren();
$massnahme->makeSehen();
?>
<?php
if(empty($anzahlen)) {
?>
<div style="margin-bottom:1em;">Keine Bewertungen</div>
<?php
} else {
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <td>&nbsp;</td>
<?php
  foreach(Bewertung::$fragen as $frage=>$fragentext) {
?>
    <td class="bewertungsfrage"><?= $fragentext ?></td>
<?php
  }
?>
    <td class="bewertungsfrage"><b>Alle Fragen zusammen</b></td>
    <td class="bewertungsfrage">Feedbacks</td>
    <td>&nbsp;</td>
  </tr>
  <tr class="summen">
    <td align="right">Durchschnitt</td>
<?php
    foreach(Bewertung::$fragen as $frage=>$fragentext) {
?>
    <td align="center" valign="top"><?= isset($anzahlen[$frage]) ? sprintf('%0.1f',$werte[$frage]/$anzahlen[$frage]) : '' ?></td>
<?php
    }
?>
    <td align="center"><?= $anzahlen['summe']>0 ? sprintf('%0.1f',$werte['summe']/$anzahlen['summe']) : '' ?></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr class="summen">
    <td align="right">Anzahl Bewertungen</td>
<?php
    foreach(Bewertung::$fragen as $frage=>$fragentext) {
?>
    <td align="center" valign="top"><?= isset($anzahlen[$frage]) ? $anzahlen[$frage] : '' ?></td>
<?php
    }
?>
    <td align="center"><?= $anzahlBewerter>0 ? $anzahlBewerter.'&nbsp;TN' : '' ?></td>
    <td align="center"><?= isset($anzahlen['info']) ? $anzahlen['info'] : '' ?></td>
    <td>&nbsp;</td>
  </tr>
<?php
  foreach($kurse->alle as $kurs) {
?>
  <tr>
    <td valign="top">
      <?= $kurs->titel ?>
      <div style="text-align:right;"><?= $massnahme->anzahlTN ?>&nbsp;TN</div>
    </td>
<?php
    foreach(Bewertung::$fragen as $frage=>$fragentext) {
?>
    <td align="center" valign="top"><?= isset($kurs->anzahlen[$frage]) ? sprintf('%0.1f',$kurs->werte[$frage]/$kurs->anzahlen[$frage]).'<br />'.$kurs->anzahlen[$frage].'&nbsp;('.round(100*$kurs->anzahlen[$frage]/$massnahme->anzahlTN).'%)' : '' ?></td>
<?php
    }
?>
    <td align="center"><b><?= isset($kurs->anzahlen) && $kurs->anzahlen['summe']>0 ? sprintf('%0.1f',$kurs->werte['summe']/$kurs->anzahlen['summe']).'<br />'.$kurs->anzahlen['summe'].'&nbsp;('.round(100*count($kurs->bewerterids)/$massnahme->anzahlTN).'%)' : '' ?></b></td>
    <td valign="top" class="klappbar">
<?php
    if(isset($kurs->feedbacks)) {
?>
      <div class="klapp"><?= $kurs->anzahlen['info'] ?></div>
<?php
      foreach($kurs->feedbacks as $fb) {
?>
      <div class="feedback"><?= nl2br($fb) ?></div>
<?php
      }
    }
?>
    </td>
<?php
    $kurs->makeBearbeitenTd();
?>
  </tr>
<?php
  }
?>
</table>
<?php
}
$seite->endeGenerieren();
?>