<?php
require_once 'check_login.php';
$anfangsdatum=isset($_POST['anfangsdatum']) ? strtotime($_POST['anfangsdatum']) : (isset($_GET['anfangsdatum']) ? strtotime($_GET['anfangsdatum']) : 0);
if($anfangsdatum>0) {
  $_SESSION['spez_anfangsdatum']=date('Y-m-d',$anfangsdatum);
}
header('Location:spezialisten.php');
exit;
?>