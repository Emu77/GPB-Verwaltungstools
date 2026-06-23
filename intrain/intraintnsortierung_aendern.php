<?php
require_once 'check_login.php';

$tnsortierung=$ich->intraintnsortierung=='nachname,vorname' ? 'vorname,nachname' : 'nachname,vorname';
$db->query("update gpb_dozent set intraintnsortierung='".$tnsortierung."' where id=".$ich->id." limit 1");
$ich->intraintnsortierung=$tnsortierung;

$url=isset($_GET['url']) ? $_GET['url'] : null;
header('Location:'.(empty($url) ? 'index.php' : $url));
exit;
?>