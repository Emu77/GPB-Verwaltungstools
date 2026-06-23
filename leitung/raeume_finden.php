<?php
require_once 'check_login.php';
$ort=isset($_GET['ort']) ? $_GET['ort'] : false;
$raeume=array();
$stmt=$db->prepare("select * from gpb_raum ".(empty($ort) ? "" : "where ort=?")." order by ort,tuer");
if(!empty($ort)) {
  $stmt->bind_param('s',$ort);
}
$stmt->execute();
$result=$stmt->get_result();
while($row=$result->fetch_object()) {
  $raeume[]=$row;
}
$result->free();
echo json_encode($raeume);
exit;
?>