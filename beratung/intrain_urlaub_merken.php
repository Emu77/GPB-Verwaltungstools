<?php
require_once 'check_login.php';
$interessent=isset($_SESSION['intrain_interessent']) ? $_SESSION['intrain_interessent'] : null;
if(empty($interessent)) {
  require_once 'intrain_seite.php';
  exit;
}
$beginn=isset($_POST['beginn']) ? $_POST['beginn'] : false;
$ende=isset($_POST['ende']) ? $_POST['ende'] : false;
if(empty($beginn)) {
  require_once 'intrain_seite.php';
  exit;
}
if(empty($ende)) {
  $ende=$beginn;
}
$interessent->urlaub[]=(object)array('beginn'=>$beginn,'ende'=>$ende);

$stmt=$db->prepare("update gpb_basket set urlaub=? where mitisid=?");
$json=json_encode($interessent->urlaub);
$stmt->bind_param('si',$json,$interessent->mitisid);
$stmt->execute();

require_once 'intrain_seite.php';
exit;
?>