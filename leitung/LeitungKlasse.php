<?php
require_once '../Klasse.php';
class LeitungKlasse extends Klasse {
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
?>
    <td>
      <a href="../verwaltung/klasse_sehen.php?klasseid=<?= $this->id ?>">Sehen</a>
      <a href="../verwaltung/klasse_tn.php?klasseid=<?= $this->id ?>">TN</a>
      <a href="../verwaltung/klasse_kurse.php?klasseid=<?= $this->id ?>">Kurse</a>
      <a href="klasse_kurse_bewertung.php?klasseid=<?= $this->id ?>">Modulbewertung</a>
      <a href="klasse_sollplanvergleich.php?klasseid=<?= $this->id ?>" target="sollplanvergleich">Soll-Plan-Vergleich</a>
    </td>
<?php
  }
}
?>