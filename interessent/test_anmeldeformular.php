<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <link rel="stylesheet" href="moodle_styles.css" />
  <title>GPB - Anmeldung zum Eignungstest</title>
</head>
<body>
<h1>Anmeldung zum Eignungstest</h1>
Wenn die Übungstests gut klappen, rufen Sie uns bitte an, um sich zum Eignungstest anzumelden!<br />
<br />
Wir freuen uns auf Ihren Besuch.
</body>
</html>
<?php
exit;
@session_start();
require_once '../db.php';

if(isset($_COOKIE['anmeldung'])) {
  $_SESSION['anmeldung']=json_decode($_COOKIE['anmeldung']);
}
if(isset($_SESSION['anmeldung'])) {
  $adressen=array(
    ''=>"Adresse zum Zeitpunkt der Anmeldung noch nicht bekannt, siehe Mail"
    ,'Online'=>"Online - Adresse und Zugangsdaten bekommen Sie per Mail"
    ,'Mitte'=>"GPB Beratung Mitte\nBeuthstraße 8\nEG\n10117 Berlin"
    ,'Neukölln'=>"GPB Beratung Neukölln\nKarl-Marx-Straße 272\n12057 Berlin"
  );
} else {
  $testmoodleid=isset($_GET['testmoodleid']) ? (int)$_GET['testmoodleid'] : 0;
    
  $termine=array();
  $stmt=$db->prepare("select * from gpb_eignungstest_termin where wann>now() order by wann");
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $row->zeit=strtotime($row->wann);
    $termine[]=$row;
  }
  $result->free();
}

$tagnamen=array(
  'Mon'=>'Montag',
  'Tue'=>'Dienstag',
  'Wed'=>'Mittwoch',
  'Thu'=>'Donnerstag',
  'Fri'=>'Freitag',
  'Sat'=>'Samstag',
  'Sun'=>'Sonntag'
);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <link rel="stylesheet" href="moodle_styles.css" />
  <title>GPB - Anmeldung zum Eignungstest</title>
</head>
<body>
<h1>Anmeldung zum Eignungstest</h1>
<?php
if(isset($_SESSION['anmeldung'])) {
  $anmeldung=$_SESSION['anmeldung'];
  $anmeldung->zeit=strtotime($anmeldung->wann);
?>
Vielen Dank für Ihr Interesse! Wir haben folgende Informationen notiert.<br />
Bitte rufen Sie uns an, falls Sie sie ändern möchten.<br />
<br />
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Vorname</th>
    <td><?= $anmeldung->vorname ?></td>
  </tr>
  <tr>
    <th>Nachname</th>
    <td><?= $anmeldung->nachname ?></td>
  </tr>
  <tr>
    <th>Termin</th>
    <td><?= $tagnamen[date('D',$anmeldung->zeit)] ?> <?= date('d.m.Y',$anmeldung->zeit) ?></td>
  </tr>
  <tr>
    <th>Uhrzeit</th>
    <td><?= date('H:i',$anmeldung->zeit) ?> Uhr</td>
  </tr>
  <tr>
    <th>Ort</th>
    <td><?= nl2br($adressen[$anmeldung->ort]) ?></td>
  </tr>
</table>
<br />
Wir freuen uns auf Ihren Besuch!
<?php
} else if(empty($termine)) {
?>
Für diesen Test sind noch keine Termine geplant. Rufen Sie uns an!
<?php
} else {
?>
<form action="test_anmelden.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="testmoodleid" value="<?= $testmoodleid ?>" />
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Vorname</th>
    <td><input type="text" name="vorname" value="" style="width:150px;" /></td>
  </tr>
  <tr>
    <th>Nachname</th>
    <td><input type="text" name="nachname" value="" style="width:150px;" /></td>
  </tr>
  <tr>
    <th>Termin</th>
    <td><select name="terminid">
<?php
  foreach($termine as $termin) {
?>
      <option value="<?= $termin->id ?>"><?= $tagnamen[date('D',$termin->zeit)] ?> <?= date('d.m.Y H:i',$termin->zeit) ?> Uhr</option>
<?php
  }
?>
    </select></td>
  </tr>
  <tr>
    <th></th>
    <td><input type="submit" value="Anmelden" /></td>
  </tr>
</table>
</form>
<?php
}
?>
</body>
</html>