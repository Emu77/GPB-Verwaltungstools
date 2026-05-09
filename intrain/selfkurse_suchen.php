<?php
require_once '../Suche.php';
@session_start();
$suche=new Suche();
$suche->inputString('titel');
$_SESSION['selfkurs_suche']=$suche;

$redirect=isset($_POST['redirect']) ? $_POST['redirect'] : (isset($_GET['redirect']) ? $_GET['redirect'] : 'selfkurse.php');
header('Location:'.$redirect);
exit;
?>