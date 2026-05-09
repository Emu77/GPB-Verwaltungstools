<?php
require_once 'check_login.php';
$kursid=isset($_POST['kursid']) ? (int)$_POST['kursid'] : 0;
$tnid=isset($_POST['tnid']) ? (int)$_POST['tnid'] : 0;
$hinweise=isset($_POST['hinweise']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['hinweise'])) : false;
$stmt=$db->prepare("update gpb_ihkprojektantrag set hinweise=?,hinweise_geaendertam=now(),hinweise_geaendertvon=? where kursid=? and tnid=?");
$dozentname=$ich->vorname.' '.$ich->nachname;
$stmt->bind_param('ssii',$hinweise,$dozentname,$kursid,$tnid);
$stmt->execute();
if($db->affected_rows<=0 && !empty($hinweise)) {
  $stmt=$db->prepare("insert into gpb_ihkprojektantrag(kursid,tnid,hinweise,hinweise_geaendertam,hinweise_geaendertvon) values(?,?,?,now(),?)");
  $stmt->bind_param('iiss',$kursid,$tnid,$hinweise,$dozentname);
  $stmt->execute();
}
echo 'OK';
exit;
?>