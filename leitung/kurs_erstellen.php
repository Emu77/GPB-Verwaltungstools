<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once '../Kurs.php';

$beginn=isset($_GET['beginn']) ? $_GET['beginn'] : false;
$dauer=isset($_GET['dauer']) ? (int)$_GET['dauer'] : 0;
$einheit=isset($_GET['einheit']) ? $_GET['einheit'] : false;
$was=isset($_GET['was']) ? $_GET['was'] : false;
$wasid=isset($_GET['wasid']) ? (int)$_GET['wasid'] : 0;
$raumid=$was=='raum' && $wasid>0 ? $wasid : null;
if(!empty($beginn) && $dauer>0 && ($einheit=='Wochen' || $einheit=='Tage')) {
  if($einheit=='Wochen') {
    $von=strtotime($beginn);
    $t=date('D',$von);
    if($t=='Sat' || $t=='Sun') {
      $von=strtotime('next monday',$von);
      $beginn=date('Y-m-d',$von);
    } else if($t!='Mon'){
      $von=strtotime('last monday',$von);
      $beginn=date('Y-m-d',$von);
    }
    $bis=strtotime('last friday',strtotime('+'.$dauer.' weeks',$von));
    $ende=date('Y-m-d',$bis);
  } else { //$einheit=='Tage'
    $von=strtotime($beginn);
    $bis=strtotime('+'.$dauer.' days',$von);
    $ende=date('Y-m-d',$bis);
  }
  //FIXME Kurstitel berechnen
  $titel=$beginn.' geplanter Kurs';
  $stmt=$db->prepare("insert into gpb_kurs(titel,beginn,ende,raumid,planungfarbe) values(?,?,?,?,'#ff0000')");
  $stmt->bind_param('sssi',$titel,$beginn,$ende,$raumid);
  $stmt->execute();
  $kursid=$db->insert_id;
  
  if($wasid>0) {
    if($was=='klasse') {
      $db->query("insert into gpb_kurs_klasse(kursid,klasseid) values(".$kursid.",".$wasid.")");
    } else if($was=='dozent') {
      $db->query("insert into gpb_kurs_dozent(kursid,dozentid) values(".$kursid.",".$wasid.")");
    }// else if($was=='raum') {
      //schon gemacht
    //}
  }
  
  $kurse=new Liste('Kurs',$db->prepare("select k.* from gpb_kurs_view k where k.id=".$kursid));
  Kurs::refsLaden($kurse);
  echo 'OK';
  echo json_encode($kurse->byId[$kursid]);
  exit;
}
echo 'Bitte Beginn, Dauer und Einheit auswählen';
exit;
?>