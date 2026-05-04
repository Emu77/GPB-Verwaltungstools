<?php
require_once '../Dozent.php';
class VerwaltungDozent extends Dozent {
  function makeSehen($classname='') {
    global $moodleisttest;
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="<?= $classname ?>">
  <tr>
    <th>Anrede</th>
    <td><?= $this->anrede ?></td>
  </tr>
  <tr>
    <th>Vorname</th>
    <td><?= $this->vorname ?></td>
  </tr>
  <tr>
    <th>Nachname</th>
    <td><?= $this->nachname ?></td>
  </tr>
  <tr>
    <th>Nutzername</th>
    <td><?= $this->nutzername ?></td>
  </tr>
<?php
    $this->makeKontaktTrs();
    $this->makeIntrainTr();
    $this->makeVerfuegbarTr();
?>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
<?php
    $this->makeMoodleTd();
?>
  </tr>
  <tr>
    <th></th>
<?php
    $this->makeBearbeitenTd();
?>
  </tr>
</table>
<br />
<?php
  }
  function makeIntrainTr() {
    global $ich;
    if($ich->istleiter) {
?>
  <tr>
    <th>Intrain-Manager</th>
    <td><?= $this->istintrain ? 'Ja' : 'Nein' ?></td>
  </tr>
<?php
    }
  }
  function makeVerfuegbarTr() {
    global $ich;
    if($ich->istleiter) {
?>
  <tr>
    <th>Für Planung verfügbar</th>
    <td>IdR. <?= $this->idrverfuegbar ? 'ja' : 'nein' ?></td>
  </tr>
<?php
    }
  }
  function makeBearbeitenTd() {
    global $ich;
?>
    <td>
      <a href="../verwaltung/dozent_bearbeiten.php?dozentid=<?= $this->id ?>">Bearbeiten</a>
      <a href="../verwaltung/dozent_sehen.php?dozentid=<?= $this->id ?>">Sehen</a>
      <a href="../verwaltung/dozent_loginas.php?dozentid=<?= $this->id ?>">LoginAs</a>
      <a href="../verwaltung/dozent_kurse.php?dozentid=<?= $this->id ?>">Kurse</a>
<?php
    if($ich->istleiter) {
?>
      <a href="../leitung/dozent_verfuegbarkeit.php?dozentid=<?= $this->id ?>">Verfügbarkeit</a>
      <a href="../leitung/dozent_module.php?dozentid=<?= $this->id ?>">Skills</a>
<?php
    }
?>
    </td>
<?php
  }
}
?>