<?php
require_once 'check_login.php';
if(isset($_GET['zusatz'])) {
  $_SESSION['raumplanung_zusatz']=$_GET['zusatz'];
}
header('Location:raumplanung.php');
exit;
?>