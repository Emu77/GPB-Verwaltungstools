<?php
require_once 'check_login.php';
require_once 'LeitungKurs.php';
require_once 'LeitungTn.php';
require_once '../Bewertung.php';
require_once '../Liste.php';

$kurs = Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0, 'LeitungKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}
$kurs->ferienLaden();
$kurs->anzahlTage=0;
for($t=$kurs->von;$t<=$kurs->bis;$t=strtotime('+1 day',$t)) {
  $tag=date('w',$t);
  if($tag<1 || $tag>5) continue;
  $tag=date('Y-m-d',$t);
  if(isset($kurs->ferienByTag[$tag])) continue;
  ++$kurs->anzahlTage;
}

$bewertungen = array();
$anonym = array();
$stumm = array();
$anzahlen = array('summe' => 0);
$werte = array('summe' => 0);

$result = $db->query("select * from gpb_bewertung where kursid=".$kurs->id);
while($row = $result->fetch_object()) {
  if(!isset($bewertungen[$row->tnid])) {
     $bewertungen[$row->tnid]=array('anzahlantworten'=>0,'summe'=>0);
  }
  $bewertungen[$row->tnid][$row->frage] = $row->frage == 'info' ? $row->feedback : $row->wert;
  
  if($row->frage != 'info') {
    $bewertungen[$row->tnid]['anzahlantworten']++;
    $bewertungen[$row->tnid]['summe'] += $row->wert;
  }
  
  if($row->anonym) {
    $anonym[$row->tnid]=true;
  }
  if($row->stumm) {
    $stumm[$row->tnid]=true;
  } else {
    if($row->frage=='info') {
      if(!empty($row->feedback)) {
        if(isset($anzahlen[$row->frage])) {
          $anzahlen[$row->frage]++;
        } else {
          $anzahlen[$row->frage]=1;
        }
      }
    } else if($row->wert>0) {
      if(isset($anzahlen[$row->frage])) {
        $anzahlen[$row->frage]++;
      } else {
        $anzahlen[$row->frage]=1;
      }
      $anzahlen['summe']++;
      if(isset($werte[$row->frage])) {
        $werte[$row->frage]+=$row->wert;
      } else {
        $werte[$row->frage]=$row->wert;
      }
      $werte['summe']+=$row->wert;
    }
  }
}
$result->free();

$tns = new Liste('LeitungTn', empty($bewertungen) ? null : $db->prepare("select * from gpb_tn_view where id in(".implode(',',array_keys($bewertungen)).") order by nachname,vorname"));

//Anwesenheit Tage brechnen


$anwesenheit = array();
//Anwesenheiten berechnen
$resultAnw = $db->query("
    SELECT DISTINCT a.tnid, a.tag 
    FROM gpb_anwesenheit a
    JOIN gpb_klasse_tn ktn ON a.tnid = ktn.tnid
    JOIN gpb_kurs_klasse kk ON ktn.klasseid = kk.klasseid
    WHERE kk.kursid = ".$kurs->id." 
      AND a.tag >= '".$kurs->beginn."' 
      AND a.tag <= '".$kurs->ende."'
      AND a.mitis in('A','O','V','a')
");
//Ferien oder Werktag überprüfen
if($resultAnw) {
  while($row = $resultAnw->fetch_object()) {
    $tag = $row->tag;
    $wochentag = date('N', strtotime($tag));
    if($wochentag < 6 && empty($kurs->ferienByTag[$tag])) {
      $anwesenheit[$row->tnid] = ($anwesenheit[$row->tnid] ?? 0) + 1;
    }
  }
  $resultAnw->free();
}
// Dozent und Schule Kategorien definieren
$fragenDozent = array('dozent_fachsicher', 'dozent_klar', 'zielorientiert', 'gegliedert', 'lernklima');
$fragenSchule = array('interessant', 'material_nuetzlich', 'material_gut', 'ausstattung');

// Dozent Gesamtsumme berechnen
$werteDozent = $anzahlenDozent = 0;
foreach($fragenDozent as $f) {
  if(isset($anzahlen[$f])) {
    $werteDozent += $werte[$f];
    $anzahlenDozent += $anzahlen[$f];
  }
}
//Schule Gesamtsumme brechnen
$werteSchule = $anzahlenSchule = 0;
foreach($fragenSchule as $f) {
  if(isset($anzahlen[$f])) {
    $werteSchule += $werte[$f];
    $anzahlenSchule += $anzahlen[$f];
  }
}

// Individuelle Summen für jeden Teilnehmer berechnen
foreach($bewertungen as $tnid => $bewertung) {
  //für Dozent
  $tnWerteDozent = 0; 
  $tnAnzDozent = 0; //Anzahl der Bewertungen
  foreach($fragenDozent as $f) {
    if(isset($bewertung[$f])) {
      $tnWerteDozent += $bewertung[$f]; 
      $tnAnzDozent++;
    }
  }
  //fügen die Dozentbewertung in dem $bewertungen Array ein. 
  $bewertungen[$tnid]['bewertung_dozent'] = $tnAnzDozent > 0 ? sprintf('%0.1f', $tnWerteDozent / $tnAnzDozent) : '';

  //für Schule
  $sWerteSchule = 0; 
  $sAnzSchule = 0; // Anzahle der Bewertungen
  foreach($fragenSchule as $f) {
    if(isset($bewertung[$f])) {
      $sWerteSchule += $bewertung[$f]; 
      $sAnzSchule++;
    }
  }
    //fügen die Schulebewertung in dem $bewertungen Array ein.
  $bewertungen[$tnid]['bewertung_schule'] = $sAnzSchule > 0 ? sprintf('%0.1f', $sWerteSchule / $sAnzSchule) : '';
}


require_once 'LeitungSeite.php';
$seite = new LeitungSeite('Bewertung vom Kurs '.$kurs->titel);
$seite->anfangGenerieren();
$kurs->makeSehen('sehen');

if($kurs->bewertungStatus == 'nochnicht') {
?>
  <div class="nok" style="margin-bottom:1em;">Bewertung erst ab dem vorletzten Tag des Kurses möglich<?= empty($kurs->ende) || $kurs->ende=='0000-00-00' ? '' : ' ('.date('d.m.Y',strtotime('-1 day',$kurs->bis)).')' ?>.</div>
<?php
} else if(empty($bewertungen)) {
  if($kurs->bewertungStatus == 'offen') {
?>
  <div style="margin-bottom:1em;">Kurs noch in Bewertung, keine Bewertungen bisher</div>
<?php
  } else {
?>
  <div style="margin-bottom:1em;">Keine Bewertungen</div>
<?php
  }
} else {
  if($kurs->bewertungStatus == 'offen') {
?>
  <div>Kurs noch in Bewertung!</div>
<?php
  }
?>

<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <td>&nbsp;</td>
<?php foreach($fragenDozent as $frage): ?>
    <td class="bewertungsfrage"><?= Bewertung::$fragen[$frage] ?></td>
<?php endforeach; ?>
    <td class="bewertungsfrage" style="border-left:2px solid #000; border-right:2px solid #000;"><b>Summe Dozent</b></td>
    
<?php foreach($fragenSchule as $frage): ?>
    <td class="bewertungsfrage"><?= Bewertung::$fragen[$frage] ?></td>
<?php endforeach; ?>
    <td class="bewertungsfrage" style="border-left:2px solid #000; border-right:2px solid #000;"><b>Summe Schule</b></td>
    
    <td class="bewertungsfrage"><b>Alle Fragen zusammen</b></td>
    <td class="bewertungsfrage">Feedback</td>
    <td class="bewertungsfrage"><b>Anwesenheit</b></td>
    <td>&nbsp;</td>
  </tr>

  <tr class="summen">
    <td align="right">Durchschnitt</td>
<?php foreach($fragenDozent as $frage): ?>
    <td align="center" valign="top"><?= isset($anzahlen[$frage]) ? sprintf('%0.1f', $werte[$frage]/$anzahlen[$frage]) : '' ?></td>
<?php endforeach; ?>
    <td align="center" style="border-left:2px solid #000; border-right:2px solid #000;"><b><?= $anzahlenDozent > 0 ? sprintf('%0.1f', $werteDozent/$anzahlenDozent) : '' ?></b></td>

<?php foreach($fragenSchule as $frage): ?>
    <td align="center" valign="top"><?= isset($anzahlen[$frage]) ? sprintf('%0.1f', $werte[$frage]/$anzahlen[$frage]) : '' ?></td>
<?php endforeach; ?>
    <td align="center" style="border-left:2px solid #000; border-right:2px solid #000;"><b><?= $anzahlenSchule > 0 ? sprintf('%0.1f', $werteSchule/$anzahlenSchule) : '' ?></b></td>
    
    <td align="center"><?= $anzahlen['summe'] > 0 ? sprintf('%0.1f', $werte['summe']/$anzahlen['summe']) : '' ?></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>

  <tr class="summen">
    <td align="right">Anzahl Bewertungen<br />/&nbsp;<?= $kurs->anzahlTN ?>&nbsp;TN</td>
<?php foreach($fragenDozent as $frage): ?>
    <td align="center" valign="top"><?= isset($anzahlen[$frage]) ? $anzahlen[$frage].'<br />('.round(100*$anzahlen[$frage]/$kurs->anzahlTN).'%)' : '' ?></td>
<?php endforeach; ?>
    <td align="center" style="border-left:2px solid #000; border-right:2px solid #000;"><b><?= $anzahlenDozent > 0 ? count($bewertungen).($kurs->anzahlTN > 0 ? '<br />('.round(100*$anzahlenDozent/($kurs->anzahlTN * count($fragenDozent))).'%)' : '') : '' ?></b></td>

<?php foreach($fragenSchule as $frage): ?>
    <td align="center" valign="top"><?= isset($anzahlen[$frage]) ? $anzahlen[$frage].'<br />('.round(100*$anzahlen[$frage]/$kurs->anzahlTN).'%)' : '' ?></td>
<?php endforeach; ?>
    <td align="center" style="border-left:2px solid #000; border-right:2px solid #000;"><b><?= $anzahlenSchule > 0 ? count($bewertungen).($kurs->anzahlTN > 0 ? '<br />('.round(100*$anzahlenSchule/($kurs->anzahlTN * count($fragenSchule))).'%)' : '') : '' ?></b></td>
    
    <td align="center"><?= count($bewertungen).'&nbsp;TN<br />('.round(100*count($bewertungen)/$kurs->anzahlTN).'%)' ?></td>
    <td align="center"><?= $anzahlen['info'] ?? '' ?></td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>

  <?php
  foreach($bewertungen as $tnid => $bewertung) {
    $tn = $tns->byId[$tnid];
?>
  <tr <?= isset($stumm[$tnid]) ? 'class="stumm"' : '' ?>>
    <td valign="top">
<?php if($ich->siehtbewerter && $tn): ?>
      <?= $tn->anrede ?> <?= $tn->vorname ?> <?= $tn->nachname ?><?= isset($anonym[$tnid]) ? '<br />(anonym)' : '' ?>
<?php else: ?>
      (anonym)
<?php endif; ?>
    </td>

<?php foreach($fragenDozent as $frage): ?>
    <td align="center" valign="top"><?= $bewertung[$frage] ?? '' ?></td>
<?php endforeach; ?>
    <td align="center" valign="top" style="border-left:2px solid #000; border-right:2px solid #000;"><b><?= $bewertung['bewertung_dozent'] ?></b></td>

<?php foreach($fragenSchule as $frage): ?>
    <td align="center" valign="top"><?= $bewertung[$frage] ?? '' ?></td>
<?php endforeach; ?>
    <td align="center" valign="top" style="border-left:2px solid #000; border-right:2px solid #000;"><b><?= $bewertung['bewertung_schule'] ?></b></td>
    
    <td align="center" valign="top"><b><?= isset($bewertung['anzahlantworten']) && $bewertung['summe'] > 0 ? sprintf('%0.1f', $bewertung['summe']/$bewertung['anzahlantworten']) : '' ?></b></td>
    <td class="feedback" valign="top"><?= isset($bewertung['info']) ? nl2br($bewertung['info']) : '' ?></td>
    <td align="center" valign="top"><?= $anwesenheit[$tnid] ?? '0' ?>/<?= $kurs->anzahlTage ?>&nbsp;Tage</td>
    
    <td valign="top">
      <input type="checkbox" <?= isset($stumm[$tnid]) ? 'checked' : '' ?> onchange="location.href='kurs_bewertung_stummschalten.php?kursid=<?= $kurs->id ?>&tnid=<?= $tnid ?>&stumm='+(this.checked ? 'J' : 'N')" /> stummgeschaltet
    </td>
<?php
    if($tn) {
      $tn->makeBearbeitenTd();
    } else {
      echo '<td></td>';
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
<br />
<a href="bewertung.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>