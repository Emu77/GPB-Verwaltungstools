<?php
require_once '../Massnahme.php';
class LeitungMassnahme extends Massnahme {
  function makeBearbeitenTd() {
?>
    <td>
      <a href="../verwaltung/massnahme_sehen.php?massnahmeid=<?= $this->id ?>">Sehen</a>
      <a href="../verwaltung/massnahme_tn.php?massnahmeid=<?= $this->id ?>">TN</a>
      <a href="../verwaltung/massnahme_kurse.php?massnahmeid=<?= $this->id ?>">Kurse der TN</a>
      <a href="massnahme_kurse_bewertung.php?massnahmeid=<?= $this->id ?>">Modulbewertungen</a>
      <a href="../verwaltung/massnahme_tagesbericht.php?massnahmeid=<?= $this->id ?>">Tagesbericht</a>
    </td>
<?php
  }
}
?>