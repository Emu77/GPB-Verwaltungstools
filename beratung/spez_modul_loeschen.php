<?php
require_once 'check_login.php';
$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;
$db->query("delete from gpb_spezausbildung_modul where modulid=".$modulid);
$db->query("delete from gpb_spezmodul where id=".$modulid);
$ausbildungid=isset($_GET['ausbildungid']) ? (int)$_GET['ausbildungid'] : 0;
header('Location:spez_ausbildungen.php'.($ausbildungid>0 ? '#ausbildung_'.$ausbildungid : ''));
exit;
?>