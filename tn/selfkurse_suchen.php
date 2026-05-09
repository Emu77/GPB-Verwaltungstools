<?php
require_once 'check_login.php';

$suche=new Suche();
$suche->inputVonBis('einstieg','ausstieg');
$suche->inputString('titel');
$suche->inputBoolean('nurich',"(sktn.tnid=".$ich->id." and sktn.einstieg is not null and sktn.einstieg<>'0000-00-00')");
$suche->inputInt('moodleid');
$_SESSION['selfkurse_suche']=$suche;
header('Location:kurse.php');
exit;
?>