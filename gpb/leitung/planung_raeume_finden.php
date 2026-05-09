<?php
require_once 'check_login.php';
$tuer=isset($_GET['tuer']) ? $_GET['tuer'] : false;
$raeume=array();
if(!empty($tuer)) {
  $tuer='%'.$tuer.'%';
  $stmt=$db->prepare("select * from gpb_raum where tuer like ?");
  $stmt->bind_param('s',$tuer);
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $raeume[]=$row;
  }
  $result->free();
}
echo 'OK';
echo json_encode($raeume);
exit;
?>