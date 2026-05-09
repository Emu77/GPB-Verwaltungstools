<?php
require_once 'check_login.php';
$zertifid=isset($_GET['zertif']) ? (int)$_GET['zertif'] : 0;
$spalte=isset($_GET['spalte']) ? str_replace('`','',$_GET['spalte']) : false;
$neu=isset($_GET['neu']) ? $_GET['neu'] : '';
if($zertifid>0 && !empty($spalte)) {
  $stmt=$db->prepare("update gpb_intrainzertif set `".$spalte."`=? where id=?");
  $stmt->bind_param('si',$neu,$zertifid);
  $stmt->execute();
}
echo 'OK';
exit;
?>