<?php
require_once 'check_login.php';

$zusaetzlicheklassen=file_get_contents('vonmitis/zusaetzlicheklassen.txt');

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Klassen Sonderimports');
$seite->anfangGenerieren();
?>
<b>Folgende Klassen sollen zusätzlich aus MITIS transferiert werden<br />
1 Klasse pro Zeile, der tatsächliche Transfer findet in DownloadMitisData statt</b>
<form action="klassen_zusaetzlich_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
<textarea name="zusaetzlicheklassen" style="width:400px;height:700px;"><?= $zusaetzlicheklassen ?></textarea><br />
<input type="submit" value="Speichern" />
</form>
<?php
if(isset($_SESSION['zusaetzlicheklassen_nachricht'])) {
  echo $_SESSION['zusaetzlicheklassen_nachricht'];
  unset($_SESSION['zusaetzlicheklassen_nachricht']);
}
?>
<br />
<a href="klassen.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>