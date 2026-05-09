<?php
require_once '../logout_funktion.php';
if(isset($_SESSION['leitung_ich'])) {
  unset($_SESSION['leitung_ich']);
}
if(isset($_SESSION['verwaltung_ich'])) {
  unset($_SESSION['verwaltung_ich']);
}
logout();
header('Location:login.php');
exit;
?>