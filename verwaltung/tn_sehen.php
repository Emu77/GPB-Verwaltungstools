<?php
require_once 'check_login.php';
require_once 'VerwaltungTn.php';

$tn=VerwaltungTn::einenLaden(isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0,'VerwaltungTn');
if(empty($tn)) {
  header('Location:tn.php');
  exit;
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('TN '.$tn->tnname);
$seite->anfangGenerieren();
$tn->makeSehen('sehen');
if($tn->moodleid>0) {
?>
<script>
function passwort_zuruecksetzen() {
  if(confirm('Moodle-Passwort automatisch zurücksetzen, sicher?')) {
    location.href='tn_passwort_reset.php?tnid=<?= $tn->id ?>&moodleid=<?= $tn->moodleid ?>';
  }
}
</script>
<input type="button" value="Moodle-Passwort automatisch zurücksetzen" onclick="passwort_zuruecksetzen()" style="margin-top:2em;" /><br />
<?php
if(isset($_SESSION['passwortresetnachricht'])) {
  echo $_SESSION['passwortresetnachricht'];
  unset($_SESSION['passwortresetnachricht']);
}
?>
oder
<?php
} else {
?>
<form action="tn_moodleid_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="tnid" value="<?= $tn->id ?>" />
  Moodle-ID: <input type="text" name="moodleid" value="" style="width:150px;" />
  <input type="submit" value="Speichern" />
</form>
<?php
}
?>
<script>
function passwort_speichern() {
  if(confirm('Neues Passwort speichern, sicher?')) {
    document.getElementById('passwort_speichern_form').submit();
  }
}
</script>
<form id="passwort_speichern_form" action="tn_passwort_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8" <?= $tn->moodleid>0 ? '' : 'style="margin-top:2em;"' ?>>
  <input type="hidden" name="tnid" value="<?= $tn->id ?>" />
  <input type="hidden" name="moodleid" value="<?= $tn->moodleid ?>" />
<?php
if(isset($_SESSION['passwortspeichernnachricht'])) {
  echo $_SESSION['passwortspeichernnachricht'];
  unset($_SESSION['passwortspeichernnachricht']);
}
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Neues Passwort</th>
    <td><input type="password" name="passwort" /></td>
  </tr>
<?php
if($tn->moodleid>0) {
?>
  <tr>
    <th>TN muss sein Passwort in Moodle ändern</th>
    <td><input type="checkbox" name="mussaendern" value="J" checked /></td>
  </tr>
<?php
}
?>
  <tr>
    <th></th>
    <td><input type="button" value="Neues Passwort speichern" onclick="passwort_speichern()" /></td>
  </tr>
</table>
</form>
<form action="tn_gender_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8" style="margin-top:2em;margin-bottom:2em;">
  <input type="hidden" name="tnid" value="<?= $tn->id ?>" />
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Gender</th>
    <td><input type="checkbox" name="gender" value="J" <?= $tn->mitisanrede!=$tn->anrede || $tn->mitisvorname!=$tn->vorname ? 'checked' : '' ?> /> Abweichend von MITIS</td>
  </tr>
  <tr>
    <th>Anrede</th>
    <td><select name="anrede">
      <option value="">Keine Anrede</option>
      <option value="Frau" <?= $tn->anrede=='Frau' ? 'selected' : '' ?>>Frau</option>
      <option value="Herr" <?= $tn->anrede=='Herr' ? 'selected' : '' ?>>Herr</option>
    </select></td>
  </tr>
  <tr>
    <th>Vorname</th>
    <td><input type="text" name="vorname" value="<?= htmlentities($tn->vorname,ENT_COMPAT) ?>" style="width:150px;" /></td>
  </tr>
  <tr>
    <th></th>
    <td><input type="submit" value="Speichern" /></td>
  </tr>
</table>
</form>
<?php
if(!empty($tn->fehlzeiten_notiz)) {
?>
<form action="tn_hohefehlzeiten_notiz_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8" style="margin-bottom:2em;">
  <input type="hidden" name="tnid" value="<?= $tn->id ?>" />
  <input type="hidden" name="redirect" value="J" />
  <b>Fehlzeitennotiz</b><br />
  <textarea name="notiz" style="width:200px;height:5em;"><?= $tn->fehlzeiten_notiz ?></textarea><br />
  <input type="submit" value="Änderungen speichern" />
</form>
<?php
}
if(!empty($tn->EABmassnahme)) {
  $schuljahr=(int)date('Y')+(date('m')>='08' ? 0 : -1);
?>
<form action="tn_bafoegformblatt2_pdf.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8" style="margin-bottom:2em;">
  <input type="hidden" name="tnid" value="<?= $tn->id ?>" />
  <b>BAföG Formblatt 2</b><br />
  <select name="schuljahr">
<?php
  for($j=$schuljahr-5;$j<=$schuljahr+5;++$j) {
?>
    <option value="<? $j ?>" <?= $j==$schuljahr ? 'selected' : '' ?>><?= $j ?>/<?= $j+1 ?></option>
<?php
  }
?>
  </select>
  <input type="submit" value="PDF generieren" />
</form>
<?php
}
?>
<a href="tn.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>