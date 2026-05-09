<?php
require_once 'check_login.php';

$dozentid=isset($_GET['dozentid']) ? (int)$_GET['dozentid'] : 0;
$idrverfuegbar=isset($_GET['idrverfuegbar']) && $_GET['idrverfuegbar']!='N';
if($dozentid>0) {
  $db->query("update gpb_dozent set idrverfuegbar=".($idrverfuegbar ? 'true' : 'false')." where id=".$dozentid." limit 1");
}

$redirect=isset($_GET['redirect']) ? $_GET['redirect'] : false;
if($redirect) {
  header('Location:'.$redirect.'?dozentid='.$dozentid);
  exit;
}
echo 'OK';
exit;
?>