<?php
require_once '../Klasse.php';
class VerwaltungKlasse extends Klasse {
  function makeAnzahlTNTr() {
?>
  <tr>
    <th>Anzahl TN</th>
    <td>
      <?= $this->anzahlAnmeldungen ?> angemeldet<br />
      <?= $this->anzahlTN ?> eingestiegen
    </td>
  </tr>
<?php
  }
  function makeBearbeitenTd() {
    global $ich;
?>
    <td>
      <a href="klasse_sehen.php?klasseid=<?= $this->id ?>">Sehen</a>
      <a href="klasse_tn.php?klasseid=<?= $this->id ?>">TN</a>
      <a href="klasse_kurse.php?klasseid=<?= $this->id ?>">Kurse</a>
<?php
    if($ich->istleiter) {
?>
      <a href="../leitung/klasse_kurse_bewertung.php?klasseid=<?= $this->id ?>">Modulbewertung</a>
      <a href="klasse_sollplanvergleich.php?klasseid=<?= $this->id ?>" target="sollplanvergleich">
<?php
    }
?>
    </td>
<?php
  }
}
?>