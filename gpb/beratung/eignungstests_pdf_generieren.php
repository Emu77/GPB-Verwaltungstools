<?php
require_once 'check_login.php';
require_once '../tcpdf/tcpdf.php';

$terminid=isset($_GET['terminid']) ? (int)$_GET['terminid'] : -1;
$anmeldungid=isset($_GET['anmeldungid']) ? (int)$_GET['anmeldungid'] : -1;

$anmeldungen=array();
$ersteanmeldung=null;
$result=$db->query("select *
  from gpb_eignungstest_anmeldung a
  join gpb_eignungstest_nutzer n on n.id=a.nutzerid
  left outer join gpb_eignungstest_termin t on t.id=a.terminid
  where a.terminid=".$terminid." or a.id=".$anmeldungid);
while($row=$result->fetch_object()) {
  $row->timestamp=strtotime($row->wann);
  $anmeldungen[]=$row;
  if(empty($ersteanmeldung)) {
    $ersteanmeldung=$row;
  }
}
$result->free();

if(empty($anmeldungen)) {
  header('Location:eignungstests.php');
  exit;
}

$daten=(object)array(
  'category'=>'Beratung'
);
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=tests_finden&daten='.urlencode(json_encode($daten));
$ergebnis=json_decode(file_get_contents($url));
if(is_object($ergebnis) && isset($ergebnis->exception)) {
  $ergebnis=null;
} else if(!is_object($ergebnis)) {
  $ergebnis=json_decode($ergebnis);
}
$coursesByMid=empty($ergebnis) ? array() : (array)$ergebnis;
$quizzesByCmid=array();
foreach($coursesByMid as $mid=>$course) {
  foreach($course->quizzes as $quiz) {
    $quizzesByCmid[$quiz->cmid]=$quiz;
  }
}

if($terminid>0) {
  $titel='GPB Eignungstests '.date('d.m.Y H:i',$ersteanmeldung->timestamp).' '.$ersteanmeldung->ort;
} else if($ersteanmeldung->testcmid>0) {
  $quiz=$quizzesByCmid[$ersteanmeldung->testcmid];
  $titel=$quiz->titel.' Zugangsdaten';
} else {
  $course=$coursesByMid[$ersteanmeldung->coursemoodleid];
  $titel='GPB '.$course->shortname.' Zugangsdaten';
}

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
foreach($anmeldungen as $an) {
  $datum=$an->timestamp>0 ? date('d.m.Y',$an->timestamp) : date('d.m.Y');
  $course=$an->coursemoodleid>0 ? $coursesByMid[$an->coursemoodleid] : null;
  $quiz=$an->testcmid>0 ? $quizzesByCmid[$an->testcmid] : null;
  $ersetzungen=array(
    'DATUM'=>$datum
    ,'TITEL'=>(empty($quiz) ? (empty($course) ? '' : $course->shortname) : $quiz->titel)
    ,'URL'=>(empty($quiz) ? (empty($course) ? '' : $moodleurl.'course/view.php?id='.$course->id) : $moodleurl.'mod/quiz/view.php?id='.$quiz->cmid)
    ,'NUTZERNAME'=>$an->nutzername
    ,'PASSWORT'=>$an->passwort
    ,'INTERESSENTENNAME'=>$an->vorname.' '.$an->nachname
    ,'ANLEITUNG'=>(empty($an->ort) ? '' : "Bitte loggen Sie sich nach Beendigung des Tests aus Moodle aus und fahren Sie den Rechner herunter. Geben Sie diesen Zettel bei dem Mitarbeiter/der Mitarbeiterin des GPB-Teams ab.")
  );
  
  $inhalt=$vorlage;
  foreach($ersetzungen as $k=>$v) {
    $inhalt=str_replace($k,$v,$inhalt);
  }
  
  $pdf->AddPage();
  $pdf->SetFont('DejaVuSans','',12);
  $pdf->WriteHTML($inhalt,false,false,false,false,'C');
}
$pdf->Output($titel.'.pdf','D');
exit;
?>