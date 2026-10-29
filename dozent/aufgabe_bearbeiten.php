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
    <br /><small>Bei Änderung (oder geänderter richtiger Antwort) werden alle Multiple-Choice-Abgaben automatisch neu bepunktet (manuell geänderte Punkte werden dabei überschrieben, Note und Kommentar bleiben).</small>
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
<?php $anzahlAbgaben = $aufgabe->anzahlAbgaben(); ?>
  <div id="fragen_bereich" style="border:1px solid #ccc;padding:0.5em;margin-bottom:1em;">
    <strong>Fragen</strong> <small>(Kreis = richtige Antwort)</small>
<?php if($anzahlAbgaben>0): ?>
    <p class="nok" style="margin:0.3em 0;">Achtung: <?= $anzahlAbgaben ?> Abgabe<?= $anzahlAbgaben==1 ? '' : 'n' ?> vorhanden. Werden Fragen/Antworten gelöscht, hinzugefügt oder die richtige Antwort geändert, werden alle Abgaben neu bepunktet (neue Fragen zählen bei bisherigen Abgaben als nicht beantwortet). Reine Textkorrekturen ändern nichts an den Punkten.</p>
<?php endif; ?>
<?php foreach($aufgabe->ladeFragen() as $nr => $frage): ?>
<?php $hatRichtige = false; foreach($frage->optionen as $o) { if($o->ist_richtig) $hatRichtige = true; } ?>
    <div class="frage-block" data-fid="<?= $frage->id ?>" style="border:1px solid #ddd;padding:0.5em;margin:0.5em 0;">
      <label><?= ($nr+1) ?>. Frage: <input type="text" name="frage_text[<?= $frage->id ?>]" value="<?= htmlspecialchars($frage->frage) ?>" style="width:60%;" required /></label>
      <label style="margin-left:1em;"><input type="checkbox" name="frage_loeschen[<?= $frage->id ?>]" value="1" class="frage-loeschen-cb" /> Frage löschen</label>
<?php if(!$hatRichtige): ?>
      <span style="color:#c00;font-weight:bold;">– keine richtige Antwort hinterlegt!</span>
<?php endif; ?>
      <div class="optionen-liste">
<?php foreach($frage->optionen as $option): ?>
        <div class="option-zeile" style="margin-left:1.5em;">
          <input type="radio" name="richtig[<?= $frage->id ?>]" value="<?= $option->id ?>" <?= $option->ist_richtig ? 'checked' : '' ?> required title="Richtige Antwort" />
          <input type="text" name="option_text[<?= $option->id ?>]" value="<?= htmlspecialchars($option->text) ?>" style="width:45%;" required />
          <label><input type="checkbox" name="option_loeschen[<?= $option->id ?>]" value="1" class="option-loeschen-cb" /> löschen</label>
        </div>
<?php endforeach; ?>
      </div>
      <p style="margin:0.3em 0 0 1.5em;"><button type="button" class="neue-option-btn">+ Antwortoption</button></p>
    </div>
<?php endforeach; ?>
    <div id="neue_fragen_liste"></div>
    <p><button type="button" id="neue_frage_btn">+ Frage hinzufügen</button></p>
  </div>

<script>
(function () {
  var bereich = document.getElementById('fragen_bereich');
  var zaehler = 0; // eindeutige Schlüssel für neue Optionen/Fragen

  function optionZeile(radioName, radioValue, textName) {
    var div = document.createElement('div');
    div.className = 'option-zeile';
    div.style.marginLeft = '1.5em';
    div.innerHTML = '<input type="radio" required title="Richtige Antwort" /> '
      + '<input type="text" style="width:45%;" required /> '
      + '<button type="button" class="neu-entfernen">entfernen</button>';
    div.querySelector('input[type=radio]').name = radioName;
    div.querySelector('input[type=radio]').value = radioValue;
    div.querySelector('input[type=text]').name = textName;
    return div;
  }

  // + Antwortoption bei bestehender Frage
  bereich.addEventListener('click', function (e) {
    if (!e.target.classList.contains('neue-option-btn')) return;
    var block = e.target.closest('.frage-block');
    var fid = block.getAttribute('data-fid');
    var key = 'n' + (++zaehler);
    var zeile = optionZeile('richtig[' + fid + ']', key, 'neue_option[' + fid + '][' + key + ']');
    block.querySelector('.optionen-liste').appendChild(zeile);
    zeile.querySelector('input[type=text]').focus();
  });

  // + Frage hinzufügen (neue Frage mit zwei leeren Optionen)
  document.getElementById('neue_frage_btn').addEventListener('click', function () {
    var i = ++zaehler;
    var block = document.createElement('div');
    block.className = 'frage-block neue-frage';
    block.style.cssText = 'border:1px dashed #888;padding:0.5em;margin:0.5em 0;';
    block.innerHTML = '<label>Neue Frage: <input type="text" style="width:60%;" required /></label> '
      + '<button type="button" class="neu-entfernen-frage">Frage entfernen</button>'
      + '<div class="optionen-liste"></div>'
      + '<p style="margin:0.3em 0 0 1.5em;"><button type="button" class="neue-frage-option-btn">+ Antwortoption</button></p>';
    block.querySelector('input[type=text]').name = 'neue_frage[' + i + '][text]';
    block.setAttribute('data-neu', i);
    block.setAttribute('data-optionen', '0');
    document.getElementById('neue_fragen_liste').appendChild(block);
    neueFrageOption(block); neueFrageOption(block);
    block.querySelector('input[type=text]').focus();
  });

  function neueFrageOption(block) {
    var i = block.getAttribute('data-neu');
    var j = parseInt(block.getAttribute('data-optionen'), 10);
    block.setAttribute('data-optionen', j + 1);
    block.querySelector('.optionen-liste').appendChild(
      optionZeile('neue_frage[' + i + '][richtig]', j, 'neue_frage[' + i + '][optionen][' + j + ']'));
  }

  bereich.addEventListener('click', function (e) {
    if (e.target.classList.contains('neue-frage-option-btn')) neueFrageOption(e.target.closest('.frage-block'));
    if (e.target.classList.contains('neu-entfernen')) e.target.closest('.option-zeile').remove();
    if (e.target.classList.contains('neu-entfernen-frage')) e.target.closest('.frage-block').remove();
  });

  // "löschen" angehakt: Zeile/Frage ausgrauen und Pflichtfelder darin
  // abschalten, damit das Formular trotzdem abgeschickt werden kann
  bereich.addEventListener('change', function (e) {
    var cb = e.target;
    var ziel = cb.classList.contains('frage-loeschen-cb') ? cb.closest('.frage-block')
             : cb.classList.contains('option-loeschen-cb') ? cb.closest('.option-zeile') : null;
    if (!ziel) return;
    ziel.style.opacity = cb.checked ? '0.4' : '';
    ziel.querySelectorAll('input[type=text], input[type=radio]').forEach(function (inp) {
      if (cb.classList.contains('option-loeschen-cb') && inp.type === 'radio') return; // Radio-Gruppe bleibt Pflicht
      inp.required = !cb.checked;
    });
    if (cb.classList.contains('frage-loeschen-cb')) {
      ziel.querySelectorAll('input[type=radio]').forEach(function (r) { r.required = !cb.checked; });
    }
  });
})();
</script>
<?php endif; ?>

  <p>
    <button type="submit">Speichern</button>
    <a href="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>">Abbrechen</a>
  </p>
</form>

<?php
$seite->endeGenerieren();
?>
