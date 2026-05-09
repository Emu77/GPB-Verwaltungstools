<?php
require_once 'check_login.php';
$name=isset($_GET['name']) ? $_GET['name'] : false;
$dozenten=array();
if(!empty($name)) {
  $name='%'.$name.'%';
  $stmt=$db->prepare("select * from (select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent) d where dozentenname like ? order by nachname,vorname limit 25");
  $stmt->bind_param('s',$name);
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $dozenten[]=$row;
  }
  $result->free();
}
echo 'OK';
echo json_encode($dozenten);
exit;
?>