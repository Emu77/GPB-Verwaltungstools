<?php
require_once '../Kursvorlage.php';
class DozentKursvorlage extends Kursvorlage {
  function makeDozentenTd() {
?>
    <td class="dozenten">
<?php
    foreach($this->dozenten as $d) {
?>
      <div><?= $d->vorname ?> <?= $d->nachname ?></div>
<?php
    }
?>
    </td>
<?php
  }
  function makeBearbeitenTd() {
    global $ich;
?>
    <td class="aktionen">
      <a href="kursvorlage_sehen.php?kursvorlageid=<?= $this->id ?>">Sehen</a>
      <a href="kursvorlage_bearbeiten.php?kursvorlageid=<?= $this->id ?>">Bearbeiten</a>
    </td>
<?php
  }
}
?>