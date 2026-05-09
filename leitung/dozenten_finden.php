<?php
require_once 'check_login.php';
$name=isset($_GET['name']) ? $_GET['name'] : false;
$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;
$dozenten=array();
if(empty($name)) {
  $stmt=$db->prepare("select d.*
      ,(select count(*) from gpb_kurs_dozent kd where kd.dozentid=d.id and kd.kursid in(select k.id from gpb_kurs k where k.modulid=?)) as male
    from gpb_dozent d
    where exists(select * from gpb_kurs_dozent kd where kd.dozentid=d.id and kd.kursid in(select k.id from gpb_kurs k where k.modulid=?))
    order by male desc,d.nachname,d.vorname limit 20");
  $stmt->bind_param('ii',$modulid,$modulid);
} else {
  $name='%'.$name.'%';
  $stmt=$db->prepare("select d.*
      ,(select count(*) from gpb_kurs_dozent kd where kd.dozentid=d.id and kd.kursid in(select k.id from gpb_kurs k where k.modulid=?)) as male
    from gpb_dozent d
    where d.vorname like ? or d.nachname like ?
    order by male desc,d.nachname,d.vorname limit 20");
  $stmt->bind_param('iss',$modulid,$name,$name);
}
$stmt->execute();
$result=$stmt->get_result();
while($row=$result->fetch_object()) {
  $dozenten[]=$row;
}
$result->free();
echo json_encode($dozenten);
exit;
?>