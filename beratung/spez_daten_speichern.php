<?php
require_once 'check_login.php';
$tabelle=isset($_GET['tabelle']) ? $_GET['tabelle'] : false;
if($tabelle!='gpb_spezausbildung' && $tabelle!='gpb_spezmodul') {
  echo 'Unbekannte Tabelle '.$tabelle;
  exit;
}
$spalte=isset($_GET['spalte']) ? $_GET['spalte'] : '';
$id=isset($_GET['id']) ? $_GET['id'] : 0;
$neu=isset($_GET['neu']) ? $_GET['neu'] : '';

$ext=strrchr($spalte,'_');
if($ext=='_von' || $ext=='_bis') {
  $spalte=substr($spalte,0,strlen($spalte)-strlen($ext)).($ext=='_von' ? '_beginn' : '_ende');
  if(empty($neu)) {
    $neu=null;
  } else {
    $neu=implode('-',array_reverse(explode('.',$neu)));
  }
}

$stmt=$db->prepare("update ".$tabelle." set ".$spalte."=? where id=?");
$stmt->bind_param('si',$neu,$id);
$stmt->execute();

$ausbildungid=isset($_GET['ausbildungid']) ? (int)$_GET['ausbildungid'] : 0;
header('Location:spez_ausbildungen.php'.($ausbildungid>0 ? '#ausbildung_'.$ausbildungid : ''));
exit;
?>