<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';
require_once '../Aufgabe.php';

$kurs = Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0, 'DozentKurs');
if(empty($kurs) || !in_array($ich->id, $kurs->dozentenids)) {
  header('Location:kurse.php');
  exit;
}

$fehler = '';
if(!empty($_SESSION['aufgabe_erstellen_fehler'])) {
  $fehler = $_SESSION['aufgabe_erstellen_fehler'];
  unset($_SESSION['aufgabe_erstellen_fehler']);
}

require_once 'DozentSeite.php';
$seite = new DozentSeite('Neue Aufgabe: '.$kurs->titel);
$seite->anfangGenerieren();
?>

<p>Kurs: <a href="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>"><?= htmlspecialchars($kurs->titel) ?></a></p>

<?php if(!empty($fehler)): ?>
<div class="nok"><?= htmlspecialchars($fehler) ?></div>
<?php endif; ?>

<form method="post" action="aufgabe_erstellen_speichern.php" id="aufgabeform">
  <input type="hidden" name="kursid" value="<?= $kurs->id ?>" />

  <p>
    <label for="titel">Titel:</label><br />
    <input type="text" id="titel" name="titel" size="50" required />
  </p>

  <p>
    <label for="beschreibung">Beschreibung:</label><br />
    <textarea id="beschreibung" name="beschreibung" rows="4" cols="60"></textarea>
  </p>

  <p>
    <label for="punkte_max">Maximale Punktzahl:</label>
    <input type="number" id="punkte_max" name="punkte_max" step="0.5" min="0" style="width:6em;" />
  </p>

  <p>
    <label for="sichtbar_ab">Sichtbar ab (optional, leer = sofort):</label>
    <input type="datetime-local" id="sichtbar_ab" name="sichtbar_ab" />
  </p>

  <p>
    <label for="faellig_am">Fällig am (optional):</label>
    <input type="datetime-local" id="faellig_am" name="faellig_am" />
  </p>

  <p>
    <strong>Aufgabentyp:</strong><br />
    <label><input type="radio" name="typ" value="text_upload" checked onchange="aufgabeTypWechsel()" /> Text/Upload</label>
    <label style="margin-left:1em;"><input type="radio" name="typ" value="multiple_choice" onchange="aufgabeTypWechsel()" /> Multiple Choice</label>
  </p>

  <div id="fragen_bereich" style="display:none;border:1px solid #ccc;padding:0.5em;margin-bottom:1em;">
    <strong>Fragen</strong>
    <div id="fragen_liste"></div>
    <p><button type="button" onclick="frageHinzufuegen()">+ Frage hinzufügen</button></p>
  </div>

  <p><button type="submit">Aufgabe anlegen</button></p>
</form>

<template id="frage_template">
  <div class="frage-block" style="border:1px solid #ddd;padding:0.5em;margin:0.5em 0;">
    <label>Frage: <input type="text" class="frage-text" style="width:70%;" required /></label>
    <button type="button" class="frage-loeschen">Frage löschen</button>
    <div class="optionen-liste"></div>
    <p><button type="button" class="option-hinzufuegen">+ Antwortoption hinzufügen</button></p>
  </div>
</template>

<template id="option_template">
  <div class="option-zeile">
    <label>
      <input type="radio" class="option-richtig" />
      <input type="text" class="option-text" style="width:50%;" required />
    </label>
    <button type="button" class="option-loeschen">Löschen</button>
  </div>
</template>

<script>
function aufgabeTypWechsel() {
  var mc = document.querySelector('input[name="typ"][value="multiple_choice"]').checked;
  document.getElementById('fragen_bereich').style.display = mc ? '' : 'none';
}

var frageZaehler = 0;

function frageHinzufuegen() {
  var tpl = document.getElementById('frage_template');
  var block = tpl.content.cloneNode(true).querySelector('.frage-block');
  var index = frageZaehler++;

  block.querySelector('.frage-text').name = 'frage['+index+'][text]';

  var optionenGroup = 'frage_'+index+'_richtig';
  block.querySelectorAll('.option-richtig').forEach(function(r){ r.name = optionenGroup; });

  block.querySelector('.frage-loeschen').addEventListener('click', function() {
    block.remove();
  });

  var optionZaehler = 0;
  block.querySelector('.option-hinzufuegen').addEventListener('click', function() {
    optionHinzufuegen(block, index, optionenGroup, optionZaehler++);
  });

  document.getElementById('fragen_liste').appendChild(block);

  // gleich zwei leere Optionen vorschlagen
  optionHinzufuegen(block, index, optionenGroup, optionZaehler++);
  optionHinzufuegen(block, index, optionenGroup, optionZaehler++);
}

function optionHinzufuegen(block, frageIndex, optionenGroup, optionIndex) {
  var tpl = document.getElementById('option_template');
  var zeile = tpl.content.cloneNode(true).querySelector('.option-zeile');

  var radio = zeile.querySelector('.option-richtig');
  radio.name = optionenGroup;
  radio.value = optionIndex;

  zeile.querySelector('.option-text').name = 'frage['+frageIndex+'][optionen]['+optionIndex+']';

  zeile.querySelector('.option-loeschen').addEventListener('click', function() {
    zeile.remove();
  });

  block.querySelector('.optionen-liste').appendChild(zeile);
}

// beim Laden schon eine erste Frage anbieten, falls MC direkt ausgewählt wird
document.getElementById('aufgabeform').addEventListener('submit', function(e) {
  var mc = document.querySelector('input[name="typ"][value="multiple_choice"]').checked;
  if(mc && document.getElementById('fragen_liste').children.length===0) {
    e.preventDefault();
    alert('Bitte mindestens eine Frage anlegen.');
  }
});
</script>

<br />
<a href="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>">Zurück zum Kurs</a>

<?php
$seite->endeGenerieren();
?>
