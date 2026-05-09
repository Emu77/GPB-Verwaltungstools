<?php
require_once 'check_login.php';
$titel=isset($_GET['titel']) ? $_GET['titel'] : false;
$klassenids=isset($_GET['klassenids']) ? $_GET['klassenids'] : false;
$berufids=null;
if(!empty($klassenids)) {
  $berufids=array();
  $result=$db->query("select id from gpb_beruf where id in(select berufid from gpb_klasse where id in(".$klassenids."))");
  while($row=$result->fetch_object()) {
    $berufids[]=$row->id;
  }
  $result->free();
}
$module=array();
if(!empty($titel)) {
  $titel='%'.$titel.'%';
  $stmt=$db->prepare("select m.*
    ,0"
    .(empty($ich->ort) ? "" : "+(case when exists(select * from gpb_beruf_modul bm 
        join gpb_beruf b on b.id=bm.berufid
        join gpb_berufsfamilie f on f.id=b.familieid
        where bm.modulid=m.id and f.ort=?) then 10 else 0 end)")
    .(empty($berufids) ? "" : "+(case when exists(select * from gpb_beruf_modul bm2 
      where bm2.modulid=m.id and bm2.berufid in(".implode(',',$berufids).")) then 20 else 0 end)")
    .(empty($klassenids) ? "" : "+(case when exists(select * from gpb_kurs_klasse kk
      join gpb_kurs k on k.id=kk.klasseid
      where k.modulid=m.id and kk.klasseid in(".$klassenids.")) then -5 else 0 end)")
    ." as score from gpb_modul m
    where m.titel like ? or m.kuerzel like ? 
    order by score desc,m.kuerzel,m.titel limit 25");
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