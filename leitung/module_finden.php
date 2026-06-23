<?php
require_once 'check_login.php';
$titel=isset($_GET['titel']) ? $_GET['titel'] : false;
$berufids=isset($_GET['berufids']) ? json_decode($_GET['berufids']) : false;
$klassenids=isset($_GET['klassenids']) ?json_decode($_GET['klassenids']) : false;
$dozentenids=isset($_GET['dozentenids']) ? json_decode($_GET['dozentenids']) : false;
$module=array();
$stmt=$db->prepare("select m.*
  ,0 
  ".(empty($berufids) ? "" : "+(case when exists(select * from gpb_beruf_modul bm where bm.modulid=m.id and bm.berufid in(".implode(',',$berufids).")) then 10 else 0 end) ")."
  ".(empty($klassenids) ? "" : " +(case when exists(select * from gpb_kurs_klasse kk join gpb_kurs k on k.id=kk.kursid where kk.klasseid in(".implode(',',$klassenids).") and k.modulid=m.id) then 0 else 1 end)")."
  ".(empty($dozentenids) ? "" : "+(case when exists(select * from gpb_kurs_klasse kk join gpb_kurs k on k.id=kk.kursid join gpb_kurs_dozent kd on kd.kursid=kk.kursid where k.modulid=m.id and kd.dozentid in(".$dozentenids.")) then 1 else 0 end)")."
    as score
  ,".(!empty($klassenids) && count($klassenids)==1 ? "(select sum(ceil(datediff(k.ende,k.beginn)/7))
        from gpb_kurs k
        join gpb_kurs_klasse kk on kk.kursid=k.id and kk.klasseid=".$klassenids[0]."
        where k.modulid=m.id)" : "-1")." as schon
  from gpb_modul m
  ".(empty($titel) ? "" : "where m.kuerzel like ? or m.titel like ?")."
  order by score desc,m.titel limit 20");
if(!empty($titel)) {
  $titel='%'.$titel.'%';
  $stmt->bind_param('ss',$titel,$titel);
}
$stmt->execute();
$result=$stmt->get_result();
while($row=$result->fetch_object()) {
  $module[]=$row;
}
$result->free();
echo json_encode($module);
exit;
?>