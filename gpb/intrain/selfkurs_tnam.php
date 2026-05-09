<?php
require_once 'check_login.php';
$tnam=isset($_POST['tnam']) ? $_POST['tnam'] : (isset($_GET['tnam']) ? $_GET['tnam'] : null);
if(empty($tnam)) {
  if(isset($_SESSION['tnam'])) {
    unset($_SESSION['tnam']);
  }
} else {
  $_SESSION['tnam']=$tnam;
}
$selfkursid=isset($_POST['selfkursid']) ? (int)$_POST['selfkursid'] : (isset($_GET['selfkursid']) ? (int)$_GET['selfkursid'] : 0);
header('Location:selfkurs_tn.php?selfkursid='.$selfkursid);
exit;
?>