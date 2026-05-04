<?php
require_once '../Raum.php';
class VerwaltungRaum extends Raum {
  function makeBearbeitenTd() {
?>
    <td>
      <a href="raum_sehen.php?raumid=<?= $this->id ?>">Sehen</a>
      <a href="raum_bearbeiten.php?raumid=<?= $this->id ?>">Bearbeiten</a>
      <a href="raum_kurse.php?raumid=<?= $this->id ?>">Kurse</a>
    </td>
<?php
  }
}
?>