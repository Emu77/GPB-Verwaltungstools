<?php
require_once 'check_login.php';
$ort=isset($_GET['ort']) ? $_GET['ort'] : false;
if($ort!='Mitte' && $ort!='Neukölln') {
  $ort=false;
}
$bezeichnung=isset($_GET['bezeichnung']) ? $_GET['bezeichnung'] : false;
$klassen=array();
if(!empty($bezeichnung)) {
  $bezeichnung='%'.$bezeichnung.'%';
  $stmt=$db->prepare("select * from gpb_klasse_view where bezeichnung like ? ".($ort ? " and ort=? " : "")." order by bezeichnung desc limit 25");
  if($ort) {
    $stmt->bind_param('ss',$bezeichnung,$ort);
  } else {
    $stmt->bind_param('s',$bezeichnung);
  }
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $klassen[]=$row;
  }
  $result->free();
}
echo 'OK';
echo json_encode($klassen);
exit;
?>