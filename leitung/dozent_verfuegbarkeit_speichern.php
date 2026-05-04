<?php
require_once 'check_login.php';

$id=isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
$dozentid=isset($_POST['dozentid']) ? (int)$_POST['dozentid'] : (isset($_GET['dozentid']) ? (int)$_GET['dozentid'] : 0);
$verfuegbar=isset($_POST['verfuegbar']) ? $_POST['verfuegbar'] : (isset($_GET['verfuegbar']) ? $_GET['verfuegbar'] : false);
$beginn=isset($_POST['beginn']) ? $_POST['beginn'] : (isset($_GET['beginn']) ? $_GET['beginn'] : false);
$ende=isset($_POST['ende']) ? $_POST['ende'] : (isset($_GET['ende']) ? $_GET['ende'] : false);
$notiz=isset($_POST['notiz']) ? $_POST['notiz'] : (isset($_GET['notiz']) ? $_GET['notiz'] : '');
if($dozentid>0 && !empty($verfuegbar) && !empty($beginn) && !empty($ende)) {
  if($id>0) {
    $stmt=$db->prepare("update gpb_dozent_verfuegbarkeit set dozentid=?,verfuegbar=?,beginn=?,ende=?,notiz=? where id=? limit 1");
    $stmt->bind_param('issssi',$dozentid,$verfuegbar,$beginn,$ende,$notiz,$id);
    $stmt->execute();
  } else {
    $stmt=$db->prepare("insert into gpb_dozent_verfuegbarkeit(dozentid,verfuegbar,beginn,ende,notiz) values(?,?,?,?,?)");
    $stmt->bind_param('issss',$dozentid,$verfuegbar,$beginn,$ende,$notiz);
    $stmt->execute();
    $id=$db->insert_id;
  }
}

$redirect=isset($_POST['redirect']) ? $_POST['redirect'] : false;
if($redirect) {
  header('Location:'.$redirect.'?dozentid='.$dozentid);
  exit;
}
$kw=array();
$von=strtotime($beginn);
$bis=strtotime($ende);
for($t=$von;$t<=$bis;$t=strtotime('+1 week',$t)) {
  $kw[]=date('Y-W',$t);
}
$obj=array(
  'id'=>$id
  ,'dozentid'=>$dozentid
  ,'verfuegbar'=>$verfuegbar
  ,'beginn'=>$beginn
  ,'ende'=>$ende
  ,'notiz'=>$notiz
  ,'kw'=>$kw
);
echo 'OK';
echo json_encode($obj);
exit;
?>