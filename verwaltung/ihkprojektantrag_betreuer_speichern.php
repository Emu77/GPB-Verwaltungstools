<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0;
$betreuerid=isset($_GET['betreuerid']) ? (int)$_GET['betreuerid'] : 0;
if($betreuerid<=0) {
  $betreuerid=null;
}
if($kursid>0 && $tnid>0) {
  $result=$db->query("select * from gpb_ihkprojektantrag where kursid=".$kursid." and tnid=".$tnid." limit 1");
  $schon=$result->fetch_object();
  $result->free();
  if($schon) {
    if($schon->betreuerid!=$betreuerid) {
      $stmt=$db->prepare("update gpb_ihkprojektantrag set betreuerid=? where kursid=? and tnid=?");
      $stmt->bind_param('iii',$betreuerid,$kursid,$tnid);
      $stmt->execute();
    }
  } else {
    if($betreuerid!=null) {
      $stmt=$db->prepare("insert into gpb_ihkprojektantrag(kursid,tnid,betreuerid) values(?,?,?)");
      $stmt->bind_param('iii',$kursid,$tnid,$betreuerid);
      $stmt->execute();
    }
  }
}
echo 'OK';
exit;
?>