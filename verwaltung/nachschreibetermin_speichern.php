<?php
require_once 'check_login.php';
$alt=isset($_POST['alt']) ? $_POST['alt'] : '';
$termin=isset($_POST['termin']) ? $_POST['termin'] : '';
if(empty($termin)) {
  header('Location:nachschreiber.php');
  exit;
}
$ort=isset($_POST['ort']) ? $_POST['ort'] : 'Neukölln';
if(empty($alt)) {
  $stmt=$db->prepare("insert into gpb_nachschreibetermin(termin,ort) values(?,?)");
  $stmt->bind_param('ss',$termin,$ort);
  $stmt->execute();
} else {
  $stmt=$db->prepare("update gpb_nachschreibetermin set termin=? where termin=? limit 1");
  $stmt->bind_param('ss',$termin,$alt);
  $stmt->execute();
}
header('Location:nachschreiber.php');
exit;
?>