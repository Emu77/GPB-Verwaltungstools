<?php
require_once 'check_login.php';
if(isset($_GET['datum'])) {
  $_SESSION['raumplanung_datum']=$_GET['datum'];
}
header('Location:raumplanung.php');
exit;
?>