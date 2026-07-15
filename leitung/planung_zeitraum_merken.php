<?php
require_once 'check_login.php';
if(isset($_GET['beginn'])) {
  if(empty($_GET['beginn'])) {
    if(isset($_SESSION['planungbeginn'])) {
      unset($_SESSION['planungbeginn']);
    }
  } else {
    $_SESSION['planungbeginn']=$_GET['beginn'];
  }
}
if(isset($_GET['ende'])) {
  if(empty($_GET['ende'])) {
    if(isset($_SESSION['planungende'])) {
      unset($_SESSION['planungende']);
    }
  } else {
    $_SESSION['planungende']=$_GET['ende'];
  }
}
header('Location:planung.php');
exit;
?>