<?php
require_once 'check_login.php';
require_once 'IntrainTn.php';

$tn=IntrainTn::einenLaden(isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0,'IntrainTn');
if(empty($tn)) {
  header('Location:tn.php');
  exit;
}
require_once 'IntrainSeite.php';
$seite=new IntrainSeite('TN '.$tn->tnname);
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
    <td><input type="checkbox" name="gender" value="J" <?= $tn->anrede!=$tn->mitisanrede || $tn->vorname!=$tn->mitisvorname ? 'checked' : '' ?> /> Abweichend von MITIS</td>
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
<a href="tn.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>