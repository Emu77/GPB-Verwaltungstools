<?php
require_once 'check_login.php';

$zertifid=isset($_GET['zertif']) ? (int)$_GET['zertif'] : 0;
$modids=isset($_GET['modids']) ? $_GET['modids'] : '';
if(!empty($modids) && $zertifid>0) {
  $db->query("update gpb_intrainmodul set zertifid=".$zertifid." where id in(".$modids.")");
}
echo 'OK';
exit;
?>