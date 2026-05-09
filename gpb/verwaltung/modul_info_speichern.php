<?php
require_once 'check_login.php';

$berufid=isset($_POST['berufid']) ? (int)$_POST['berufid'] : (isset($_GET['berufid']) ? (int)$_GET['berufid'] : 0);
$id=isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$spalte=isset($_GET['spalte']) ? $_GET['spalte'] : '';
$wert=isset($_GET['wert']) ? $_GET['wert'] : '';
if($id>0) {
  if($spalte=='pflichtig') {
    $stmt=$db->prepare("update gpb_beruf_modul set pflichtig=not pflichtig where berufid=? and modulid=? limit 1");
    $stmt->bind_param('ii',$berufid,$id);
    $stmt->execute();
  } else {
    $stmt=$db->prepare("update gpb_modul set `".$spalte."`=? where id=? limit 1");
    $stmt->bind_param('si',$wert,$id);
    $stmt->execute();
  }
}
header('Location:beruf_module.php?berufid='.$berufid);
exit;
?>