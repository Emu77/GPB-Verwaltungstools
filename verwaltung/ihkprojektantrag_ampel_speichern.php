<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$tnid=isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0;
$ampel=isset($_GET['ampel']) ? $_GET['ampel'] : 'red';
if($kursid>0 && $tnid>0 && !empty($ampel)) {
  $result=$db->query("select * from gpb_ihkprojektantrag where kursid=".$kursid." and tnid=".$tnid." limit 1");
  $schon=$result->fetch_object();
  $result->free();
  if($schon) {
    if($schon->ampel!=$ampel) {
      $stmt=$db->prepare("update gpb_ihkprojektantrag set ampel=? where kursid=? and tnid=?");
      $stmt->bind_param('sii',$ampel,$kursid,$tnid);
      $stmt->execute();
    }
  } else {
    if($ampel!='red') {
      $stmt=$db->prepare("insert into gpb_ihkprojektantrag(kursid,tnid,ampel) values(?,?,?)");
      $stmt->bind_param('iis',$kursid,$tnid,$ampel);
      $stmt->execute();
    }
  }
}
echo 'OK';
exit;
?>