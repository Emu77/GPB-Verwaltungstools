<?php
@session_start();
$zeitmodell=isset($_POST['zeitmodell']) ? $_POST['zeitmodell'] : false;
if(!empty($zeitmodell)) {
  $_SESSION['spez_zeitmodell']=$zeitmodell;
}
header('Location:spezialisten.php');
exit;
?>