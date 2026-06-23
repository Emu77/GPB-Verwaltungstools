<?php
require_once 'check_login.php';
$kursid=isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0;
$kursmoodleid=isset($_GET['kursmoodleid']) ? (int)$_GET['kursmoodleid'] : 0;
$beginn=isset($_GET['beginn']) ? $_GET['beginn'] : false;
$dauer=isset($_GET['dauer']) ? (int)$_GET['dauer'] : 0;
$einheit=isset($_GET['einheit']) ? $_GET['einheit'] : false;
if(!empty($kursid) && !empty($beginn) && $dauer>0 && ($einheit=='Wochen' || $einheit=='Tage')) {
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
  $stmt=$db->prepare("update gpb_kurs set beginn=?,ende=? where id=?");
  $stmt->bind_param('ssi',$beginn,$ende,$kursid);
  $stmt->execute();
  $kw=array();
  for($wann=$von;$wann<=$bis;$wann=strtotime('+1 week',$wann)) {
    $kw[]=date('Y-W',$wann);
  }
  $zeitraum=array(
    'beginn'=>$beginn,
    'ende'=>$ende,
    'von'=>$von,
    'bis'=>$bis,
    'kw'=>$kw,
    'fehler'=>null
  );
  if($kursmoodleid>0) {
    $result=$db->query("select * from gpb_kurs where id=".$kursid);
    $kurs=$result->fetch_object();
    $result->free();
    $daten=(object)array(
      'courseid'=>$kurs->moodleid,
      'kategorie'=>'ILKurse',
      'titel'=>$kurs->titel,
      'kursid'=>$kurs->id,
      'beginn'=>$kurs->beginn,
      'ende'=>$kurs->ende,
      'keinenoten'=>($kurs->notenstatus=='keine' ? 'J' : 'N')
    );
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=kurs_speichern&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if(is_object($ergebnis) && isset($ergebnis->exception)) {
      $zeitraum->fehler=$ergebnis;
    }
  }
  echo 'OK';
  echo json_encode($zeitraum);
  exit;
}
echo 'Bitte Kurs, Beginn, Dauer und Einheit auswählen';
exit;
?>