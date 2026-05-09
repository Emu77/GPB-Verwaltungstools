<?php
require_once 'check_login.php';
require_once 'LeitungSeite.php';
$seite=new LeitungSeite('Startseite');
$seite->anfangGenerieren();
?>

<?php
$seite->endeGenerieren();
?>