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

require_once '../MiniAnhang.php';
require_once '../MiniHtmlSanitizer.php';

function gpbMiniNormalisieren($s) {
  return str_replace("\r", "\n", str_replace("\r\n", "\n", $s));
}

// Zufälliger, nicht erratbarer Dateiname für die Platte (Original-Name
// bleibt nur in der DB gespeichert und wird beim Download wieder verwendet)
function gpbMiniAnhangDateinameErzeugen($originalName) {
  $ext = pathinfo($originalName, PATHINFO_EXTENSION);
  $extSauber = preg_replace('/[^A-Za-z0-9]/', '', $ext);
  return bin2hex(random_bytes(16)) . ($extSauber !== '' ? '.' . $extSauber : '');
}

// Erlaubte Dateitypen und maximale Größe für Mini-Anhänge
define('GPB_MINI_ANHANG_ERLAUBTE_ENDUNGEN', array(
  'jpg', 'jpeg', 'png', 'svg', 'gif', 'webp', // Bilder
  'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', // Dokumente
));
define('GPB_MINI_ANHANG_MAX_BYTES', 15 * 1024 * 1024); // 15 MB

// Anhang hochladen
if (isset($_POST['aktion']) && $_POST['aktion'] === 'anhang_hochladen' && isset($_POST['pid'])) {
  $pid = (int)$_POST['pid'];
  $ergebnis = 'anhang_fehler'; // Standard: Fehler, wird unten bei Erfolg überschrieben
  $fehlerText = 'Anhang konnte nicht hochgeladen werden (siehe php_error_log für Details).';

  // Prüfen, dass der Paragraph wirklich zu diesem Kurs gehört
  $res = $db->query("SELECT id FROM `gpb_mini_paragraph` WHERE id=" . $pid . " AND kursid=" . $kurs->id . " LIMIT 1");
  $paragraphOk = $res->fetch_object();
  $res->free();

  $originalName = isset($_FILES['datei']) ? basename($_FILES['datei']['name']) : '';
  $endung = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

  if (!$paragraphOk) {
    error_log('Mini-Anhang-Upload: Paragraph ' . $pid . ' gehört nicht zu Kurs ' . $kurs->id);
  } elseif (!isset($_FILES['datei']) || $_FILES['datei']['error'] !== UPLOAD_ERR_OK) {
    $fehlerCode = isset($_FILES['datei']) ? $_FILES['datei']['error'] : 'kein $_FILES[datei]';
    error_log('Mini-Anhang-Upload: Datei-Upload-Fehler, error-Code=' . $fehlerCode . ' (siehe PHP-Doku UPLOAD_ERR_*)');
    if ($fehlerCode === UPLOAD_ERR_INI_SIZE || $fehlerCode === UPLOAD_ERR_FORM_SIZE) {
      $fehlerText = 'Datei ist zu groß.';
    }
  } elseif (!in_array($endung, GPB_MINI_ANHANG_ERLAUBTE_ENDUNGEN, true)) {
    $fehlerText = 'Dateityp ".' . htmlspecialchars($endung) . '" ist nicht erlaubt. Erlaubt: ' . implode(', ', GPB_MINI_ANHANG_ERLAUBTE_ENDUNGEN) . '.';
  } elseif ((int)$_FILES['datei']['size'] > GPB_MINI_ANHANG_MAX_BYTES) {
    $fehlerText = 'Datei ist zu groß (max. ' . round(GPB_MINI_ANHANG_MAX_BYTES / 1024 / 1024) . ' MB, hochgeladen: ' . miniAnhangGroesseAnzeigen($_FILES['datei']['size']) . ').';
  } else {
    $groesse = (int)$_FILES['datei']['size'];
    $gespeichert = gpbMiniAnhangDateinameErzeugen($originalName);

    $ordner = dirname(__DIR__) . '/mini_uploads/dozent_' . $ich->id;
    if (!is_dir($ordner) && !mkdir($ordner, 0755, true) && !is_dir($ordner)) {
      error_log('Mini-Anhang-Upload: Konnte Ordner nicht anlegen: ' . $ordner);
    } elseif (!move_uploaded_file($_FILES['datei']['tmp_name'], $ordner . '/' . $gespeichert)) {
      error_log('Mini-Anhang-Upload: move_uploaded_file fehlgeschlagen nach ' . $ordner . '/' . $gespeichert);
    } else {
      $stmt = $db->prepare("INSERT INTO `gpb_mini_anhang` (paragraphid, dozentid, dateiname, gespeicherter_dateiname, groesse, hochgeladen_am) VALUES (?, ?, ?, ?, ?, NOW())");
      $stmt->bind_param('iissi', $pid, $ich->id, $originalName, $gespeichert, $groesse);
      $stmt->execute();
      $stmt->close();
      $ergebnis = 'anhang_hochgeladen';
    }
  }

  if ($ergebnis === 'anhang_fehler') {
    $_SESSION['mini_fehler'] = $fehlerText;
  }
  // Kein Erfolgshinweis nötig: die neue Datei erscheint direkt in der Anhangliste.
  header('Location: mini_kurs_sehen.php?kursid=' . $kurs->id . '&bearbeiten=' . $pid);
  exit;
}

// Anhang löschen
if (isset($_POST['aktion']) && $_POST['aktion'] === 'anhang_loeschen' && isset($_POST['anhangid'])) {
  $anhangid = (int)$_POST['anhangid'];
  $pid = isset($_POST['pid']) ? (int)$_POST['pid'] : 0;
  // Nur löschen, wenn der Anhang zu einem Paragraph dieses Kurses gehört
  $res = $db->query(
    "SELECT a.id, a.dozentid, a.gespeicherter_dateiname FROM `gpb_mini_anhang` a " .
    "JOIN `gpb_mini_paragraph` p ON p.id=a.paragraphid " .
    "WHERE a.id=" . $anhangid . " AND p.kursid=" . $kurs->id . " LIMIT 1"
  );
  $anhang = $res->fetch_object();
  $res->free();
  if ($anhang) {
    miniAnhangLoeschen($anhangid);
  }
  // Kein Erfolgshinweis nötig: der Anhang verschwindet direkt aus der Liste.
  header('Location: mini_kurs_sehen.php?kursid=' . $kurs->id . '&bearbeiten=' . $pid);
  exit;
}

// Paragraph hinzufügen (optional mit Titel/Inhalt und an einer bestimmten Position)
if (isset($_POST['aktion']) && $_POST['aktion'] === 'hinzufuegen') {
  $titel = $_POST['titel'] ?? '';
  $inhalt = isset($_POST['inhalt']) ? gpbMiniHtmlSaeubern(gpbMiniNormalisieren($_POST['inhalt'])) : '';

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

  // Direkt ins Bearbeiten-Formular des neuen Paragraphen springen - kein
  // Erfolgshinweis nötig, der Dozent sieht den neuen Paragraph sofort im
  // Bearbeitungsmodus.
  header('Location: mini_kurs_sehen.php?kursid=' . $kurs->id . '&bearbeiten=' . $neueId);
  exit;
}

// Paragraph löschen (inkl. aller Anhänge, damit keine Dateileichen bleiben)
if (isset($_POST['aktion']) && $_POST['aktion'] === 'loeschen' && isset($_POST['pid'])) {
  $pid = (int)$_POST['pid'];
  $res = $db->query("SELECT id FROM `gpb_mini_paragraph` WHERE id=" . $pid . " AND kursid=" . $kurs->id . " LIMIT 1");
  $paragraphOk = $res->fetch_object();
  $res->free();
  if ($paragraphOk) {
    miniAnhaengeVonParagraphLoeschen($pid);
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
  }
  // Kein Erfolgshinweis nötig: der Paragraph verschwindet direkt aus der Liste.
  header('Location: mini_kurs_sehen.php?kursid=' . $kurs->id);
  exit;
}

// Paragraph speichern (Titel + Inhalt)
if (isset($_POST['aktion']) && $_POST['aktion'] === 'speichern' && isset($_POST['pid'])) {
  $pid = (int)$_POST['pid'];
  $titel = $_POST['titel'] ?? '';
  $inhalt = isset($_POST['inhalt']) ? gpbMiniHtmlSaeubern(gpbMiniNormalisieren($_POST['inhalt'])) : '';
  $stmt = $db->prepare("UPDATE `gpb_mini_paragraph` SET titel=?, inhalt=? WHERE id=? AND kursid=?");
  $stmt->bind_param('ssii', $titel, $inhalt, $pid, $kurs->id);
  $stmt->execute();
  $stmt->close();

  // "Speichern und weiter bearbeiten" hält das Formular offen - hier braucht es
  // eine Meldung, weil sich sonst optisch nichts sichtbar verändert. Beim
  // normalen "Speichern" springt man zurück in die Ansicht und sieht den
  // gespeicherten Inhalt direkt, da ist keine Meldung nötig.
  if (isset($_POST['weiter']) && $_POST['weiter'] === '1') {
    $_SESSION['mini_erfolg'] = 'Paragraph gespeichert.';
    header('Location: mini_kurs_sehen.php?kursid=' . $kurs->id . '&bearbeiten=' . $pid);
  } else {
    header('Location: mini_kurs_sehen.php?kursid=' . $kurs->id);
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
  // Kein Erfolgshinweis nötig: die neue Reihenfolge ist direkt sichtbar.
  header('Location: mini_kurs_sehen.php?kursid=' . $kurs->id);
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

// Alle Anhänge dieses Kurses auf einen Blick laden (für die zentrale
// Anhang-Übersicht weiter unten), inkl. Paragraph-Titel und ob der Anhang
// im Paragraph-Text verwendet (verlinkt) wird oder "verwaist" ist.
$alleAnhaenge = array();
$result = $db->query(
  "SELECT a.*, p.titel AS paragraph_titel, p.id AS paragraph_id, p.inhalt AS paragraph_inhalt, p.nummer AS paragraph_nummer " .
  "FROM `gpb_mini_anhang` a " .
  "JOIN `gpb_mini_paragraph` p ON p.id = a.paragraphid " .
  "WHERE p.kursid=" . $kurs->id . " " .
  "ORDER BY p.nummer ASC, a.hochgeladen_am ASC"
);
while ($row = $result->fetch_object()) {
  $row->verwendet = (strpos($row->paragraph_inhalt, 'id=' . $row->id) !== false)
    && preg_match('/mini_anhang_download\.php\?id=' . $row->id . '(?!\d)/', $row->paragraph_inhalt);
  $alleAnhaenge[] = $row;
}
$result->free();

// Bearbeiten-Modus für einen bestimmten Paragraph?
$bearbeitenId = isset($_GET['bearbeiten']) ? (int)$_GET['bearbeiten'] : 0;

// Erfolgs-/Fehlermeldung nach Redirect (PRG-Pattern) - über die Session
// übergeben statt über die URL, und nur dort gesetzt, wo der Dozent das
// Ergebnis nicht ohnehin sofort sieht (siehe Kommentare bei den jeweiligen
// Aktionen weiter oben).
if (!empty($_SESSION['mini_erfolg'])) {
  $erfolg = $_SESSION['mini_erfolg'];
  unset($_SESSION['mini_erfolg']);
}
if (!empty($_SESSION['mini_fehler'])) {
  $fehler = $_SESSION['mini_fehler'];
  unset($_SESSION['mini_fehler']);
}

$miniSeiteOhneTodos = true; // auf der Mini-Seite werden die allgemeinen Todos nicht angezeigt
$miniSeiteVersteckeAktionszeile = true; // "Sehen/Mini/Bewerten"-Zeile ist auf der Mini-Seite selbst selbstreferenziell
require_once 'DozentSeite.php';
$seite = new DozentSeite('Mini-Kurs: ' . $kurs->titel);
$seite->anfangGenerieren();

// Kursinfos
$kurs->makeSehen('sehen', true);

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
    <?php
      $anhaenge = miniAnhangListeLaden($p->id);
      $bildAnhaenge = array_values(array_filter($anhaenge, function ($a) { return miniAnhangIstBild($a->dateiname); }));
    ?>
    <div class="mini-paragraph-bearbeiten-formular">
    <form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>">
      <input type="hidden" name="aktion" value="speichern" />
      <input type="hidden" name="pid" value="<?= $p->id ?>" />
      <div>
        <label><strong>Titel:</strong><br />
        <input type="text" name="titel" value="<?= htmlspecialchars($p->titel) ?>" style="width:100%;" /></label>
      </div>
      <div style="margin-top:0.5em;">
        <label><strong>Inhalt (HTML erlaubt):</strong></label>
        <div class="mini-format-toolbar" style="margin-bottom:0.3em;">
          <span class="mini-format-plain-buttons">
          <button type="button" class="mini-format-btn" data-open="&lt;strong&gt;" data-close="&lt;/strong&gt;"><strong>Fett</strong></button>
          <button type="button" class="mini-format-btn" data-open="&lt;em&gt;" data-close="&lt;/em&gt;"><em>Kursiv</em></button>
          <button type="button" class="mini-format-btn" data-open="&lt;u&gt;" data-close="&lt;/u&gt;"><u>Unterstrichen</u></button>
          <button type="button" class="mini-format-btn" data-open="&lt;span style=&quot;color:red&quot;&gt;" data-close="&lt;/span&gt;" style="color:red;">Rot</button>
          <button type="button" class="mini-format-btn" data-open="&lt;span style=&quot;color:green&quot;&gt;" data-close="&lt;/span&gt;" style="color:green;">Grün</button>
          <button type="button" class="mini-format-liste-btn">Liste</button>
          </span>
          <?php if (empty($bildAnhaenge)): ?>
          <select class="mini-bild-auswahl" disabled title="Zuerst ein Bild als Anhang hochladen">
            <option>(keine Bild-Anhänge)</option>
          </select>
          <button type="button" class="mini-bild-einfuegen-btn" disabled>Bild einfügen</button>
          <?php else: ?>
          <select class="mini-bild-auswahl">
            <?php foreach ($bildAnhaenge as $a): ?>
            <option value="../mini_anhang_download.php?id=<?= $a->id ?>"><?= htmlspecialchars($a->dateiname) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="button" class="mini-bild-einfuegen-btn">Bild einfügen</button>
          <?php endif; ?>
          <label style="margin-left:1em;" title="Formatierten Editor mit Werkzeugleiste statt reinem HTML-Textfeld verwenden">
            <input type="checkbox" class="mini-wysiwyg-toggle" autocomplete="off" data-target="mini-inhalt-<?= $p->id ?>" /> WYSIWYG-Editor
          </label>
        </div>
        <!-- Die Vorschau bekommt bewusst KEIN eigenes Gelb/Rahmen-Styling -
             sie soll so aussehen wie der spätere, gespeicherte Paragraph. -->
        <div class="mini-vorschau mini-inhalt" style="display:none; margin-bottom:0.3em;">
          <div class="mini-vorschau-inhalt"></div>
        </div>
        <textarea name="inhalt" id="mini-inhalt-<?= $p->id ?>" rows="10" style="width:100%;"><?= htmlspecialchars($p->inhalt) ?></textarea>
      </div>
      <div style="margin-top:0.5em;">
        <button type="submit">Speichern</button>
        <button type="submit" name="weiter" value="1">Speichern und weiter bearbeiten</button>
        <button type="button" class="mini-vorschau-btn">Vorschau</button>
        <button type="button" class="mini-vorschau-aktualisieren-btn" style="display:none;">Vorschau aktualisieren</button>
        <a href="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>">Abbrechen</a>
        
      </div>
    </form>

    <form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>"
          onsubmit="return confirm('Paragraph wirklich löschen (inkl. aller Anhänge)?');" style="margin-top:0.3em;">
      <input type="hidden" name="aktion" value="loeschen" />
      <input type="hidden" name="pid" value="<?= $p->id ?>" />
      <button type="submit" title="Paragraph löschen">🗑️ Paragraph löschen</button>
    </form>

    <!-- Anhänge (eigenes Formular mit enctype=multipart, unabhängig vom Bearbeiten-Formular) -->
    <div class="mini-anhaenge" style="margin-top:1em; padding-top:0.5em; border-top:1px solid #ddd;">
      <strong>Anhänge:</strong>
      <?php if (empty($anhaenge)): ?>
      <div><em>Keine Anhänge.</em></div>
      <?php else: ?>
      <div style="margin:0.3em 0;">
        <?php foreach ($anhaenge as $a): ?>
        <div style="display:inline-block; vertical-align:top;">
          <?php miniAnhangAnzeigen($a, '../'); ?>
          <form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>"
                onsubmit="return confirm('Anhang wirklich löschen?');">
            <input type="hidden" name="aktion" value="anhang_loeschen" />
            <input type="hidden" name="anhangid" value="<?= $a->id ?>" />
            <input type="hidden" name="pid" value="<?= $p->id ?>" />
            <button type="submit" title="Anhang löschen">🗑️ Anhang löschen</button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>" enctype="multipart/form-data">
        <input type="hidden" name="aktion" value="anhang_hochladen" />
        <input type="hidden" name="pid" value="<?= $p->id ?>" />
        <input type="file" name="datei" accept=".jpg,.jpeg,.png,.svg,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip" required />
        <small>Max. 15 MB. Erlaubt: Bilder, PDF, Office-Dokumente, TXT, ZIP.</small>
        <button type="submit">Anhang hochladen</button>
      </form>
    </div>
    </div>

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
        <a href="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>&amp;bearbeiten=<?= $p->id ?>" title="Paragraph bearbeiten">✏️ Paragraph bearbeiten</a>
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
    <?php $anhaengeAnsicht = miniAnhangListeLaden($p->id); ?>
    <?php if (!empty($anhaengeAnsicht)): ?>
    <div class="mini-anhaenge" style="margin-top:0.5em;">
      <strong>Anhänge:</strong>
      <?php foreach ($anhaengeAnsicht as $a): ?>
        <?php miniAnhangAnzeigen($a, '../'); ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
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

<!-- Zentrale Anhang-Übersicht: alle Anhänge dieses Kurses auf einen Blick -->
<style>
.mini-anhaenge-uebersicht .zuklappen { display:none; }
.mini-anhaenge-uebersicht[open] .aufklappen { display:none; }
.mini-anhaenge-uebersicht[open] .zuklappen { display:inline; }
</style>
<details class="mini-anhaenge-uebersicht" style="margin-top:1.5em;">
  <summary><strong><?= count($alleAnhaenge) ?> Anhänge in diesem Kurs</strong> (<span class="aufklappen">Übersicht aufklappen</span><span class="zuklappen">Übersicht zuklappen</span>)</summary>
  <?php if (empty($alleAnhaenge)): ?>
  <p><em>Noch keine Anhänge in diesem Kurs.</em></p>
  <?php else: ?>
  <table border="1" cellspacing="0" style="border-collapse:collapse; margin-top:0.5em;" class="sehen">
    <tr>
      <th>Paragraph</th>
      <th>Datei</th>
      <th>Größe</th>
      <th>Hochgeladen am</th>
      <th>Status</th>
      <th></th>
    </tr>
    <?php foreach ($alleAnhaenge as $a): ?>
    <tr>
      <td><a href="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>&amp;bearbeiten=<?= $a->paragraph_id ?>"><?= htmlspecialchars($a->paragraph_titel !== '' ? $a->paragraph_titel : '(ohne Titel)') ?></a></td>
      <td><?php miniAnhangAnzeigen($a, '../'); ?></td>
      <td><?= miniAnhangGroesseAnzeigen($a->groesse) ?></td>
      <td><?= date('d.m.Y H:i', strtotime($a->hochgeladen_am)) ?></td>
      <td>
        <?php if (miniAnhangIstBild($a->dateiname)): ?>
          <?php if ($a->verwendet): ?>
          <span style="color:green;">✓ im Text verwendet</span>
          <?php else: ?>
          <span style="color:#b36b00;">⚠ verwaist (nicht im Text)</span>
          <?php endif; ?>
        <?php else: ?>
          <span style="color:#666;">Datei-Anhang</span>
        <?php endif; ?>
      </td>
      <td>
        <form method="post" action="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>"
              onsubmit="return confirm('Anhang wirklich löschen?');">
          <input type="hidden" name="aktion" value="anhang_loeschen" />
          <input type="hidden" name="anhangid" value="<?= $a->id ?>" />
          <button type="submit" title="Anhang löschen">🗑️</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <p><small>"Verwaist" heißt: das Bild ist hochgeladen, wird aber in keinem Paragraph-Text per "Bild einfügen" verwendet. Datei-Anhänge (PDF, Word, ...) können nicht in den Text eingefügt werden und werden deshalb nicht als verwaist markiert.</small></p>
  <?php endif; ?>
</details>

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
        <label><strong>Inhalt (HTML erlaubt):</strong></label>
        <div class="mini-format-toolbar" style="margin-bottom:0.3em;">
          <span class="mini-format-plain-buttons">
          <button type="button" class="mini-format-btn" data-open="&lt;strong&gt;" data-close="&lt;/strong&gt;"><strong>Fett</strong></button>
          <button type="button" class="mini-format-btn" data-open="&lt;em&gt;" data-close="&lt;/em&gt;"><em>Kursiv</em></button>
          <button type="button" class="mini-format-btn" data-open="&lt;u&gt;" data-close="&lt;/u&gt;"><u>Unterstrichen</u></button>
          <button type="button" class="mini-format-btn" data-open="&lt;span style=&quot;color:red&quot;&gt;" data-close="&lt;/span&gt;" style="color:red;">Rot</button>
          <button type="button" class="mini-format-btn" data-open="&lt;span style=&quot;color:green&quot;&gt;" data-close="&lt;/span&gt;" style="color:green;">Grün</button>
          <button type="button" class="mini-format-liste-btn">Liste</button>
          </span>
          <label style="margin-left:1em;" title="Formatierten Editor mit Werkzeugleiste statt reinem HTML-Textfeld verwenden">
            <input type="checkbox" class="mini-wysiwyg-toggle" autocomplete="off" /> WYSIWYG-Editor
          </label>
        </div>
        <div class="mini-vorschau mini-inhalt" style="display:none; margin-bottom:0.3em;">
          <div class="mini-vorschau-inhalt"></div>
        </div>
        <textarea name="inhalt" rows="6" style="width:100%;"></textarea>
      </div>
      <div style="margin-top:0.5em;">
        <button type="submit">Speichern</button>
        <button type="button" class="mini-vorschau-btn">Vorschau</button>
        <button type="button" class="mini-vorschau-aktualisieren-btn" style="display:none;">Vorschau aktualisieren</button>
        <button type="button" class="mini-neu-abbrechen">Abbrechen</button>
      </div>
    </form>
  </div>
</template>

<!-- Custom-WYSIWYG-Editor (Ersatz für TinyMCE, IHK-Projektarbeit Emu),
     siehe docs/Projektantrag_gpb_WYSIWYG_Editor.pdf -->
<link rel="stylesheet" href="mini_wysiwyg.css" />
<script src="mini_wysiwyg.js"></script>
<script>
(function () {
  var container = document.getElementById('mini-paragraphen');
  if (!container) return;

  // Custom-WYSIWYG-Editor ein-/ausschaltbar pro Paragraph. Die Wahl wird
  // in localStorage gemerkt, damit sie sich beim nächsten Bearbeiten (auch
  // nach einem Reload) nicht jedes Mal neu einstellen muss.
  var WYSIWYG_PREF_KEY = 'gpbMiniWysiwyg';

  function miniWysiwygAn(textareaId) {
    if (typeof MiniWysiwyg === 'undefined') return;
    var textarea = document.getElementById(textareaId);
    if (!textarea) return;
    MiniWysiwyg.init(textarea);
  }

  function miniWysiwygAus(textareaId) {
    if (typeof MiniWysiwyg === 'undefined') return;
    MiniWysiwyg.destroy(textareaId);
  }

  container.addEventListener('change', function (e) {
    if (!e.target.classList.contains('mini-wysiwyg-toggle')) return;
    var textareaId = e.target.getAttribute('data-target');
    var toolbar = e.target.closest('.mini-format-toolbar');
    var plainButtons = toolbar ? toolbar.querySelector('.mini-format-plain-buttons') : null;

    if (e.target.checked) {
      miniWysiwygAn(textareaId);
      if (plainButtons) plainButtons.style.display = 'none';
    } else {
      miniWysiwygAus(textareaId);
      if (plainButtons) plainButtons.style.display = '';
    }
    try { localStorage.setItem(WYSIWYG_PREF_KEY, e.target.checked ? '1' : '0'); } catch (ex) {}
  });

  // Beim Laden: gespeicherte Präferenz auf alle vorhandenen Toggles anwenden.
  // Als eigene Funktion, damit sie auch auf neu eingefügte Formulare (z.B.
  // "+ neuer Paragraph") angewendet werden kann, nicht nur beim Seitenladen.
  function miniWysiwygPraefAnwenden(checkboxes) {
    var pref;
    try { pref = localStorage.getItem(WYSIWYG_PREF_KEY); } catch (ex) { pref = null; }
    if (pref !== '1') return;
    checkboxes.forEach(function (checkbox) {
      checkbox.checked = true;
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  miniWysiwygPraefAnwenden(Array.prototype.slice.call(container.querySelectorAll('.mini-wysiwyg-toggle')));

  // Vor dem Absenden: Editor-Inhalt in die eigentliche Textarea übernehmen,
  // sonst würde das Formular den alten (vor dem Editor-Start gespeicherten)
  // Stand abschicken. MiniWysiwyg hält die Textarea zwar schon laufend bei
  // jedem Input synchron, das hier ist die zusätzliche Absicherung.
  container.addEventListener('submit', function (e) {
    if (typeof MiniWysiwyg === 'undefined') return;
    var textarea = e.target.querySelector('textarea[name="inhalt"]');
    if (!textarea || !textarea.id) return;
    var inhalt = MiniWysiwyg.getContent(textarea.id);
    if (inhalt !== null) textarea.value = inhalt;
  }, true);

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

    // Eindeutige Textarea-ID vergeben (ein neuer, noch nicht gespeicherter
    // Paragraph hat keine feste $p->id) und die WYSIWYG-Checkbox darauf
    // verknüpfen, damit der Custom-Editor auch hier nutzbar ist.
    var neueTextarea = clone.querySelector('textarea[name="inhalt"]');
    var neueId = 'mini-inhalt-neu-' + Date.now();
    neueTextarea.id = neueId;
    var neueCheckbox = clone.querySelector('.mini-wysiwyg-toggle');
    if (neueCheckbox) neueCheckbox.setAttribute('data-target', neueId);

    form.style.display = 'none';
    form.parentNode.appendChild(clone);
    if (neueCheckbox) miniWysiwygPraefAnwenden([neueCheckbox]);
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

  // Vorschau-Button: kopiert den Textarea-Inhalt in ein Vorschau-div, das
  // dieselbe Klasse (mini-inhalt) wie die spätere echte Anzeige trägt - die
  // Vorschau soll wie das fertige Ergebnis aussehen, kein Extra-Styling.
  // Ist die Vorschau eingeblendet, erscheint daneben "Vorschau aktualisieren",
  // um den Inhalt neu zu übernehmen, ohne die Vorschau erst zu schließen.
  function miniVorschauFuellen(form) {
    var textarea = form.querySelector('textarea[name="inhalt"]');
    var vorschauInhalt = form.querySelector('.mini-vorschau-inhalt');
    if (!textarea || !vorschauInhalt) return;
    var editorInhalt = (typeof MiniWysiwyg !== 'undefined' && textarea.id) ? MiniWysiwyg.getContent(textarea.id) : null;
    var inhalt = editorInhalt !== null ? editorInhalt : textarea.value.replace(/\n/g, '<br>');
    vorschauInhalt.innerHTML = inhalt;
  }

  container.addEventListener('click', function (e) {
    if (!e.target.classList.contains('mini-vorschau-btn')) return;
    var form = e.target.closest('form');
    if (!form) return;
    var vorschauDiv = form.querySelector('.mini-vorschau');
    var aktualisierenBtn = form.querySelector('.mini-vorschau-aktualisieren-btn');
    if (!vorschauDiv) return;

    if (vorschauDiv.style.display === 'none' || !vorschauDiv.style.display) {
      miniVorschauFuellen(form);
      vorschauDiv.style.display = 'block';
      e.target.textContent = 'Vorschau ausblenden';
      if (aktualisierenBtn) aktualisierenBtn.style.display = '';
    } else {
      vorschauDiv.style.display = 'none';
      e.target.textContent = 'Vorschau';
      if (aktualisierenBtn) aktualisierenBtn.style.display = 'none';
    }
  });

  container.addEventListener('click', function (e) {
    if (!e.target.classList.contains('mini-vorschau-aktualisieren-btn')) return;
    var form = e.target.closest('form');
    if (!form) return;
    miniVorschauFuellen(form);
  });

  // Formatierungs-Buttons (Fett, Kursiv, Unterstrichen, Rot, Grün):
  // Wenn die Markierung bereits genau mit dem Tag-Paar beginnt/endet, wird
  // es entfernt (Toggle aus). Sonst wird die Markierung damit umschlossen
  // (Toggle an). Funktioniert zuverlässig, wenn korrekt markiert wurde -
  // bei "falscher" Markierung entsteht ggf. unsauberer HTML-Code, das ist
  // laut Auftrag erstmal ok.
  function miniFormatToggle(textarea, open, close) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var value = textarea.value;
    var selected = value.substring(start, end);
    var neu, neuStart, neuEnd;

    if (selected.indexOf(open) === 0 && selected.slice(-close.length) === close) {
      neu = selected.substring(open.length, selected.length - close.length);
      neuStart = start;
      neuEnd = start + neu.length;
    } else {
      neu = open + selected + close;
      neuStart = start;
      neuEnd = start + neu.length;
    }
    textarea.value = value.substring(0, start) + neu + value.substring(end);
    textarea.focus();
    textarea.setSelectionRange(neuStart, neuEnd);
  }

  container.addEventListener('click', function (e) {
    var btn = e.target.closest('.mini-format-btn');
    if (!btn) return;
    var form = btn.closest('form');
    var textarea = form ? form.querySelector('textarea[name="inhalt"]') : null;
    if (!textarea) return;
    miniFormatToggle(textarea, btn.getAttribute('data-open'), btn.getAttribute('data-close'));
  });

  // Bild einfügen: fügt an der Cursorposition ein <img>-Tag mit der Adresse
  // des im <select> daneben gewählten Bild-Anhangs ein. Funktioniert sowohl
  // im normalen Textfeld als auch (falls aktiv) direkt im Custom-Editor.
  container.addEventListener('click', function (e) {
    var btn = e.target.closest('.mini-bild-einfuegen-btn');
    if (!btn || btn.disabled) return;
    var toolbar = btn.closest('.mini-format-toolbar');
    var form = btn.closest('form');
    var auswahl = toolbar ? toolbar.querySelector('.mini-bild-auswahl') : null;
    var textarea = form ? form.querySelector('textarea[name="inhalt"]') : null;
    if (!auswahl || !textarea || !auswahl.value) return;

    var url = auswahl.value;
    var alt = auswahl.options[auswahl.selectedIndex].textContent;
    var tag = '<img src="' + url + '" alt="' + alt.replace(/"/g, '&quot;') + '" style="max-width:100%;" />';

    var editorAktiv = typeof MiniWysiwyg !== 'undefined' && textarea.id && MiniWysiwyg.getContent(textarea.id) !== null;
    if (editorAktiv) {
      MiniWysiwyg.insertContent(textarea.id, tag);
      return;
    }

    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var value = textarea.value;
    textarea.value = value.substring(0, start) + tag + value.substring(end);
    textarea.focus();
    var neuePos = start + tag.length;
    textarea.setSelectionRange(neuePos, neuePos);
  });

  // Liste/Aufzählung: markierte Zeilen werden in <ul><li>...</li></ul>
  // umgewandelt bzw. wieder zurück, wenn die Markierung bereits eine
  // solche Liste ist.
  container.addEventListener('click', function (e) {
    if (!e.target.classList.contains('mini-format-liste-btn')) return;
    var form = e.target.closest('form');
    var textarea = form ? form.querySelector('textarea[name="inhalt"]') : null;
    if (!textarea) return;

    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var value = textarea.value;
    var selected = value.substring(start, end);
    var trimmed = selected.trim();
    var neu;

    if (trimmed.indexOf('<ul>') === 0 && trimmed.slice(-5) === '</ul>') {
      var innerLines = trimmed.substring(4, trimmed.length - 5).split('\n').map(function (line) {
        line = line.trim();
        if (line.indexOf('<li>') === 0 && line.slice(-5) === '</li>') {
          return line.substring(4, line.length - 5);
        }
        return line;
      }).filter(function (l) { return l.length > 0; });
      neu = innerLines.join('\n');
    } else {
      var lines = selected.split('\n').filter(function (l) { return l.trim().length > 0; });
      neu = '<ul>\n' + lines.map(function (l) { return '<li>' + l.trim() + '</li>'; }).join('\n') + '\n</ul>';
    }

    textarea.value = value.substring(0, start) + neu + value.substring(end);
    textarea.focus();
    textarea.setSelectionRange(start, start + neu.length);
  });
})();
</script>

<br />
<a href="kurs_sehen.php?kursid=<?= $kurs->id ?>">Zurück zur Kursseite</a>

<?php
$seite->endeGenerieren();
?>
