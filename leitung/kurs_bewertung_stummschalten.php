<?php
require_once 'check_login.php';

$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0;
$stumm=isset($_GET['stumm']) && $_GET['stumm']!='N' ? 'true' : 'false';

$db->query("update gpb_bewertung set stumm=".$stumm." where kursid=".$kursid." and tnid=".$tnid);

header('Location:kurs_bewertung.php?kursid='.$kursid);
exit;
?>