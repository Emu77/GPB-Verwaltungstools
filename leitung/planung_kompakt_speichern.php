<?php
require_once 'check_login.php';
$_SESSION['kompakt']=isset($_GET['kompakt']) && $_GET['kompakt']!='N';
echo 'OK';
exit;
?>