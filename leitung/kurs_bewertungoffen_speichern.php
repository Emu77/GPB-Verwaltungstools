<?php
require_once 'check_login.php';

$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$bewertungoffen=isset($_GET['bewertungoffen']) && $_GET['bewertungoffen']=='N' ? 'false' : 'true';

$db->query("update gpb_kurs set bewertungoffen=".$bewertungoffen." where id=".$kursid." limit 1");

$redirect=isset($_GET['redirect']) ? $_GET['redirect'] : false;
header('Location:'.($redirect ? $redirect : 'kurs_bewertung.php?kursid='.$kursid));
exit;
?>