<?php
require_once '../Ferien.php';

class TnFerien extends Ferien {
  function makeKlassenTd() {
?>
    <td class="klassen">
<?php
    foreach($this->klassen as $k) {
?>
      <div><?= $k->bezeichnung ?></div>
<?php
    }
?>
    </td>
<?php
  }
  
  function makeBearbeitenTd() {
?>
    <td><a href="ferien_sehen.php?ferienid=<?= $this->id ?>">Sehen</a></td>
<?php
  }
}
?>