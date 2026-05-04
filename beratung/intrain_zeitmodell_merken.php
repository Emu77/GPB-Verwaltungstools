<?php
@session_start();
$zeitmodell=isset($_POST['zeitmodell']) ? $_POST['zeitmodell'] : false;
if(!empty($zeitmodell)) {
  $_SESSION['intrain_zeitmodell']=$zeitmodell;
}
require_once 'intrain_seite.php';
exit;
?>