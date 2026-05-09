<?php
require_once 'check_login.php';

$kursid=isset($_POST['kursid']) ? (int)$_POST['kursid'] : 0;
if(empty($kursid)) {
  header('Location:kurse.php');
  exit;
}

$id=isset($_POST['id']) ? (int)$_POST['id'] : 0;
$was=isset($_POST['was']) ? $_POST['was'] : '';
$beginn=isset($_POST['beginn']) ? $_POST['beginn'] : '';
if(empty($beginn)) {
  $beginn=null;
}
$beginn_uhrzeit=isset($_POST['beginn_uhrzeit']) ? $_POST['beginn_uhrzeit'] : '';
if(empty($beginn_uhrzeit)) {
  $beginn_uhrzeit=null;
}
$ende=isset($_POST['ende']) ? $_POST['ende'] : '';
if(empty($ende)) {
  $ende=null;
}
$ende_uhrzeit=isset($_POST['ende_uhrzeit']) ? $_POST['ende_uhrzeit'] : '';
if(empty($ende_uhrzeit)) {
  $ende_uhrzeit=null;
}
$betrifftAP1=isset($_POST['betrifftAP1']) && $_POST['betrifftAP1']!='N' ? 1 : 0;
$betrifftAP2=isset($_POST['betrifftAP2']) && $_POST['betrifftAP2']!='N' ? 1 : 0;
$betrifftMuendliche=isset($_POST['betrifftMuendliche']) && $_POST['betrifftMuendliche']!='N' ? 1 : 0;
if($id>0) {
  $stmt=$db->prepare("update gpb_pruefungsvorbereitung_termin set was=?,beginn=?,beginn_uhrzeit=?,ende=?,ende_uhrzeit=?,betrifftAP1=?,betrifftAP2=?,betrifftMuendliche=? where id=? limit 1");
  $stmt->bind_param('sssssiiii',$was,$beginn,$beginn_uhrzeit,$ende,$ende_uhrzeit,$betrifftAP1,$betrifftAP2,$betrifftMuendliche,$id);
  $stmt->execute();
} else {
  $stmt=$db->prepare("insert into gpb_pruefungsvorbereitung_termin(kursid,was,beginn,beginn_uhrzeit,ende,ende_uhrzeit,betrifftAP1,betrifftAP2,betrifftMuendliche) values(?,?,?,?,?,?,?,?,?)");
  $stmt->bind_param('isssssiii',$kursid,$was,$beginn,$beginn_uhrzeit,$ende,$ende_uhrzeit,$betrifftAP1,$betrifftAP2,$betrifftMuendliche);
  $stmt->execute();
  $id=$db->insert_id;
}

header('Location:kurs_pv.php?kursid='.$kursid);
exit;
?>