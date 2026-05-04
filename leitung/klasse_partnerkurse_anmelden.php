<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once '../Klasse.php';
require_once '../Ferien.php';
require_once '../Kurs.php';

$redirect=!isset($_GET['redirect']) || $_GET['redirect']!='N';
$partnerid=isset($_GET['partnerid']) ? (int)$_GET['partnerid'] : 0;
if($partnerid<=0) {
  if($redirect) {
    header('Location:klassen.php');
    exit;
  } else {
    echo 'Bitte Partner-Klasse auswählen';
    exit;
  }
}
$klasse=Klasse::eineLaden(isset($_GET['klasseid']) ? (int)$_GET['klasseid'] : 0,'Klasse');
if(empty($klasse)) {
  if($redirect) {
    header('Location:klassen.php');
    exit;
  } else {
    echo 'Bitte Klasse auswählen';
    exit;
  }
}
if($klasse->von<=0) {
  if($redirect) {
    header('Location:klasse_kurse.php');
    exit;
  } else {
    echo 'Klasse hat kein Startdatum';
    exit;
  }
}

$kursids=array();
$values=array();
$kurse=array();
$result=$db->query("select id,moodleid
  from gpb_kurs 
  where id in(select kursid from gpb_kurs_klasse where klasseid=".$partnerid.")
    and beginn>='".$klasse->beginn."'
    and id not in(select kursid from gpb_kurs_klasse where klasseid=".$klasse->id.")");
while($row=$result->fetch_object()) {
  $kursids[]=$row->id;
  $values[]="(".$row->id.",".$klasse->id.")";
  if($row->moodleid>0) {
    $kurse[]=$row;
  }
}
$result->free();
if(!empty($values)) {
  $db->query("insert into gpb_kurs_klasse(kursid,klasseid) values ".implode(',',$values));
  if($klasse->moodleid>0 && !empty($kurse)) {
    foreach($kurse as $kurs) {
      $daten=(object)array(
        'courseid'=>$kurs->moodleid,
        'klassencourseids'=>array(),
        'ersetzen'=>true
      );
      $result=$db->query("select id,moodleid from gpb_klasse where moodleid>0 and id in(select klasseid from gpb_kurs_klasse where kursid=".$kurs->id.")");
      while($row=$result->fetch_object()) {
        $daten->klassencourseids[]=$row->moodleid;
      }
      $result->free();
      $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=meta_einschreibung&daten='.urlencode(json_encode($daten));
      $ergebnis=json_decode(file_get_contents($url));
      if($ergebnis!='ok' && $ergebnis!='"ok"') {
        if(!isset($fehler)) {
          $fehler=array();
        }
        $fehler[]=is_string($ergebnis) ? $ergebnis : json_encode($ergebnis);
      }
    }
  }
}

if($redirect) {
  header('Location:klasse_kurse.php?klasseid='.$klasse->id);
  exit;
}

$kurse=new Liste('Kurs',empty($kursids) ? null : $db->prepare("select k.* from gpb_kurs_view k where k.id in(".implode(',',$kursids).")"));
Kurs::refsLaden($kurse);
echo 'OK';
echo json_encode($kurse->alle);
exit;
?>