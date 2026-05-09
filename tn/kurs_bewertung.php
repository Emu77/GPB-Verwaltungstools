<?php
require_once 'check_login.php';
require_once 'TnKurs.php';
require_once '../Bewertung.php';

$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$kursmoodleid=isset($_GET['kursmoodleid']) ? (int)$_GET['kursmoodleid'] : 0;
$kurs=$kursid>0 ? Kurs::einenLaden($kursid,'TnKurs') : Kurs::einenByMoodleIDLaden($kursmoodleid,'TnKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}
$kurs->ladeVonMir();

$bewertungen=array();
if($kurs->vonMir) {
  $result=$db->query("select * from gpb_bewertung where kursid=".$kurs->id." and tnid=".$ich->id);
  while($row=$result->fetch_object()) {
    $bewertungen[$row->frage]=$row;
  }
  $result->free();
}

require_once 'TnSeite.php';
$seite=new TnSeite('Kurs '.$kurs->titel.' bewerten');
$seite->anfangGenerieren();
$kurs->makeSehen('sehen');

if(!$kurs->vonMir) {
?>
<div class="nok" style="margin-bottom:1em;">Sie können nur Kurse bewerten, an den Sie teil genommen haben.</div>
<?php
} else if($kurs->bewertungStatus=='nochnicht') {
?>
<div class="nok" style="margin-bottom:1em;">Der Kurs kann erst ab seinem vorletzten Tag bewertet werden<?= empty($kurs->ende) || $kurs->ende=='0000-00-00' ? '' : ' ('.date('d.m.Y',strtotime('-1 day',$kurs->bis)).')' ?>.</div>
<?php
} else if($kurs->bewertungStatus=='geschlossen') {
  if(empty($bewertungen)) {
?>
<div class="nok" style="margin-bottom:1em;">Bewertung schon abgeschlossen.</div>
<?php
  } else {
?>
<div class="done">Danke für Ihre Bewertung!</div>
<br />
<table id="bewertung" border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
  <tr>
    <th></th>
    <td colspan="2">
<?php
  foreach(Bewertung::$werte as $wert=>$t) {
?>
      <?= $wert ?>&nbsp;=&nbsp;<?= $t ?><br />
<?php
  }
?>
    </td>
  </tr>
<?php
  foreach(Bewertung::$fragen as $frage=>$fragentext) {
?>
  <tr>
    <th align="right"><?= $fragentext ?></th>
    <td>
<?php
    foreach(Bewertung::$werte as $wert=>$t) {
?>
      <div style="display:inline-block;padding-right:2px;background-color:<?= Bewertung::$farben[$wert] ?>;"><input type="radio" name="<?= $frage ?>" value="<?= $wert ?>" <?= isset($bewertungen[$frage]) && $bewertungen[$frage]->wert==$wert ? 'checked' : '' ?> disabled /></div><?= $wert ?> &nbsp;
<?php
    }
?>
    </td>
    <td>&nbsp;</td>
  </tr>
<?php
  }
?>
  <tr>
    <th align="right">Feedback</th>
    <td colspan="2"><textarea name="feedback" style="width:250px;height:100px;" disabled><?= isset($bewertungen['info']) ? $bewertungen['info']->feedback : '' ?></textarea></td>
  </tr>
  <tr>
    <th align="right"><input type="checkbox" name="anonym" value="J" <?= !isset($bewertungen['info']) || $bewertungen['info']->anonym ? 'checked' : '' ?> disabled />Anonym bewerten</th>
    <td colspan="2">&nbsp;</td>
  </tr>
</table>
<?php
  }
} else {
?>
<script>
function bewertung_loeschen(frage) {
  for(let r of document.getElementsByName(frage)) {
    r.checked=false;
  }
}
</script>
<form action="kurs_bewertung_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="kursid" value="<?= $kurs->id ?>" />
<table id="bewertung" border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
  <tr>
    <th></th>
    <td colspan="2">
<?php
  foreach(Bewertung::$werte as $wert=>$t) {
?>
      <?= $wert ?>&nbsp;=&nbsp;<?= $t ?><br />
<?php
  }
?>
    </td>
  </tr>
<?php
  foreach(Bewertung::$fragen as $frage=>$fragentext) {
?>
  <tr>
    <th align="right"><?= $fragentext ?></th>
    <td>
<?php
    foreach(Bewertung::$werte as $wert=>$t) {
?>
      <div style="display:inline-block;background-color:<?= Bewertung::$farben[$wert] ?>;padding-right:2px;"><input type="radio" name="<?= $frage ?>" value="<?= $wert ?>" <?= isset($bewertungen[$frage]) && $bewertungen[$frage]->wert==$wert ? 'checked' : '' ?> /></div><?= $wert ?> &nbsp;
<?php
    }
?>
    </td>
    <td><button type="button" onclick="bewertung_loeschen('<?= $frage ?>')">löschen</button></td>
  </tr>
<?php
  }
?>
  <tr>
    <th align="right">Feedback</th>
    <td colspan="2"><textarea name="feedback" style="width:250px;height:100px;"><?= isset($bewertungen['info']) ? $bewertungen['info']->feedback : '' ?></textarea></td>
  </tr>
  <tr>
    <th align="right"><input type="checkbox" name="anonym" value="J" <?= !isset($bewertungen['info']) || $bewertungen['info']->anonym ? 'checked' : '' ?> />Anonym bewerten</th>
    <td colspan="2">
      <input type="submit" value="Speichern" />
<?php
  if(isset($_SESSION['bewertungnachricht'])) {
    echo $_SESSION['bewertungnachricht'];
    unset($_SESSION['bewertungnachricht']);
  }
?>
    </td>
  </tr>
</table>
</form>
<?php
}
?>
<a href="kurse.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>