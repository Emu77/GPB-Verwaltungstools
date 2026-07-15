<?php
require_once 'check_login.php';
$bezeichnung=isset($_GET['bezeichnung']) ? $_GET['bezeichnung'] : false;
$klassen=array();
if(!empty($bezeichnung)) {
  $bezeichnung='%'.$bezeichnung.'%';
  $stmt=$db->prepare("select * from gpb_klasse_view where bezeichnung like ?");
  $stmt->bind_param('s',$bezeichnung);
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