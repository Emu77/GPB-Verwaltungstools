<?php
require_once '../../db.php';
require_once '../../Seite.php';
$seite=new Seite('Importe aus MITIS','../');
$seite->anfangGenerieren();
?>
<h2>Maßnahmen importieren</h2>
<form action="massnahmen_import.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
Datei mitisdata/MitisExportMassnahme.csv <input type="file" name="MitisExportMassnahme" /> <input type="submit" value="Importieren" />
</form>
<h2>Klassen importieren</h2>
<form action="klassen_import.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
Datei mitisdata/MitisExportKlasse.csv <input type="file" name="MitisExportKlasse" /> <input type="submit" value="Importieren" />
</form>
<h2>TN importieren</h2>
<form action="tn_import.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="passwort" value="vonmitis" />
Datei mitisdata/MitisExportTeilnehmer.csv <input type="file" name="MitisExportTeilnehmer" /> <input type="submit" value="Importieren" />
</form>
<h2>Zuweisung TN &lt;-&gt; Maßnahmen importieren</h2>
<form action="massnahmen_tn_import.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
Datei mitisdata/MitisExportMassnahme_Teilnehmer.csv <input type="file" name="MitisExportMassnahme_Teilnehmer" /> <input type="submit" value="Importieren" />
</form>
<h2>Zuweisung TN &lt;-&gt; Klassen importieren</h2>
<form action="klassen_tn_import.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
Datei mitisdata/MitisExportKlasse_Teilnehmer.csv <input type="file" name="MitisExportKlasse_Teilnehmer" /> <input type="submit" value="Importieren" />
</form>
<h2>Anwesenheiten importieren</h2>
<form action="anwesenheiten_import.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
Datei mitisdata/MitisExportAnwesenheiten.csv <input type="file" name="MitisExportAnwesenheiten" /> <input type="submit" value="Importieren" />
</form>
<br />
<h2>Dozenten importieren</h2>
<form action="dozenten_import.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
Datei DozentenAusMoodle.csv <input type="file" name="DozentenAusMoodle" /><br />
Datei uebersicht_dozenten_jahrgaenge.csv <input type="file" name="uebersicht_dozenten_jahrgaenge" /><br />
<input type="submit" value="Importieren" />
</form>
<br />
<h2>Beratung Vertragszahlen importieren</h2>
<form action="vertragszahlen_import.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
Datei MitisExportVertragszahlen.csv <input type="file" name="MitisExportVertragszahlen" /> <input type="submit" value="Importieren" />
</form>
<h2>inTrain-Pakete importieren</h2>
<form action="intrainpakete_import.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
Datei mitisdata/MitisExportIntrainPaket.csv <input type="file" name="MitisExportIntrainPaket" /> <input type="submit" value="Importieren" />
</form>
<h2>inTrain-Module importieren</h2>
<form action="intrainmodule_import.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
Datei mitisdata/MitisExportIntrainModul.csv <input type="file" name="MitisExportIntrainModul" /> <input type="submit" value="Importieren" />
</form>
<h2>Zuweisung inTrain-Paket &lt;-&gt; inTrain-Modul importieren</h2>
<form action="intrainpaket_modul_import.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
Datei mitisdata/MitisExportIntrainPaket_Modul.csv <input type="file" name="MitisExportIntrainPaket_Modul" /> <input type="submit" value="Importieren" />
</form>
<a href="../">Zurück</a>
<?php
$seite->endeGenerieren();
?>