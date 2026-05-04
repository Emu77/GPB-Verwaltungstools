<?php
require_once '../logout_funktion.php';
if(isset($_SESSION['beratung_ich'])) {
  unset($_SESSION['beratung_ich']);
}
logout();
header('Location:login.php');
exit;
?>