<?php
require_once 'check_login.php';

$id=isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id>0) {
  $db->query("delete from gpb_dozent_verfuegbarkeit where id=".$id." limit 1");
}

$dozentid=isset($_GET['dozentid']) ? (int)$_GET['dozentid'] : 0;
$redirect=isset($_GET['redirect']) ? $_GET['redirect'] : false;
if($redirect) {
  header('Location:'.$redirect.'&dozentid='.$dozentid);
  exit;
}
echo 'OK';
exit;
?>