<?php
require_once '../Suche.php';
@session_start();
$suche=new Suche();
$suche->inputInt('familieid');
$suche->inputInt('berufid');
$kuerzel=isset($_POST['kuerzel']) ? $_POST['kuerzel'] : (isset($_GET['kuerzel']) ? $_GET['kuerzel'] : '');
$suche->kuerzel=$kuerzel;
if(!empty($kuerzel)) {
  $suche->addKriterium("m.kuerzel like ?",'%'.$kuerzel.'%','s');
}
$am=isset($_POST['am']) ? $_POST['am'] : (isset($_GET['am']) ? $_GET['am'] : '');
$suche->am=$am;
if(!empty($am) && $am!='0000-00-00') {
  $suche->addKriterium("m.beginn<=?",$am,'s');
  $suche->addKriterium("m.ende>=?",$am,'s');
}
$_SESSION['massnahmen_suche']=$suche;
header('Location:massnahmen.php');
exit;
?>