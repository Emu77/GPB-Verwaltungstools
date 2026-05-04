<?php
require_once '../logout_funktion.php';
if(isset($_SESSION['intrain_ich'])) {
  unset($_SESSION['intrain_ich']);
}
if(isset($_SESSION['dozent_ich'])) {
  unset($_SESSION['dozent_ich']);
}
logout();
header('Location:login.php');
exit;
?>