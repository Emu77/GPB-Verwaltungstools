<?php
require_once 'check_login.php';
$zertifid=isset($_GET['zertif']) ? (int)$_GET['zertif'] : 0;
$spalte=isset($_GET['spalte']) ? str_replace('`','',$_GET['spalte']) : false;
$neu=isset($_GET['neu']) ? $_GET['neu'] : '';
if($zertifid>0 && !empty($spalte)) {
  if(empty($neu)) {
    $t=0;
  } else {
    $neu=explode('.',$neu);
    $tag=isset($neu[0]) ? (int)$neu[0] : 0;
    $monat=isset($neu[1]) ? (int)$neu[1] : 0;
    $jahr=isset($neu[2]) ? (int)$neu[2] : 0;
    if($tag<=0 || $monat<=0 || $jahr<=0) {
      $t=0;
    } else {
      if($jahr<100) $jahr+=2000;
      $t=strtotime(''.$jahr.'-'.($monat<10 ? '0' : '').$monat.'-'.($tag<10 ? '0' : '').$tag);
    }
  }
  $dbdatum=empty($t) ? null : date('Y-m-d',$t);
  $stmt=$db->prepare("update gpb_intrainzertif set `".$spalte."`=? where id=?");
  $stmt->bind_param('si',$dbdatum,$zertifid);
  $stmt->execute();
  $data=array(
    $spalte=>$dbdatum,
    str_replace('beginn','von',str_replace('ende','bis',$spalte))=>$t,
    'anzeige'=>empty($t) ? '' : date('d.m.Y',$t)
  );
  echo 'OK';
  echo json_encode($data);
  exit;
}
echo 'NOK';
exit;
?>