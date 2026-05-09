<?php
require_once 'check_login.php';
$interessent=isset($_SESSION['spez_interessent']) ? $_SESSION['spez_interessent'] : null;
if(empty($interessent) || empty($interessent->urlaub)) {
  header('Location:spezialisten.php');
  exit;
}
$beginn=isset($_GET['beginn']) ? $_GET['beginn'] : false;
$ende=isset($_GET['ende']) ? $_GET['ende'] : false;
if(empty($beginn) || empty($ende)) {
  header('Location:spezialisten.php');
  exit;
}
for($i=count($interessent->urlaub)-1;$i>=0;--$i) {
  $u=$interessent->urlaub[$i];
  if($u->beginn==$beginn && $u->ende==$ende) {
    array_splice($interessent->urlaub,$i,1);
    break;
  }
}

$stmt=$db->prepare("update gpb_basket set urlaub=? where mitisid=?");
$json=json_encode($interessent->urlaub);
$stmt->bind_param('si',$json,$interessent->mitisid);
$stmt->execute();

header('Location:spezialisten.php');
exit;
?>