<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once 'VerwaltungVerwalter.php';

if(isset($_GET['verwalterid']) && $_GET['verwalterid']!='0') {
  $verwalter=VerwaltungVerwalter::einenLaden((int)$_GET['verwalterid'],'VerwaltungVerwalter');
  if(empty($verwalter)) {
    header('Location:verwalter.php');
    exit;
  }
} else {
  $verwalter=(object)array(
    'id'=>0,
    'anrede'=>'',
    'vorname'=>'',
    'nachname'=>'',
    'nutzername'=>'',
    'email'=>'',
    'moodleid'=>0,
    'massnahmenanzeigen'=>true,
    'ort'=>''
  );
}

if(isset($_SESSION['fehler']['verwalter'])) {
  $fehler=$_SESSION['fehler'];
  unset($_SESSION['fehler']);
  foreach($fehler['verwalter'] as $k=>$v) {
    $verwalter->$k=$v;
  }
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Verwalter '.$verwalter->vorname.' '.$verwalter->nachname.' bearbeiten');
$seite->anfangGenerieren();
?>
<form action="verwalter_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="id" value="<?= $verwalter->id ?>" />
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="bearbeiten">
  <tr>
    <th>Anrede</th>
    <td><select name="anrede">
      <option value=""></option>
      <option value="Frau" <?= $verwalter->anrede=='Frau' ? 'selected' : '' ?>>Frau</option>
      <option value="Herr" <?= $verwalter->anrede=='Herr' ? 'selected' : '' ?>>Herr</option>
    </select></td>
    <td class="fehler"><?= isset($fehler['anrede']) ? $fehler['anrede'] : '' ?></td>
  </tr>
  <tr>
    <th>Vorname</th>
    <td><input type="text" name="vorname" value="<?= htmlentities($verwalter->vorname,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['vorname']) ? $fehler['vorname'] : '' ?></td>
  </tr>
  <tr>
    <th>Nachname</th>
    <td><input type="text" name="nachname" value="<?= htmlentities($verwalter->nachname,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['nachname']) ? $fehler['nachname'] : '' ?></td>
  </tr>
  <tr>
    <th>Nutzername</th>
    <td><input type="text" name="nutzername" value="<?= htmlentities($verwalter->nutzername,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['nutzername']) ? $fehler['nutzername'] : '' ?></td>
  </tr>
  <tr>
    <th>Neues Passwort</th>
    <td><input type="password" name="passwort" value="" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['passwort']) ? $fehler['passwort'] : '' ?></td>
  </tr>
  <tr>
    <th>Email</th>
    <td><input type="text" name="email" value="<?= htmlentities($verwalter->email,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['email']) ? $fehler['email'] : '' ?></td>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">
<?php
if($verwalter->moodleid>0) {
?>
      <a href="<?= $moodleurl ?>user/profile.php?id=<?= $verwalter->moodleid ?>" target="moodle"><?= $verwalter->moodleid ?></a><br />
<?php
} else {
?>
      <input type="checkbox" name="moodlenutzererstellen" value="J" /> Moodle-Nutzer erstellen<br />
<?php
}
?>
      <input type="number" name="moodleid" value="<?= $verwalter->moodleid ?>" style="width:50px;" />
    </td>
    <td class="fehler"><?= isset($fehler['moodleid']) ? $fehler['moodleid'] : '' ?></td>
  </tr>
  <tr>
    <th>Maßnahmen anzeigen</th>
    <td><input type="checkbox" name="massnahmenanzeigen" value="J" <?= $verwalter->massnahmenanzeigen ? 'checked' : '' ?> /></td>
    <td class="fehler"><?= isset($fehler['massnahmenanzeigen']) ? $fehler['massnahmenanzeigen'] : '' ?></td>
  </tr>
  <tr>
    <th>Ort</th>
    <td><select name="ort">
      <option value="">(alle)</option>
      <option value="Mitte" <?= $verwalter->ort=='Mitte' ? 'selected' : '' ?>>Mitte</option>
      <option value="Neukölln" <?= $verwalter->ort=='Neukölln' ? 'selected' : '' ?>>Neukölln</option>
    </select></td>
    <td class="fehler"><?= isset($fehler['massnahmenanzeigen']) ? $fehler['massnahmenanzeigen'] : '' ?></td>
  </tr>
  <tr>
    <th></th>
    <td><input type="submit" value="Speichern" /> <a href="verwalter_sehen.php?verwalterid=<?= $verwalter->id ?>">Abbrechen</a></td>
    <td class="fehler"><?= isset($fehler['speichern']) ? $fehler['speichern'] : '' ?></td>
  </tr>
</table>
</form>
<?php
$seite->endeGenerieren();
?>