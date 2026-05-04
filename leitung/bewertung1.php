<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'LeitungKurs.php';
require_once '../Bewertung.php';

$defaultbis=strtotime('last sunday');
$defaultvon=date('Y-m-d',strtotime('-1 week',$defaultbis));
$defaultbis=date('Y-m-d',$defaultbis);
$suche=isset($_SESSION['bewertung_kurse_suche']) ? $_SESSION['bewertung_kurse_suche'] : new Suche('am','beginn_von','beginn_bis','ende_von','ende_bis','titel','modultitel','ort','raum','ohneraum','klassebez','dozentname','moodleid','todo');
if(empty($suche->where)) {
  $suche->ende_von=$defaultvon;
  $suche->addKriterium("ende>=?",$defaultvon,'s');
  $suche->ende_bis=$defaultbis;
  $suche->addKriterium("ende<=?",$defaultbis,'s');
  if(!empty($ich->ort)) {
    $suche->ort=$ich->ort;
    $suche->addKriterium('ort=?',$suche->ort,'s');
  }
}

//$t0=microtime(true);

$kurse=new Liste('LeitungKurs',$suche->prepare("select * from gpb_kurs_view where","order by beginn,ende,modultitel,titel limit 50"));

//$t1=microtime(true);
//echo "Kurse laden: ".(($t1-$t0)*1000)."ms<br />\n";
//ob_flush();
//$t0=$t1;

Kurs::anzahlTNLaden($kurse);

//$t1=microtime(true);
//echo "Anzahl TN laden: ".(($t1-$t0)*1000)."ms<br />\n";
//ob_flush();
//$t0=$t1;

Kurs::refsLaden($kurse);

//$t1=microtime(true);
//echo "Refs laden: ".(($t1-$t0)*1000)."ms<br />\n";
//ob_flush();
//$t0=$t1;
//
//LeitungKurs::massnahmenLaden($kurse);
//
//$t1=microtime(true);
//echo "Maßnahmen laden: ".(($t1-$t0)*1000)."ms<br />\n";
//ob_flush();
//$t0=$t1;

$werte=array('summe'=>0);
$anzahlen=array('summe'=>0);
if(!empty($kurse->byId)) {
  $result=$db->query("select * from gpb_bewertung where not stumm and kursid in(".implode(',',array_keys($kurse->byId)).")");
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

//$t1=microtime(true);
//echo "Bewertungen laden: ".(($t1-$t0)*1000)."ms<br />\n";
//ob_flush();
//$t0=$t1;

require_once 'LeitungSeite.php';
$seite=LeitungSeite::$menueByUrl['../leitung/bewertung.php'];
$seite->anfangGenerieren();
?>
<script>
function keinFilter(prefix) {
  document.getElementById(prefix+'von').value=null;
  document.getElementById(prefix+'bis').value=null;
}
function addWoche(prefix,anzahl) {
  let elem=document.getElementById(prefix+'von');
  let val=elem.value;
  if(val) {
    let d=new Date(val);
    d.setDate(d.getDate()+anzahl*7);
    elem.value=d.toISOString().substring(0,10);
  }
  elem=document.getElementById(prefix+'bis');
  val=elem.value;
  if(val) {
    let d=new Date(val);
    d.setDate(d.getDate()+anzahl*7);
    elem.value=d.toISOString().substring(0,10);
  }
}
function endeLetzteWoche() {
  document.getElementById('ende_von').value='<?= $defaultvon ?>';
  document.getElementById('ende_bis').value='<?= $defaultbis ?>';
}
</script>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>KW</th>
    <th>Beginn
      <button type="button" onclick="keinFilter('beginn_')">Kein Filter</button><br />
      <button type="button" onclick="addWoche('beginn_',-1)">-1Wo</button>
      <button type="button" onclick="addWoche('beginn_',1)">+1Wo</button>
    </th>
    <th>Ende
      <button type="button" onclick="keinFilter('ende_')">Kein Filter</button>
      <button type="button" onclick="endeLetzteWoche()">Letzte Woche</button><br />
      <button type="button" onclick="addWoche('ende_',-1)">-1Wo</button>
      <button type="button" onclick="addWoche('ende_',1)">+1Wo</button>
    </th>
    <th>Modul</th>
    <th>Titel</th>
    <th>Ort</th>
    <!-- th>Massnahmen</th -->
    <th>Klassen</th>
    <th>Anz. TN</th>
    <th>Dozenten</th>
    <th></th>
  </tr>
  <form action="bewertung_kurse_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <tr>
    <td>&nbsp;</td>
<?php
$suche->makeVonBisInput(1,'beginn_');
$suche->makeVonBisInput(1,'ende_');
$suche->makeStringInput('modultitel');
$suche->makeStringInput('titel');
$suche->makeEnumInput('ort',array('-','Mitte','Neukölln'));
//$suche->makeStringInput('massnahmekuerzel');
$suche->makeStringInput('klassebez',2);
$suche->makeStringInput('dozentname');
?>
    <td>
<?php
$suche->makeBooleanInput('todo','unvollständig');
$suche->makeSubmit(false);
?>
    </td>
  </tr>
  </form>
<?php
foreach($kurse->alle as $kurs) {
?>
  <tr>
<?php
  $kurs->makeZeitraumTds();
  $kurs->makeModulTd();
  $kurs->makeTitelTd();
?>
    <td><?= $kurs->ort ?></td>
<?php
//  $kurs->makeMassnahmenTd();
  $kurs->makeKlassenTd();
  $kurs->makeAnzahlTNTd(true);
  $kurs->makeDozentenTd();
  $kurs->makeBearbeitenTd();
?>
  </tr>
<?php
}
?>
</table>
<br />
<?php
if(empty($anzahlen)) {
?>
<div style="margin-bottom:1em;">Keine Bewertungen</div>
<?php
} else {
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <td align="right" valign="bottom"><button type="button" style="margin:1em;" onclick="location.href='bewertung_pdf.php'">PDF</button></td>
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
    <td align="center"><?= $anzahlen['summe'] > 0 ? sprintf('%0.1f',$werte['summe']/$anzahlen['summe']) : '' ?></td>
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
      <div align="right"><?= $kurs->anzahlTN ?>&nbsp;TN</div>
    </td>
<?php
    foreach(Bewertung::$fragen as $frage=>$fragentext) {
?>
    <td align="center" valign="top"><?= isset($kurs->anzahlen[$frage]) ? sprintf('%0.1f',$kurs->werte[$frage]/$kurs->anzahlen[$frage]).'<br />'.$kurs->anzahlen[$frage].'&nbsp;('.round(100*$kurs->anzahlen[$frage]/$kurs->anzahlTN).'&nbsp;%)' : '' ?></td>
<?php
    }
?>
    <td align="center"><b><?= isset($kurs->anzahlen) && $kurs->anzahlen['summe']>0 ? sprintf('%0.1f',$kurs->werte['summe']/$kurs->anzahlen['summe']).'<br />'.count($kurs->bewerterids).'&nbsp;('.round(100*count($kurs->bewerterids)/$kurs->anzahlTN).'%)' : '' ?></b></td>
    <td valign="top" align="center" class="klappbar">
<?php
    if(isset($kurs->feedbacks)) {
?>
      <div class="klapp"><br /><?= $kurs->anzahlen['info'] ?>&nbsp;(<?= round(100*$kurs->anzahlen['info']/$kurs->anzahlTN) ?>&nbsp;%)</div>
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