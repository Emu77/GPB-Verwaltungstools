<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';
require_once '../Aufgabe.php';
require_once '../AufgabeTextUpload.php';
require_once '../AufgabeMultipleChoice.php';

$aufgabe = Aufgabe::laden(isset($_GET['aufgabeid']) ? (int)$_GET['aufgabeid'] : 0);
if(empty($aufgabe)) {
  header('Location:kurse.php');
  exit;
}

// Zugriffsprüfung: nur Dozenten des Kurses dürfen die Aufgabe bearbeiten
$kurs = Kurs::einenLaden($aufgabe->kursid, 'DozentKurs');
if(empty($kurs) || !in_array($ich->id, $kurs->dozentenids)) {
  header('Location:kurse.php');
  exit;
}

$fehler = '';
if(!empty($_SESSION['aufgabe_bearbeiten_fehler'])) {
  $fehler = $_SESSION['aufgabe_bearbeiten_fehler'];
  unset($_SESSION['aufgabe_bearbeiten_fehler']);
}

require_once 'DozentSeite.php';
$seite = new DozentSeite('Aufgabe bearbeiten: '.$aufgabe->titel);
$seite->anfangGenerieren();
?>

<p>Kurs: <a href="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>"><?= htmlspecialchars($kurs->titel) ?></a></p>

<?php if(!empty($fehler)): ?>
<div class="nok"><?= htmlspecialchars($fehler) ?></div>
<?php endif; ?>

<form method="post" action="aufgabe_bearbeiten_speichern.php">
  <input type="hidden" name="aufgabeid" value="<?= $aufgabe->id ?>" />

  <p>
    <label for="titel">Titel:</label><br />
    <input type="text" id="titel" name="titel" size="50" value="<?= htmlspecialchars($aufgabe->titel) ?>" required />
  </p>

  <p>
    <label for="beschreibung">Beschreibung:</label><br />
    <textarea id="beschreibung" name="beschreibung" rows="4" cols="60"><?= htmlspecialchars($aufgabe->beschreibung ?? '') ?></textarea>
  </p>

  <p>
    <label for="punkte_max">Maximale Punktzahl:</label>
    <input type="number" id="punkte_max" name="punkte_max" step="0.5" min="0" style="width:6em;" value="<?= $aufgabe->punkteMax!==null ? htmlspecialchars((float)$aufgabe->punkteMax) : '' ?>" />
<?php if($aufgabe instanceof AufgabeMultipleChoice): ?>
    <br /><small>Bei Änderung werden alle Multiple-Choice-Abgaben automatisch neu bepunktet (manuell geänderte Punkte werden dabei überschrieben, Note und Kommentar bleiben).</small>
<?php endif; ?>
  </p>

  <p>
    <label for="sichtbar_ab">Sichtbar ab (optional, leer = sofort):</label>
    <input type="datetime-local" id="sichtbar_ab" name="sichtbar_ab" value="<?= Aufgabe::datumFuerFormular($aufgabe->sichtbarAb) ?>" />
  </p>

  <p>
    <label for="faellig_am">Fällig am (optional):</label>
    <input type="datetime-local" id="faellig_am" name="faellig_am" value="<?= Aufgabe::datumFuerFormular($aufgabe->faelligAm) ?>" />
  </p>

  <p>
    <strong>Aufgabentyp:</strong>
    <?= $aufgabe->typ=='multiple_choice' ? 'Multiple Choice' : 'Text/Upload' ?>
    <small>(nicht änderbar)</small>
  </p>

<?php if($aufgabe instanceof AufgabeMultipleChoice): ?>
  <div style="border:1px solid #ccc;padding:0.5em;margin-bottom:1em;">
    <strong>Fragen</strong> <small>(nur Ansicht – richtige Antwort ist fett)</small>
<?php foreach($aufgabe->ladeFragen() as $nr => $frage): ?>
    <div style="margin:0.5em 0;">
      <?= ($nr+1) ?>. <?= htmlspecialchars($frage->frage) ?>
      <ul style="margin:0.2em 0;">
<?php foreach($frage->optionen as $option): ?>
        <li><?= $option->ist_richtig ? '<strong>'.htmlspecialchars($option->text).' ✓</strong>' : htmlspecialchars($option->text) ?></li>
<?php endforeach; ?>
      </ul>
    </div>
<?php endforeach; ?>
  </div>
<?php endif; ?>

  <p>
    <button type="submit">Speichern</button>
    <a href="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>">Abbrechen</a>
  </p>
</form>

<?php
$seite->endeGenerieren();
?>
