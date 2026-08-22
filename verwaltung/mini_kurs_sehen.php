<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once 'VerwaltungKurs.php';
require_once '../MiniAnhang.php';

$kurs = Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0, 'VerwaltungKurs');
if (empty($kurs)) {
  header('Location:kurse.php');
  exit;
}

// Paragraphen laden
$paragraphen = array();
$result = $db->query(
  "SELECT * FROM `gpb_mini_paragraph` WHERE kursid=" . $kurs->id . " ORDER BY nummer ASC"
);
while ($row = $result->fetch_object()) {
  $paragraphen[] = $row;
}
$result->free();

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite = new VerwaltungSeite('Mini-Kurs: ' . $kurs->titel);
$seite->anfangGenerieren();

$kurs->makeSehen('sehen');
?>

<h2>Kursinhalt (Vorschau)</h2>

<?php if (empty($paragraphen)): ?>
<p><em>Noch keine Inhalte vorhanden.</em></p>
<?php else: ?>
<div class="mini-paragraphen">
<?php foreach ($paragraphen as $p): ?>
  <div class="mini-paragraph" style="border:1px solid #ccc; margin-bottom:1em; padding:0.5em;">
    <h3><?= htmlspecialchars($p->titel) ?></h3>
    <div class="mini-inhalt"><?= nl2br($p->inhalt) ?></div>
    <?php $anhaenge = miniAnhangListeLaden($p->id); ?>
    <?php if (!empty($anhaenge)): ?>
    <div class="mini-anhaenge" style="margin-top:0.5em;">
      <strong>Anhänge:</strong>
      <?php foreach ($anhaenge as $a): ?>
        <?php miniAnhangAnzeigen($a, '../'); ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<br />
<a href="kurs_sehen.php?kursid=<?= $kurs->id ?>">Zurück zur Kursseite</a>

<?php
$seite->endeGenerieren();
?>
