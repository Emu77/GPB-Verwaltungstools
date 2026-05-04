<?php
require_once 'check_login.php';
require_once 'IntrainSelfkurs.php';

$selfkurs=Selfkurs::einenLaden(isset($_GET['selfkursid']) ? (int)$_GET['selfkursid'] : 0,'IntrainSelfkurs');
if(empty($selfkurs)) {
  header('Location:selfkurse.php');
  exit;
}

require_once 'IntrainSeite.php';
$seite=new IntrainSeite('inTrain-Kurs '.$selfkurs->titel);
$seite->anfangGenerieren();
$selfkurs->makeSehen('sehen');
?>
<a href="selfkurse.php">Zurück zur Liste</a>
<?php
$seite->endeGenerieren();
?>