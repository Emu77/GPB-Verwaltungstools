<?php
require_once '../Ferien.php';
class BeratungFerien extends Ferien {
  function makeBearbeitenTd() {
?>
    <td>
      <a href="ferien_sehen.php?ferienid=<?= $this->id ?>">Sehen</a>
      <a href="ferien_bearbeiten.php?ferienid=<?= $this->id ?>">Bearbeiten</a>
    </td>
<?php
  }
}
?>