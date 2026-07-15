<?php
require_once 'check_login.php';
$titel=isset($_GET['titel']) ? $_GET['titel'] : false;
$module=array();
if(!empty($titel)) {
  $titel='%'.$titel.'%';
  $stmt=$db->prepare("select m.*
      ,(select count(*) from gpb_beruf_modul bm where bm.modulid=m.id) as anzahlBerufe
    from gpb_modul m
    where m.titel like ? or m.kuerzel like ? 
    order by m.kuerzel,m.titel limit 25");
  if(empty($ich->ort)) {
      $stmt->bind_param('ss',$titel,$titel);
  } else {
      $stmt->bind_param('sss',$ich->ort,$titel,$titel);
  }
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $module[]=$row;
  }
  $result->free();
}
echo 'OK';
echo json_encode($module);
exit;
?>