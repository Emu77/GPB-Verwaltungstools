<?php
require_once 'check_login.php';

$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
if($kursid<=0) {
  header('Location:noten.php');
  exit;
}
$inauszugsichtbar=isset($_GET['inauszugsichtbar']) && $_GET['inauszugsichtbar']=='true';
$db->query("update gpb_note set inauszugsichtbar=".($inauszugsichtbar ? 'true' : 'false')." where kursid=".$kursid." and tnid=".$ich->id." limit 1");
header('Location:noten.php');
exit;
?>