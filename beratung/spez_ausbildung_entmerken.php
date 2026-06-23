<?php
require_once 'check_login.php';

$ausbildungid=isset($_GET['ausbildungid']) ? (int)$_GET['ausbildungid'] : 0;

if(isset($_SESSION['spez_ausbildungids']) && isset($_SESSION['spez_ausbildungids'][$ausbildungid])) {
  unset($_SESSION['spez_ausbildungids'][$ausbildungid]);
}

header('Location:spezialisten.php');
exit;
?>