<?php
require_once 'check_login.php';
$termin=isset($_GET['termin']) ? $_GET['termin'] : '';
if(!empty($termin)) {
  $stmt=$db->prepare("delete from gpb_nachschreibetermin where termin=? limit 1");
  $stmt->bind_param('s',$termin);
  $stmt->execute();
}
header('Location:nachschreiber.php');
exit;
?>