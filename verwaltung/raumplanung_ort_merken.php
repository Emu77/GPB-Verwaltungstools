<?php
require_once 'check_login.php';
if(isset($_GET['ort'])) {
  $_SESSION['raumplanung_ort']=$_GET['ort'];
  if(isset($_SESSION['raumplanung_zusatz'])) {
    unset($_SESSION['raumplanung_zusatz']);
  }
}
header('Location:raumplanung.php');
exit;
?>