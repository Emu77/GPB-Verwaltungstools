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

function gpbMiniNormalisieren($s) {
  return str_replace("\r", "\n", str_replace("\r\n", "\n", $s));
}

// Paragraph hinzufügen (optional mit Titel/Inhalt und an einer bestimmten Position)
if (isset($_POST['aktion']) && $_POST['aktion'] === 'hinzufuegen') {
  $titel = $_POST['titel'] ?? '';
  $inhalt = isset($_POST['inhalt']) ? gpbMiniNormalisieren($_POST['inhalt']) : '';

  // Bestehende Paragraphen laden, um Position zu bestimmen und ggf. umzunummerieren
  $res = $db->query("SELECT id, nummer FROM `gpb_mini_paragraph` WHERE kursid=" . $kurs->id . " ORDER BY nummer ASC");
  $bestehende = array();
  while ($row = $res->fetch_object()) {
    $bestehende[] = $row;
  }
  $res->free();
  $anzahl = count($bestehende);

  $position = isset($_POST['position']) && $_POST['position'] !== '' ? (int)$_POST['position'] : $anzahl;
  if ($position < 0) $position = 0;
  if ($position > $anzahl) $position = $anzahl;

  // Alle Paragraphen ab der Einfügeposition um eins nach hinten schieben
  for ($i = $anzahl - 1; $i >= $position; $i--) {
    $db->query("UPDATE `gpb_mini_paragraph` SET nummer=" . ($bestehende[$i]->nummer + 1) . " WHERE id=" . $bestehende[$i]->id);
  }

  $neueNummer = $position + 1;
  $stmt = $db->prepare("INSERT INTO `gpb_mini_paragraph` (kursid, nummer, titel, inhalt) VALUES (?, ?, ?, ?)");
  $stmt->bind_param('iiss', $kurs->id, $neueNummer, $titel, $inhalt);
  $stmt->execute();
  $neueId = $stmt->insert_id;
  $stmt->close();

  // Direkt ins Bearbeiten-Formular des neuen Paragraphen springen
  header('Location: mini_kurs_sehen.php?kursid=' . $kurs->id . '&bearbeiten=' . $neueId . '&erfolg=hinzugefuegt');
  exit;
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
  header('Location: mini_kurs_sehen.php?kursid=' . $kurs->id . '&erfolg=geloescht');
  exit;
}

// Paragraph speichern (Titel + Inhalt)
if (isset($_POST['aktion']) && $_POST['aktion'] === 'speichern' && isset($_POST['pid'])) {
  $pid = (int)$_POST['pid'];
  $titel = $_POST['titel'] ?? '';
  $inhalt = isset($_POST['inhalt']) ? gpbMiniNormalisieren($_POST['inhalt']) : '';
  $stmt = $db->prepare("UPDATE `gpb_mini_paragraph` SET titel=?, inhalt=? WHERE id=? AND kursid=?");
  $stmt->bind_param('ssii', $titel, $inhalt, $pid, $kurs->id);
  $stmt->execute();
  $stmt->close();

  // "Speichern und weiter bearbeiten" hält das Formular offen, sonst zurück zur Ansicht
  if (isset($_POST['weiter']) && $_POST['weiter'] === '1') {
    header('Location: mini_kurs_sehen.php?kursid=' . $kurs->id . '&bearbeiten=' . $pid . '&erfolg=gespeichert');
  } else {
    header('Location: mini_kurs_sehen.php?kursid=' . $kurs->id . '&erfolg=gespeichert');
  }
  exit;
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
  header('Location: mini_kurs_sehen.php?kursid=' . $kurs->id . '&erfolg=verschoben');
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

// Bearbeiten-Modus für einen bestimmten Paragraph?
$bearbeitenId = isset($_GET['bearbeiten']) ? (int)$_GET['bearbeiten'] : 0;

// Erfolgsmeldung nach Redirect (PRG-Pattern)
$erfolgsTexte = array(
  'hinzugefuegt' => 'Neuer Paragraph hinzugefügt.',
  'gespeichert'  => 'Paragraph gespeichert.',
  'geloescht'    => 'Paragraph gelöscht.',
  'verschoben'   => 'Reihenfolge geändert.',
);
if (isset($_GET['erfolg']) && isset($erfolgsTexte[$_GET['erfolg']])) {
  $erfolg = $erfolgsTexte[$_GET['erfolg']];
}

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

<!--
  "+ neuer Paragraph"-Zeilen: funktionieren auch ohne JavaScript (legen sofort einen leeren
  Paragraph an der gewünschten Position an und springen ins Bearbeiten-Formular). Mit JavaScript
  wird stattdessen an der Klickstelle direkt ein Titel/Inhalt-Formular eingeblendet, das den
  Paragraph in einem Schritt mit Inhalt anlegt.
-->
<div class="mini-paragraphen" id="mini-paragraphen">

  <div class="mini-neu-slot">
    <form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>" class="mini-neu-slot-form">
      <input type="hidden" name="aktion" value="hinzufuegen" />
      <input type="hidden" name="position" value="0" />
      <button type="submit" class="mini-neu-btn">+ neuer Paragraph</button>
    </form>
  </div>

<?php if (!empty($paragraphen)): foreach ($paragraphen as $i => $p): ?>
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
        <button type="submit" name="weiter" value="1">Speichern und weiter bearbeiten</button>
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

  <div class="mini-neu-slot">
    <form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>" class="mini-neu-slot-form">
      <input type="hidden" name="aktion" value="hinzufuegen" />
      <input type="hidden" name="position" value="<?= $i + 1 ?>" />
      <button type="submit" class="mini-neu-btn">+ neuer Paragraph</button>
    </form>
  </div>

<?php endforeach; else: ?>
  <p><em>Noch keine Paragraphen vorhanden.</em></p>
<?php endif; ?>

</div>

<template id="mini-neu-template">
  <div class="mini-neu-formular" style="border:1px dashed #888; margin:0.5em 0; padding:0.5em; background:#f8f8f8;">
    <form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>">
      <input type="hidden" name="aktion" value="hinzufuegen" />
      <input type="hidden" name="position" value="" />
      <div>
        <label><strong>Titel:</strong><br />
        <input type="text" name="titel" style="width:100%;" /></label>
      </div>
      <div style="margin-top:0.5em;">
        <label><strong>Inhalt (HTML erlaubt):</strong><br />
        <textarea name="inhalt" rows="6" style="width:100%;"></textarea></label>
      </div>
      <div style="margin-top:0.5em;">
        <button type="submit">Speichern</button>
        <button type="button" class="mini-neu-abbrechen">Abbrechen</button>
      </div>
    </form>
  </div>
</template>

<script>
(function () {
  var container = document.getElementById('mini-paragraphen');
  if (!container) return;

  function schliesseOffeneFormulare() {
    container.querySelectorAll('.mini-neu-formular').forEach(function (el) { el.remove(); });
    container.querySelectorAll('.mini-neu-slot-form').forEach(function (f) { f.style.display = ''; });
  }

  // "+ neuer Paragraph" abfangen und stattdessen Formular inline öffnen
  container.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.classList.contains('mini-neu-slot-form')) return;
    e.preventDefault();

    var position = form.querySelector('input[name="position"]').value;
    schliesseOffeneFormulare();

    var tpl = document.getElementById('mini-neu-template');
    var clone = tpl.content.cloneNode(true);
    clone.querySelector('input[name="position"]').value = position;

    form.style.display = 'none';
    form.parentNode.appendChild(clone);
    var titelFeld = form.parentNode.querySelector('input[name="titel"]');
    if (titelFeld) titelFeld.focus();
  });

  // Abbrechen im eingeblendeten Formular
  container.addEventListener('click', function (e) {
    if (!e.target.classList.contains('mini-neu-abbrechen')) return;
    var slot = e.target.closest('.mini-neu-slot');
    if (!slot) return;
    var formular = slot.querySelector('.mini-neu-formular');
    if (formular) formular.remove();
    var slotForm = slot.querySelector('.mini-neu-slot-form');
    if (slotForm) slotForm.style.display = '';
  });
})();
</script>

<br />
<a href="kurs_sehen.php?kursid=<?= $kurs->id ?>">Zurück zur Kursseite</a>

<?php
$seite->endeGenerieren();
?>
