<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';
require_once '../Liste.php';

$kurs = Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0, 'DozentKurs');
if (empty($kurs)) {
  header('Location:kurse.php');
  exit;
}

// Zugriffsprüfung: Dozent darf nur Mini von Kursen sehen/bearbeiten, wo er angemeldet ist
if (!in_array($ich->id, $kurs->dozentenids)) {
  header('Location:kurse.php');
  exit;
}

// Aktionen verarbeiten
$fehler = '';
$erfolg = '';

// Paragraph hinzufügen
if (isset($_POST['aktion']) && $_POST['aktion'] === 'hinzufuegen') {
  $naechsteNummer = 1;
  $res = $db->query("SELECT MAX(nummer) as max FROM `gpb_mini_paragraph` WHERE kursid=" . $kurs->id);
  $row = $res->fetch_object();
  $res->free();
  if ($row && $row->max !== null) {
    $naechsteNummer = (int)$row->max + 1;
  }
  $stmt = $db->prepare("INSERT INTO `gpb_mini_paragraph` (kursid, nummer, titel, inhalt) VALUES (?, ?, '', '')");
  $stmt->bind_param('ii', $kurs->id, $naechsteNummer);
  $stmt->execute();
  $stmt->close();
  $erfolg = 'Neuer Paragraph hinzugefügt.';
}

// Paragraph löschen
if (isset($_POST['aktion']) && $_POST['aktion'] === 'loeschen' && isset($_POST['pid'])) {
  $pid = (int)$_POST['pid'];
  $stmt = $db->prepare("DELETE FROM `gpb_mini_paragraph` WHERE id=? AND kursid=?");
  $stmt->bind_param('ii', $pid, $kurs->id);
  $stmt->execute();
  $stmt->close();
  // Umnummerieren nach Löschung
  $res = $db->query("SELECT id FROM `gpb_mini_paragraph` WHERE kursid=" . $kurs->id . " ORDER BY nummer ASC");
  $num = 1;
  while ($row = $res->fetch_object()) {
    $db->query("UPDATE `gpb_mini_paragraph` SET nummer=" . $num . " WHERE id=" . $row->id);
    $num++;
  }
  $res->free();
  $erfolg = 'Paragraph gelöscht.';
}

// Paragraph speichern (Titel + Inhalt)
if (isset($_POST['aktion']) && $_POST['aktion'] === 'speichern' && isset($_POST['pid'])) {
  $pid = (int)$_POST['pid'];
  $titel = $_POST['titel'] ?? '';
  $inhalt = isset($_POST['inhalt']) ? str_replace("\r", "\n", str_replace("\r\n", "\n", $_POST['inhalt'])) : '';
  $stmt = $db->prepare("UPDATE `gpb_mini_paragraph` SET titel=?, inhalt=? WHERE id=? AND kursid=?");
  $stmt->bind_param('ssii', $titel, $inhalt, $pid, $kurs->id);
  $stmt->execute();
  $stmt->close();
  $erfolg = 'Paragraph gespeichert.';
}

// Paragraphen nach oben/unten verschieben
if (isset($_POST['aktion']) && in_array($_POST['aktion'], array('hoch', 'runter')) && isset($_POST['pid'])) {
  $pid = (int)$_POST['pid'];
  $res = $db->query("SELECT nummer FROM `gpb_mini_paragraph` WHERE id=" . $pid . " AND kursid=" . $kurs->id . " LIMIT 1");
  $row = $res->fetch_object();
  $res->free();
  if ($row) {
    $aktNum = (int)$row->nummer;
    $nachbarNum = $_POST['aktion'] === 'hoch' ? $aktNum - 1 : $aktNum + 1;
    $res2 = $db->query("SELECT id FROM `gpb_mini_paragraph` WHERE kursid=" . $kurs->id . " AND nummer=" . $nachbarNum . " LIMIT 1");
    $nachbar = $res2->fetch_object();
    $res2->free();
    if ($nachbar) {
      $db->query("UPDATE `gpb_mini_paragraph` SET nummer=" . $nachbarNum . " WHERE id=" . $pid);
      $db->query("UPDATE `gpb_mini_paragraph` SET nummer=" . $aktNum . " WHERE id=" . $nachbar->id);
    }
  }
  $erfolg = 'Reihenfolge geändert.';
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

// Bearbeiten-Modus für einen bestimmten Paragraph?
$bearbeitenId = isset($_GET['bearbeiten']) ? (int)$_GET['bearbeiten'] : 0;

require_once 'DozentSeite.php';
$seite = new DozentSeite('Mini-Kurs: ' . $kurs->titel);
$seite->anfangGenerieren();

// Kursinfos
$kurs->makeSehen('sehen');

if (!empty($fehler)): ?>
<div class="nok"><?= htmlspecialchars($fehler) ?></div>
<?php endif; ?>
<?php if (!empty($erfolg)): ?>
<div class="ok"><?= htmlspecialchars($erfolg) ?></div>
<?php endif; ?>

<h2>Kursinhalt</h2>

<form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>">
  <input type="hidden" name="aktion" value="hinzufuegen" />
  <button type="submit">+ Paragraph hinzufügen</button>
</form>

<br />

<?php if (empty($paragraphen)): ?>
<p><em>Noch keine Paragraphen vorhanden.</em></p>
<?php else: ?>
<div class="mini-paragraphen">
<?php foreach ($paragraphen as $i => $p): ?>
  <div class="mini-paragraph" style="border:1px solid #ccc; margin-bottom:1em; padding:0.5em;">

    <?php if ($bearbeitenId === (int)$p->id): ?>
    <!-- Bearbeitungsformular -->
    <form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>">
      <input type="hidden" name="aktion" value="speichern" />
      <input type="hidden" name="pid" value="<?= $p->id ?>" />
      <div>
        <label><strong>Titel:</strong><br />
        <input type="text" name="titel" value="<?= htmlspecialchars($p->titel) ?>" style="width:100%;" /></label>
      </div>
      <div style="margin-top:0.5em;">
        <label><strong>Inhalt (HTML erlaubt):</strong><br />
        <textarea name="inhalt" rows="10" style="width:100%;"><?= htmlspecialchars($p->inhalt) ?></textarea></label>
      </div>
      <div style="margin-top:0.5em;">
        <button type="submit">Speichern</button>
        <a href="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>">Abbrechen</a>
      </div>
    </form>

    <?php else: ?>
    <!-- Ansichtsmodus -->
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h3 style="margin:0;"><?= htmlspecialchars($p->titel) ?></h3>
      <span>
        <!-- Hoch/Runter -->
        <?php if ($i > 0): ?>
        <form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>" style="display:inline;">
          <input type="hidden" name="aktion" value="hoch" />
          <input type="hidden" name="pid" value="<?= $p->id ?>" />
          <button type="submit" title="Nach oben">▲</button>
        </form>
        <?php endif; ?>
        <?php if ($i < count($paragraphen) - 1): ?>
        <form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>" style="display:inline;">
          <input type="hidden" name="aktion" value="runter" />
          <input type="hidden" name="pid" value="<?= $p->id ?>" />
          <button type="submit" title="Nach unten">▼</button>
        </form>
        <?php endif; ?>
        <!-- Bearbeiten -->
        <a href="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>&amp;bearbeiten=<?= $p->id ?>" title="Bearbeiten">✏️</a>
        <!-- Löschen -->
        <form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>" style="display:inline;"
              onsubmit="return confirm('Paragraph wirklich löschen?');">
          <input type="hidden" name="aktion" value="loeschen" />
          <input type="hidden" name="pid" value="<?= $p->id ?>" />
          <button type="submit" title="Löschen">🗑️</button>
        </form>
      </span>
    </div>
    <div class="mini-inhalt" style="margin-top:0.5em;"><?= nl2br($p->inhalt) ?></div>
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
