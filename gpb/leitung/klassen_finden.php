<?php
require_once 'check_login.php';
$bezeichnung=isset($_GET['bezeichnung']) ? $_GET['bezeichnung'] : false;
$partnerid=isset($_GET['partnerid']) ? (int)$_GET['partnerid'] : 0;
$berufid=isset($_GET['berufid']) ? (int)$_GET['berufid'] : 0;
$partnerab=isset($_GET['partnerab']) ? $_GET['partnerab'] : false;
$klassen=array();
if(!empty($bezeichnung)) {
  $bezeichnung='%'.$bezeichnung.'%';
  $stmt=$db->prepare("select * from gpb_klasse_view where bezeichnung like ? order by bezeichnung limit 20");
  $stmt->bind_param('s',$bezeichnung);
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $klassen[]=$row;
  }
  $result->free();
} else if($partnerid>0 && $berufid>0) {
  if(empty($partnerab)) {
    $stmt=$db->prepare("select * from gpb_klasse_view where id<>? and berufid=? order by beginn desc,bezeichnung limit 20");
    $stmt->bind_param('ii',$partnerid,$berufid);
  } else {
    $stmt=$db->prepare("select * from gpb_klasse_view where id<>? and berufid=? and (ende='0000-00-00' or ende is null or ende>=?) order by bezeichnung limit 20");
    $stmt->bind_param('iis',$partnerid,$berufid,$partnerab);
  }
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $klassen[]=$row;
  }
  $result->free();
}
echo json_encode($klassen);
exit;
?>