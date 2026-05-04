<?php
require_once 'check_login.php';

$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : (isset($_POST['tnid']) ? (int)$_POST['tnid'] : 0);
$notiz=isset($_GET['notiz']) ? trim(str_replace("\r","\n",str_replace("\r\n","\n",$_GET['notiz']))) 
  : (isset($_POST['notiz']) ? trim(str_replace("\r","\n",str_replace("\r\n","\n",$_POST['notiz']))) 
  : '');
$redirect=isset($_POST['redirect']) && $_POST['redirect']!='N';
  
if($tnid>0) {
  if(empty($notiz)) {
    $db->query("delete from gpb_fehlzeiten_notiz where tnid=".$tnid);
  } else {
    $result=$db->query("select * from gpb_fehlzeiten_notiz where tnid=".$tnid." limit 1");
    $fehlzeiten_notiz=$result->fetch_object();
    $result->free();
    if($fehlzeiten_notiz) {
      $stmt=$db->prepare("update gpb_fehlzeiten_notiz set notiz=? where tnid=? limit 1");
      $stmt->bind_param('si',$notiz,$tnid);
      $stmt->execute();
    } else {
      $stmt=$db->prepare("insert into gpb_fehlzeiten_notiz(tnid,notiz) values(?,?)");
      $stmt->bind_param('is',$tnid,$notiz);
      $stmt->execute();
//      $id=$db->insert_id;
    }
  }
}

if($redirect) {
  header('Location:tn_sehen.php?tnid='.$tnid);
  exit;
}
echo 'OK';
exit;
?>