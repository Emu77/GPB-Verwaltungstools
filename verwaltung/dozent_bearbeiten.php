<?php
require_once 'check_login.php';
require_once 'VerwaltungDozent.php';

if(isset($_GET['dozentid']) && $_GET['dozentid']!='0') {
  $dozent=Dozent::einenLaden((int)$_GET['dozentid'],'VerwaltungDozent');
  if(empty($dozent)) {
    header('Location:dozenten.php');
    exit;
  }
} else {
  $dozent=(object)array(
    'id'=>0,
    'anrede'=>'',
    'vorname'=>'',
    'nachname'=>'',
    'nutzername'=>'',
    'moodleid'=>0,
    'email'=>'',
    'tel1'=>'',
    'tel2'=>'',
    'istintrain'=>0,
    'idrverfuegbar'=>0
  );
}

if(isset($_SESSION['fehler']['dozent'])) {
  $fehler=$_SESSION['fehler'];
  unset($_SESSION['fehler']);
  foreach($fehler['dozent'] as $k=>$v) {
    $dozent->$k=$v;
  }
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Dozent '.$dozent->vorname.' '.$dozent->nachname.' bearbeiten');
$seite->anfangGenerieren();
?>
<form action="dozent_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="id" value="<?= $dozent->id ?>" />
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="bearbeiten">
  <tr>
    <th>Anrede</th>
    <td><select name="anrede">
      <option value=""></option>
      <option value="Frau" <?= $dozent->anrede=='Frau' ? 'selected' : '' ?>>Frau</option>
      <option value="Herr" <?= $dozent->anrede=='Herr' ? 'selected' : '' ?>>Herr</option>
    </select></td>
    <td class="fehler"><?= isset($fehler['anrede']) ? $fehler['anrede'] : '' ?></td>
  </tr>
  <tr>
    <th>Vorname</th>
    <td><input type="text" name="vorname" value="<?= htmlentities($dozent->vorname,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['vorname']) ? $fehler['vorname'] : '' ?></td>
  </tr>
  <tr>
    <th>Nachname</th>
    <td><input type="text" name="nachname" value="<?= htmlentities($dozent->nachname,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['nachname']) ? $fehler['nachname'] : '' ?></td>
  </tr>
  <tr>
    <th>Nutzername</th>
    <td><input type="text" name="nutzername" value="<?= htmlentities($dozent->nutzername,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['nutzername']) ? $fehler['nutzername'] : '' ?></td>
  </tr>
  <tr>
    <th>Neues Passwort</th>
    <td>
      <input type="checkbox" name="createpassword" value="J" <?= isset($dozent->createpassword) && $dozent->createpassword ? 'checked' : '' ?> />&nbsp;Passwort von Moodle generieren lassen<br />
      <input type="password" name="passwort" value="" style="width:150px;" /><br />
      <input type="checkbox" name="mussaendern" value="J" <?= isset($dozent->mussaendern) && $dozent->mussaendern ? 'checked' : '' ?> />&nbsp;Passwort muss in Moodle geändert werden
    </td>
    <td class="fehler"><?= isset($fehler['passwort']) ? $fehler['passwort'] : '' ?></td>
  </tr>
  <tr>
    <th>Email</th>
    <td><input type="text" name="email" value="<?= htmlentities($dozent->email,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td class="fehler"><?= isset($fehler['email']) ? $fehler['email'] : '' ?></td>
  </tr>
  <tr>
    <th>Tel</th>
    <td>
      <input type="text" name="tel1" value="<?= htmlentities($dozent->tel1,ENT_COMPAT) ?>" style="width:150px;" /><br />
      <input type="text" name="tel2" value="<?= htmlentities($dozent->tel2,ENT_COMPAT) ?>" style="width:150px;" />
    </td>
    <td class="fehler">
      <?= isset($fehler['tel1']) ? $fehler['tel1'] : '' ?>
      <?= isset($fehler['tel2']) ? $fehler['tel2'] : '' ?>
    </td>
  </tr>
<?php
if($ich->istleiter) {
?>
  <tr>
    <th>Intrain-Manager</th>
    <td><input type="checkbox" name="istintrain" value="J" <?= $dozent->istintrain ? 'checked' : '' ?> /></td>
    <td class="fehler"><?= isset($fehler['istintrain']) ? $fehler['istintrain'] : '' ?></td>
  </tr>
  <tr>
    <th>Für Planung verfügbar</th>
    <td><input type="checkbox" name="idrverfuegbar" value="J" <?= $dozent->idrverfuegbar ? 'checked' : '' ?> /> IdR verfügbar</td>
    <td class="fehler"><?= isset($fehler['idrverfuegbar']) ? $fehler['idrverfuegbar'] : '' ?></td>
  </tr>
<?php
}
?>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <td>
<?php
if($dozent->moodleid>0) {
?>
      <a href="<?= $moodleurl ?>user/profile.php?id=<?= $dozent->moodleid ?>" target="moodle"><?= $dozent->moodleid ?></a><br />
<?php
} else {
?>
      <input type="checkbox" name="moodlenutzererstellen" value="J" /> Moodle-Nutzer erstellen<br />
<?php
}
?>
      <input type="number" name="moodleid" value="<?= htmlentities($dozent->moodleid,ENT_COMPAT) ?>" style="width:50px;" />
    </td>
    <td class="fehler"><?= isset($fehler['moodleid']) ? $fehler['moodleid'] : '' ?></td>
  </tr>
  <tr>
    <th></th>
    <td><input type="submit" value="Speichern" /> <a href="dozent_sehen.php?dozentid=<?= $dozent->id ?>">Abbrechen</a></td>
    <td class="fehler"><?= isset($fehler['speichern']) ? $fehler['speichern'] : '' ?></td>
  </tr>
</table>
</form>
<?php
$seite->endeGenerieren();
?>