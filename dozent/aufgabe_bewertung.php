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

// Zugriffsprüfung: nur Dozenten des Kurses dürfen die Noten sehen/vergeben
$kurs = Kurs::einenLaden($aufgabe->kursid, 'DozentKurs');
if(empty($kurs) || !in_array($ich->id, $kurs->dozentenids)) {
  header('Location:kurse.php');
  exit;
}

$teilnehmer = $aufgabe->ladeTeilnehmerMitAbgabe();

require_once 'DozentSeite.php';
$seite = new DozentSeite('Aufgabe bewerten: '.$aufgabe->titel);
$seite->anfangGenerieren();
?>

<p>
  Kurs: <a href="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>"><?= htmlspecialchars($kurs->titel) ?></a>
</p>

<?php if(!empty($aufgabe->beschreibung)): ?>
<p><?= nl2br(htmlspecialchars($aufgabe->beschreibung)) ?></p>
<?php endif; ?>

<?php if(!empty($aufgabe->punkteMax)): ?>
<p>Maximale Punktzahl: <?= htmlspecialchars($aufgabe->punkteMax) ?></p>
<?php endif; ?>

<?php if(empty($teilnehmer)): ?>
<p><em>Keine Teilnehmer in diesem Kurs.</em></p>
<?php else: ?>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-top:1em;">
  <tr>
    <th>TN</th>
    <th>Abgabe</th>
    <th>Abgegeben am</th>
    <th>Bewertung</th>
  </tr>
<?php foreach($teilnehmer as $tn): ?>
  <tr>
    <td><?= htmlspecialchars($tn->vorname.' '.$tn->nachname) ?></td>
    <td>
<?php if(empty($tn->abgabeid)): ?>
      <em>Keine Abgabe</em>
<?php else: ?>
<?php if($aufgabe instanceof AufgabeTextUpload): ?>
<?php if(!empty($tn->text)): ?>
      <div style="max-width:300px;white-space:pre-wrap;"><?= htmlspecialchars($tn->text) ?></div>
<?php endif; ?>
<?php if(!empty($tn->dateiname)): ?>
      <div><a href="../aufgabe_anhang_download.php?abgabeid=<?= $tn->abgabeid ?>" target="aufgabe_download_frame" onclick="var f=document.getElementById('aufgabe_download_frame'); if(!f){f=document.createElement('iframe'); f.name='aufgabe_download_frame'; f.id='aufgabe_download_frame'; f.style.display='none'; document.body.appendChild(f);}"><?= htmlspecialchars($tn->dateiname) ?></a></div>
<?php endif; ?>
<?php elseif($aufgabe instanceof AufgabeMultipleChoice): ?>
      <em>Beantwortet</em>
<?php endif; ?>
<?php endif; ?>
    </td>
    <td><?= empty($tn->abgegeben_am) ? '-' : date('d.m.Y H:i', strtotime($tn->abgegeben_am)) ?></td>
    <td>
<?php if(empty($tn->abgabeid)): ?>
      -
<?php else: ?>
      <form method="post" action="aufgabe_bewertung_speichern.php">
        <input type="hidden" name="abgabeid" value="<?= $tn->abgabeid ?>" />
        <input type="hidden" name="aufgabeid" value="<?= $aufgabe->id ?>" />
        Punkte: <input type="text" name="punkte" value="<?= htmlspecialchars($tn->punkte ?? '') ?>" size="4" />
        Note: <input type="text" name="note" value="<?= htmlspecialchars($tn->note ?? '') ?>" size="4" />
        <br />
        Kommentar: <input type="text" name="kommentar" value="<?= htmlspecialchars($tn->kommentar ?? '') ?>" size="30" />
        <button type="submit">Speichern</button>
<?php if(!empty($tn->bewertet_am)): ?>
        <div><em>Zuletzt bewertet am <?= date('d.m.Y H:i', strtotime($tn->bewertet_am)) ?></em></div>
<?php endif; ?>
      </form>
<?php endif; ?>
    </td>
  </tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<br />
<a href="mini_kurs_sehen.php?kursid=<?= $kurs->id ?>">Zurück zum Kurs</a>

<?php
$seite->endeGenerieren();
?>
