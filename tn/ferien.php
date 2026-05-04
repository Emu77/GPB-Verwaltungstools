<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once '../Tn.php';
require_once 'TnFerien.php';

$tn=Tn::einenLaden($ich->id,'Tn');
if(empty($tn)) {
  header('Location:kurse.php');
  exit;
}
$tn->ferienLaden('TnFerien');

require_once 'TnSeite.php';
$seite=TnSeite::$menueByUrl['ferien.php'];
$seite->anfangGenerieren();
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
Ferien::makeHeaderTr();
foreach($tn->ferien as $fer) {
  $fer->makeTr();
}
?>
</table>
<?php
$seite->endeGenerieren();
?>