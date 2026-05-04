<?php
require_once 'check_login.php';
$fragenid=isset($_GET['fragenid']) ? (int)$_GET['fragenid'] : 0;
$antwort=isset($_GET['antwort']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_GET['antwort'])) : '';
if($fragenid>0) {
  $db->query("delete from rm_klausur where tnid=".$ich->id." and fragenid=".$fragenid);
  $stmt=$db->prepare("insert into rm_klausur(tnid,fragenid,antwort) values(?,?,?)");
  $stmt->bind_param('iis',$ich->id,$fragenid,$antwort);
  $stmt->execute();
}
echo 'OK';
exit;
?>