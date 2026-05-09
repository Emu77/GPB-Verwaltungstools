<?php
require_once 'check_login.php';

$berufid=isset($_POST['berufid']) ? (int)$_POST['berufid'] : (isset($_GET['berufid']) ? (int)$_GET['berufid'] : 0);
$id=isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$titel=isset($_POST['titel']) ? $_POST['titel'] : (isset($_GET['titel']) ? $_GET['titel'] : '');
$kuerzel=isset($_POST['kuerzel']) ? $_POST['kuerzel'] : (isset($_GET['kuerzel']) ? $_GET['kuerzel'] : '');
$dauer=isset($_POST['dauer']) ? (int)$_POST['dauer'] : (isset($_GET['dauer']) ? (int)$_GET['dauer'] : 2);
if($id>0) {
  $stmt=$db->prepare("update gpb_modul set titel=?,kuerzel=?,dauer=? where id=? limit 1");
  $stmt->bind_param('ssii',$titel,$kuerzel,$dauer,$id);
  $stmt->execute();
} else {
  $stmt=$db->prepare("insert into gpb_modul(titel,kuerzel,dauer) values(?,?,?)");
  $stmt->bind_param('ssi',$titel,$kuerzel,$dauer);
  $stmt->execute();
  $id=$db->insert_id;
  if($berufid>0) {
    $db->query("insert into gpb_beruf_modul(berufid,modulid) values(".$berufid.",".$id.")");
  }
}
header('Location:beruf_module.php?berufid='.$berufid);
exit;
?>