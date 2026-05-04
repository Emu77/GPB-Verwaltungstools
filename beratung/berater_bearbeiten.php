<?php
require_once 'check_login.php';
require_once 'BeratungBerater.php';

if(isset($_GET['beraterid']) && $_GET['beraterid']!='0') {
  $berater=BeratungBerater::einenLaden((int)$_GET['beraterid'],'BeratungBerater');
  if(empty($berater)) {
    header('Location:berater.php');
    exit;
  }
} else {
  $berater=(object)array(
    'id'=>0,
    'anrede'=>'',
    'vorname'=>'',
    'nachname'=>'',
    'nutzername'=>'',
    'tel'=>'',
    'email'=>'',
    'ort'=>'',
    'signaturbild'=>'',
    'moodleid'=>0
  );
}

if(isset($_SESSION['fehler']['berater'])) {
  $fehler=$_SESSION['fehler'];
  unset($_SESSION['fehler']);
  foreach($fehler['berater'] as $k=>$v) {
    $berater->$k=$v;
  }
}

require_once 'BeratungSeite.php';
$seite=new BeratungSeite('Berater '.$berater->vorname.' '.$berater->nachname.' bearbeiten');
$seite->anfangGenerieren();
?>
<form action="berater_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="id" value="<?= $berater->id ?>" />
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="bearbeiten">
  <tr>
    <th>Anrede</th>
    <td><select name="anrede">
      <option value=""></option>
      <option value="Frau" <?= $berater->anrede=='Frau' ? 'selected' : '' ?>>Frau</option>
      <option value="Herr" <?= $berater->anrede=='Herr' ? 'selected' : '' ?>>Herr</option>
    </select></td>
    <td class="fehler"><?= isset($fehler['anrede']) ? $fehler['anrede'] : '' ?></td>
  </tr>
  <tr>
    <th>Vorname</th>
    <td><input type="text" name="vorname" value="<?= htmlentities($berater->vorname,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['vorname']) ? $fehler['vorname'] : '' ?></td>
  </tr>
  <tr>
    <th>Nachname</th>
    <td><input type="text" name="nachname" value="<?= htmlentities($berater->nachname,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['nachname']) ? $fehler['nachname'] : '' ?></td>
  </tr>
  <tr>
    <th>Nutzername</th>
    <td><input type="text" name="nutzername" value="<?= htmlentities($berater->nutzername,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['nutzername']) ? $fehler['nutzername'] : '' ?></td>
  </tr>
  <tr>
    <th>Neues Passwort</th>
    <td>
      <input type="checkbox" name="createpassword" value="J" <?= isset($berater->createpassword) && $berater->createpassword ? 'checked' : '' ?> />&nbsp;Passwort von Moodle generieren lassen<br />
      <input type="password" name="passwort" value="" style="width:150px;" /><br />
      <input type="checkbox" name="mussaendern" value="J" <?= isset($berater->mussaendern) && $berater->mussaendern ? 'checked' : '' ?> />&nbsp;Passwort muss in Moodle geändert werden
    </td>
    <td class="fehler"><?= isset($fehler['passwort']) ? $fehler['passwort'] : '' ?></td>
  </tr>
  <tr>
    <th>Tel</th>
    <td><input type="text" name="tel" value="<?= htmlentities($berater->tel,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['tel']) ? $fehler['tel'] : '' ?></td>
  </tr>
  <tr>
    <th>Email</th>
    <td><input type="text" name="email" value="<?= htmlentities($berater->email,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['email']) ? $fehler['email'] : '' ?></td>
  </tr>
  <tr>
    <th>Ort</th>
    <td><select name="ort">
      <option value="">(alle)</option>
      <option value="Mitte" <?= $berater->ort=='Mitte' ? 'selected' : '' ?>>Mitte</option>
      <option value="Neukölln" <?= $berater->ort=='Neukölln' ? 'selected' : '' ?>>Neukölln</option>
    </select></td>
    <td class="fehler"><?= isset($fehler['massnahmenanzeigen']) ? $fehler['massnahmenanzeigen'] : '' ?></td>
  </tr>
  <tr>
    <th>Signatur</th>
    <td>
<?php
if(!empty($berater->signaturbild)) {
?>
      <?= $berater->signaturbild ?><br />
      <input type="checkbox" name="signaturbild_loeschen" value="J"> Löschen<br />
      oder ersetzen durch 
<?php
}
?>
      <input type="file" name="signaturbild" />
    </td>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <td>
<?php
if($berater->moodleid>0) {
?>
      <a href="<?= $moodleurl ?>user/profile.php?id=<?= $berater->moodleid ?>" target="moodle"><?= $berater->moodleid ?></a><br />
<?php
} else {
?>
      <input type="checkbox" name="moodlenutzererstellen" value="J" /> Moodle-Nutzer erstellen<br />
<?php
}
?>
      <input type="number" name="moodleid" value="<?= htmlentities($berater->moodleid,ENT_COMPAT) ?>" style="width:50px;" />
    </td>
    <td class="fehler"><?= isset($fehler['moodleid']) ? $fehler['moodleid'] : '' ?></td>
  </tr>
  <tr>
    <th></th>
    <td><input type="submit" value="Speichern" /> <a href="berater_sehen.php?beraterid=<?= $berater->id ?>">Abbrechen</a></td>
    <td class="fehler"><?= isset($fehler['speichern']) ? $fehler['speichern'] : '' ?></td>
  </tr>
</table>
</form>
<?php
$seite->endeGenerieren();
?>