<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungVerwalter.php';

$verwalter=array();
$result=$db->query("select verw.*
  from gpb_verwalter verw
  order by nachname,vorname");
while($row=$result->fetch_object('VerwaltungVerwalter')) {
  $verwalter[]=$row;
}
$result->free();

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=VerwaltungSeite::$menueByUrl['../verwaltung/verwalter.php'];
$seite->anfangGenerieren();
?>
<a href="verwalter_bearbeiten.php?verwalterid=0">Neuer Verwalter</a><br />
<br />
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Anrede</th>
    <th>Vorname</th>
    <th>Nachname</th>
    <th>Nutzername</th>
    <th>Email</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th>Maßnahmen<br />anzeigen</th>
    <th>Ort</th>
    <th></th>
  </tr>
<?php
foreach($verwalter as $row) {
?>
  <tr>
    <td><?= $row->anrede ?></td>
    <td><?= $row->vorname ?></td>
    <td><?= $row->nachname ?></td>
    <td><?= $row->nutzername ?></td>
    <td><a href="mailto:<?= htmlentities($row->email,ENT_COMPAT) ?>"><?= $row->email ?></a></td>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">
<?php
  if($row->moodleid>0) {
?>
    <a href="<?= $moodleurl ?>user/profile.php?id=<?= $row->moodleid ?>" target="moodle"><?= $row->moodleid ?></a>
<?php
  }
?>
    </td>
    <td align="center"><?= $row->massnahmenanzeigen ? 'Ja' : 'Nein' ?></td>
    <td><?= $row->ort ?></td>
<?php
  $row->makeBearbeitenTd();
?>
  </tr>
<?php
}
?>
</table>
<?php
$seite->endeGenerieren();
?>