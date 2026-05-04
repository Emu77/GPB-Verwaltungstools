<?php
require_once 'check_login.php';
$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;
$kuerzel=isset($_GET['kuerzel']) ? $_GET['kuerzel'] : false;
if(!empty($modulid) && $kuerzel!==false){
  $stmt=$db->prepare("update gpb_modul set kuerzel=? where id=?");
  $stmt->bind_param('si',$kuerzel,$modulid);
  $stmt->execute();
}
echo 'OK';
exit;
?>