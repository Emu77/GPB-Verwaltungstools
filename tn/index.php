<?php
require_once 'check_login.php';
if($ich->berichtsheft_offen) {
  header('Location:berichtsheft.php');
  exit;
}
header('Location:kurse.php');
exit;
?>