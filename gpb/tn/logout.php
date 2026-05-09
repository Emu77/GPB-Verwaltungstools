<?php
require_once '../logout_funktion.php';
if(isset($_SESSION['tn_ich'])) {
  unset($_SESSION['tn_ich']);
}
logout();
header('Location:login.php');
exit;
?>