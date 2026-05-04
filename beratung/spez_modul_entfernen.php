<?php
require_once 'check_login.php';
$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;
$ausbildungid=isset($_GET['ausbildungid']) ? (int)$_GET['ausbildungid'] : 0;
$db->query("delete from gpb_spezausbildung_modul where modulid=".$modulid." and ausbildungid=".$ausbildungid);
header('Location:spez_ausbildungen.php#ausbildung_'.$ausbildungid);
exit;
?>