<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'TnKurs.php';

$kurs = Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0, 'TnKurs');
if (empty($kurs)) {
  header('Location:kurse.php');
  exit;
}
$kurs->ladeVonMir();

// Zugriffsprüfung: TN darf nur Mini von Kursen sehen, wo eine seiner Klassen angemeldet ist
if (!$kurs->vonMir) {
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

require_once 'TnSeite.php';
$seite = new TnSeite('Mini-Kurs: ' . $kurs->titel);
$seite->anfangGenerieren();

// Kursinfos (wie kurs_sehen.php)
$kurs->makeSehen('sehen');
?>

<h2>Kursinhalt</h2>

<?php if (empty($paragraphen)): ?>
<p><em>Noch keine Inhalte vorhanden.</em></p>
<?php else: ?>
<div class="mini-paragraphen">
<?php foreach ($paragraphen as $p): ?>
  <div class="mini-paragraph">
    <h3><?= htmlspecialchars($p->titel) ?></h3>
    <div class="mini-inhalt"><?= nl2br($p->inhalt) /* HTML erlaubt */ ?></div>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<br />
<a href="kurs_sehen.php?kursid=<?= $kurs->id ?>">Zurück zur Kursseite</a>

<?php
$seite->endeGenerieren();
?>
