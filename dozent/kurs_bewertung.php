<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';
require_once '../Bewertung.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'DozentKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}

$anzahlen=array();
$werte=array();
$feedbacks=array();
if(in_array($ich->id,$kurs->dozentenids)) {
  $result=$db->query("select * from gpb_bewertung where not stumm and kursid=".$kurs->id);
  while($row=$result->fetch_object()) {
    if($row->frage=='info') {
      if($row->feedback) {
        $feedbacks[]=$row->feedback;
      }
    } else if($row->wert>0) {
      if(isset($anzahlen[$row->frage])) {
        $anzahlen[$row->frage]++;
      } else {
        $anzahlen[$row->frage]=1;
      }
      if(isset($werte[$row->frage])) {
        $werte[$row->frage]+=$row->wert;
      } else {
        $werte[$row->frage]=$row->wert;
      }
    }
  }
  $result->free();
}

require_once 'DozentSeite.php';
$seite=new DozentSeite('Bewertung vom Kurs '.$kurs->titel);
$seite->anfangGenerieren();
$kurs->makeSehen('sehen');
if(!in_array($ich->id,$kurs->dozentenids)) {
?>
<div class="nok" style="margin-bottom:1em;">Sie dürfen nur die Bewertung der eigenen Kurse sehen.</div>
<?php
} else if($kurs->bewertungStatus=='nochnicht') {
?>
<div class="nok" style="margin-bottom:1em;">Bewertung erst ab dem vorletzten Tag des Kurses möglich<?= empty($kurs->ende) || $kurs->ende=='0000-00-00' ? '' : ' ('.date('d.m.Y',strtotime('-1 day',$kurs->bis)).')' ?>.</div>
<?php
} else if(empty($anzahlen) && empty($feedbacks)) {
  if($kurs->bewertungStatus=='offen') {
?>
<div style="margin-bottom:1em;">Kurs noch in Bewertung, keine Bewertungen bisher</div>
<?php
  } else {
?>
<div style="margin-bottom:1em;">Keine Bewertungen</div>
<?php
  }
} else {
  if($kurs->bewertungStatus=='offen') {
?>
<div>Kurs noch in Bewertung!</div>
<?php
  }
  if(!empty($anzahlen)) {
?>
<table id="bewertung" border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
  <tr>
    <th></th>
    <th>Durchschnitt</th>
    <th>Anzahl Bewertungen</th>
  </tr>
<?php
    foreach(Bewertung::$fragen as $frage=>$fragentext) {
?>
  <tr>
    <th align="right"><?= $fragentext ?></th>
    <td align="center"><?= isset($anzahlen[$frage]) ? sprintf('%0.1f',$werte[$frage]/$anzahlen[$frage]) : '' ?></td>
    <td align="center"><?= isset($anzahlen[$frage]) ? $anzahlen[$frage] : 0 ?></td>
  </tr>
<?php
    }
?>
</table>
<?php
  }
  if(!empty($feedbacks)) {
?>
<div id="feedbacks">
<b>Feedback:</b>
<?php
    foreach($feedbacks as $fb) {
?>
  <div class="feedback"><?= nl2br($fb) ?></div>
<?php
    }
  }
}
?>
</div>
<a href="kurse.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>
?>