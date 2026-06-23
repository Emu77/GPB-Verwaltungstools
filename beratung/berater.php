<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'BeratungBerater.php';

$berater=array();
$result=$db->query("select *
  from gpb_berater
  order by nachname,vorname");
while($row=$result->fetch_object('BeratungBerater')) {
  $berater[]=$row;
}
$result->free();

require_once 'BeratungSeite.php';
$seite=BeratungSeite::$menueByUrl['berater.php'];
$seite->anfangGenerieren();
?>
<a href="berater_bearbeiten.php?beraterid=0">Neuer Berater</a><br />
<br />
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Anrede</th>
    <th>Vorname</th>
    <th>Nachname</th>
    <th>Nutzername</th>
    <th>Tel</th>
    <th>Email</th>
    <th>Ort</th>
    <th>Signatur</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th></th>
  </tr>
<?php
foreach($berater as $row) {
?>
  <tr>
    <td><?= $row->anrede ?></td>
    <td><?= $row->vorname ?></td>
    <td><?= $row->nachname ?></td>
    <td><?= $row->nutzername ?></td>
    <td><?= $row->tel ?></td>
    <td><a href="mailto:<?= htmlentities($row->email,ENT_COMPAT) ?>"><?= $row->email ?></a></td>
    <td><?= $row->ort ?></td>
    <td><?= $row->signaturbild ?></td>
<?php
  $row->makeMoodleTd();
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