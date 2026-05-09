<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$beginn=isset($_GET['beginn']) ? $_GET['beginn'] : '';
$ende=isset($_GET['ende']) ? $_GET['ende'] : '';
$beschaeftigt=array();
if(!empty($beginn) && !empty($ende)) {
  $stmt=$db->prepare("select kk.*,k.titel,a.bezeichnung
    from gpb_kurs_klasse kk
    join gpb_kurs k on k.id=kk.kursid
    left outer join gpb_klasse a on a.id=kk.klasseid
    where k.beginn<=? and k.ende>=? and k.id<>?");
  $stmt->bind_param('ssi',$ende,$beginn,$kursid);
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $beschaeftigt[$row->klasseid]=$row;
  }
  $result->free();
}
echo 'OK';
echo json_encode($beschaeftigt);
exit;
?>