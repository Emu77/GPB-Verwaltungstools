<?php
require_once '../db.php';
require_once '../tcpdf/tcpdf.php';

// Dieses Script wird von der yealink.exe Telefonsoftware aufgerufen, um einen Zugriff zu einem Interessentenkurs und das entsprechende PDF zu bekommen.
// siehe .../gpb/logins.php
// URL-Params:
// typ='IT' (im alten Moodle: 1) oder 'Medien' (im alten Moodle: 2) TODO "kaufmännische Berufe"
// port=67!Agt35P0saq2$625

$typ=isset($_GET['typ']) ? $_GET['typ'] : false;
if(empty($typ)) {
  exit;
}
$port=isset($_GET['port']) ? $_GET['port'] : false;
//if($port!='67!Agt35P0saq2$625') {
//  exit;
//}

// Interessenten-Kurs finden
$daten=(object)array(
  'category'=>'Beratung'
);
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=tests_finden&daten='.urlencode(json_encode($daten));
$ergebnis=json_decode(file_get_contents($url));
if(is_object($ergebnis) && isset($ergebnis->exception)) {
  $kursladenfehler='<div class="fehler">'.json_encode($ergebnis).'</div>';
  $ergebnis=null;
} else if(!is_object($ergebnis)) {
  $ergebnis=json_decode($ergebnis);
}
$coursesByMid=empty($ergebnis) ? array() : (array)$ergebnis;
$kurs=null;
foreach($coursesByMid as $coursemitisid=>$course) {
  if(strpos($course->shortname,'Interessentenkurs')===false) {
    continue;
  }
  if(strpos($course->shortname,$typ)!==false) {
    $kurs=$course;
    break;
  }
}
if(empty($kurs)) {
  echo 'In der Kategorie "Beratung" wurde kein Kurs mit "Interessentenkurs" und "'.$typ.'" gefunden.';
  exit;
}

// Nutzer reservieren, Passwort setzen
$moodleid=0;
$result=$db->query("select * from gpb_eignungstest_nutzer where id not in(select nutzerid from gpb_eignungstest_anmeldung) order by moodleid limit 1");
$nutzer=$result->fetch_object();
$result->free();
if(empty($nutzer)) {
  echo 'Keine freien Dummy-Nutzer mehr :-(';
  exit;
}
$passwort=''.random_int(1000,9999);
$daten=(object)array(
    'userid'=>$nutzer->moodleid
    ,'hash'=>password_hash($passwort,PASSWORD_DEFAULT)
    ,'idnumber'=>''
  );
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
$user=json_decode(file_get_contents($url));
if(is_object($user) && isset($user->exception)) {
  echo json_encode($user);
  exit;
}
$stmt=$db->prepare("update gpb_eignungstest_nutzer set passwort=? where id=? limit 1");
$stmt->bind_param('si',$passwort,$nutzer->id);
$stmt->execute();
$nutzer->passwort=$passwort;

// Nutzer in Interessentenkurs anmelden
$daten=(object)array(
    'rolename'=>'student',
    'courseid'=>$kurs->id,
    'userid'=>$nutzer->moodleid,
    'anmelden'=>true
  );
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_einschreibung&daten='.urlencode(json_encode($daten));
$ergebnis=json_decode(file_get_contents($url));
if($ergebnis!='ok' && $ergebnis!='"ok"') {
  echo json_encode($ergebnis);
  exit;
}
$stmt=$db->prepare("insert into gpb_eignungstest_anmeldung(nutzerid,coursemoodleid) values(?,?)");
$stmt->bind_param('ii',$nutzer->id,$kurs->id);
$stmt->execute();

// PDF generieren
$titel='GPB '.$kurs->shortname.' Zugangsdaten';

$vorlage=file_get_contents('vorlagen/EignungstestLogin.html');
$vorlage=substr($vorlage,strpos($vorlage,'<body>')+6);
$vorlage=substr($vorlage,0,strrpos($vorlage,'</body>'));

$pdf=new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor('GPBmbH');
$pdf->setTitle($titel);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(20,10,20,true);
$pdf->SetAutoPageBreak(false,10);

$datum=date('d.m.Y');
$ersetzungen=array(
  'DATUM'=>$datum
  ,'TITEL'=>$kurs->shortname
  ,'URL'=>$moodleurl.'course/view.php?id='.$kurs->id
  ,'NUTZERNAME'=>$nutzer->nutzername
  ,'PASSWORT'=>$nutzer->passwort
  ,'INTERESSENTENNAME'=>''
  ,'ANLEITUNG'=>''
);

$inhalt=$vorlage;
foreach($ersetzungen as $k=>$v) {
  $inhalt=str_replace($k,$v,$inhalt);
}

$pdf->AddPage();
$pdf->SetFont('DejaVuSans','',12);
$pdf->WriteHTML($inhalt,false,false,false,false,'C');

$pdf->Output($titel.'.pdf','D');
exit;
?>