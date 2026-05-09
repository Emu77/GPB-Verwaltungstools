<?php
require_once 'check_login.php';
require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Startseite');
$seite->anfangGenerieren();
?>
<!-- a href="vonmitis">Importe aus MITIS</a -->
<?php
$seite->endeGenerieren();
?>