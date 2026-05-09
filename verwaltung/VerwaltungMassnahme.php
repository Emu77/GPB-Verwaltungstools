<?php
require_once '../Massnahme.php';
class VerwaltungMassnahme extends Massnahme {
  function makeBearbeitenTd() {
    global $ich;
?>
    <td>
      <a href="massnahme_sehen.php?massnahmeid=<?= $this->id ?>">Sehen</a>
      <a href="massnahme_tn.php?massnahmeid=<?= $this->id ?>">TN</a>
      <a href="massnahme_kurse.php?massnahmeid=<?= $this->id ?>">Kurse der TN</a>
<?php
    if($ich->istleiter) {
?>
      <a href="../leitung/massnahme_kurse_bewertung.php?massnahmeid=<?= $this->id ?>">Modulbewertungen</a>
<?php
    }
?>
      <a href="massnahme_tagesbericht.php?massnahmeid=<?= $this->id ?>">Tagesbericht</a>
    </td>
<?php
  }
}
?>