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
<<<<<<< HEAD
      <a href="klasse_sollplanvergleich.php?klasseid=<?= $this->id ?>" target="sollplanvergleich">
=======
      <a href="../leitung/klasse_sollplanvergleich.php?klasseid=<?= $this->id ?>" target="sollplanvergleich">Soll-Plan-Vergleich</a>
>>>>>>> 9da4159481b70c6eec1600617e27d80c11728803
<?php
    }
?>
    </td>
<?php
  }
}
?>