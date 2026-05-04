<?php
require_once 'check_login.php';
require_once '../tcpdf/tcpdf.php';

$attempt=isset($_GET['attempt']) ? (int)$_GET['attempt'] : 0;
$daten=(object)array(
  'attemptid'=>$attempt
);
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=abgaben&daten='.urlencode(json_encode($daten));
$ergebnis=json_decode(file_get_contents($url));
if(is_object($ergebnis) && isset($ergebnis->exception)) {
  echo '<div class="fehler">'.json_encode($ergebnis).'</div>';
  $ergebnis=null;
  exit;
} else if(!is_object($ergebnis)) {
  $ergebnis=json_decode($ergebnis);
}

//echo json_encode($ich,JSON_PRETTY_PRINT);
//exit;

//echo json_encode($ergebnis,JSON_PRETTY_PRINT);
//exit;

$ergebnis->attempt->timestart=(int)$ergebnis->attempt->timestart;
$ergebnis->attempt->timefinish=(int)$ergebnis->attempt->timefinish;
$ergebnis->questions=(array)$ergebnis->questions;
$ergebnis->questionattempts=(array)$ergebnis->questionattempts;
$ergebnis->steps=(array)$ergebnis->steps;

foreach($ergebnis->steps as $id=>$step) {
  $step->fraction=(float)$step->fraction;
  $qa=$ergebnis->questionattempts[$step->questionattemptid];
  $qa->step=$step;
}

$vorname=null;
$nachname=null;
$gesamt=(object)array(
  'titel'=>'Gesamtbewertung'
  ,'qasBySlot'=>array()
  ,'maxmark'=>0.0
  ,'mark'=>0.0
);
$kapitel=array($gesamt);
$aktuelles_kapitel=null;
foreach($ergebnis->questionattempts as $id=>$qa) {
  //wir hoffen, dass die 1. Frage immer Vorname + Nachname bringt, in dieser Reihenfolge
  if($vorname===null) {
    $data=explode(';',$qa->responsesummary);
    if(count($data)>=2) {
      $i=strpos($data[0],':');
      $vorname=$i===false ? $data[0] : trim(substr($data[0],$i+1));
      $i=strpos($data[1],':');
      $nachname=$i===false ? $data[1] : trim(substr($data[1],$i+1));
    }
    continue;
  }
  if($qa->behaviour=='informationitem') {
    $q=$ergebnis->questions[$qa->questionid];
    $aktuelles_kapitel=(object)array(
      'titel'=>$q->questiontext
      ,'qasBySlot'=>array()
      ,'maxmark'=>0.0
      ,'mark'=>0.0
    );
    $kapitel[]=$aktuelles_kapitel;
    continue;
  }
  // nur 1 Attempt pro Slot beibehalten, und zwar den letzten Attempt mit Step
  if(isset($qa->step)) {
    if(empty($aktuelles_kapitel)) { // Abschnittbeschreibung fehlt
      $aktuelles_kapitel=(object)array(
        'titel'=>''
        ,'qasBySlot'=>array()
        ,'maxmark'=>0.0
        ,'mark'=>0.0
      );
      $kapitel[]=$aktuelles_kapitel;
    }
    $aktuelles_kapitel->qasBySlot[$qa->slot]=$qa;
  }
}
foreach($kapitel as $k) {
  foreach($k->qasBySlot as $slot=>$qa) {
    $k->maxmark+=$qa->maxmark;
    $k->mark+=$qa->step->fraction*$qa->maxmark;
    $gesamt->maxmark+=$qa->maxmark;
    $gesamt->mark+=$qa->step->fraction*$qa->maxmark;
  }
}

$titel='Ergebnis '.$ergebnis->quiz->name.' '.$vorname.' '.$nachname;

$vorlage=file_get_contents('vorlagen/EignungstestErgebnis.html');
$vorlage=substr($vorlage,strpos($vorlage,'<body>')+6);
$vorlage=substr($vorlage,0,strrpos($vorlage,'</body>'));
$i=strpos($vorlage,'<tr class="testkapitel">');
$j=strpos($vorlage,'</tr>',$i)+5;
$vorlageanfang=substr($vorlage,0,$i);
$vorlagekapitel=substr($vorlage,$i,$j-$i);
$vorlageende=substr($vorlage,$j);

$pdf=new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor('GPBmbH');
$pdf->setTitle($titel);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(20,10,20,true);
$pdf->SetAutoPageBreak(false,10);

$ersetzungen=array(
  'QUIZTITEL'=>$ergebnis->quiz->name
  ,'INTERESSENTVORNAME'=>(empty($vorname) ? ' &nbsp; ' : $vorname)
  ,'INTERESSENTNACHNAME'=>(empty($nachname) ? ' &nbsp; ' : $nachname)
  ,'QUIZBEGINN'=>date('d.m.Y H:i',$ergebnis->attempt->timestart)
  ,'QUIZENDE'=>date('d.m.Y H:i',$ergebnis->attempt->timefinish)
  ,'QUIZDAUER'=>round(($ergebnis->attempt->timefinish-$ergebnis->attempt->timestart)/60.0)
);
$inhalt=$vorlageanfang;
foreach($ersetzungen as $k=>$v) {
  $inhalt=str_replace($k,$v,$inhalt);
}

foreach($kapitel as $k) {
  if($k->maxmark<=0.0) continue;
  $ersetzungen=array(
    'KAPITELTITEL'=>$k->titel
    ,'KAPITELERGEBNIS'=>round(100.0*$k->mark/$k->maxmark)
  );
  $kapinhalt=$vorlagekapitel;
  foreach($ersetzungen as $k=>$v) {
    $kapinhalt=str_replace($k,$v,$kapinhalt);
  }
  $inhalt.=$kapinhalt;
  if(count($kapitel)<=2) { //nur Gesamt und 1 Kapitel: Kapitel nicht wiederholen
    break;
  }
}

$ersetzungen=array(
  'NICHTBESTANDENCHECKBOX'=>($gesamt->mark>=0.5*$gesamt->maxmark ? '&#9744;' : '&#9745;')
  ,'BESTANDENCHECKBOX'=>($gesamt->mark>=0.5*$gesamt->maxmark ? '&#9745;' : '&#9744;')
  ,'DATUM'=>date('d.m.Y')
  ,'BERATERSIGNATUR'=>(empty($ich->signaturbild) || !file_exists('signaturbilder/'.$ich->signaturbild) ? '' : '<img src="signaturbilder/'.$ich->signaturbild.'" width="175" />')
);
foreach($ersetzungen as $k=>$v) {
  $vorlageende=str_replace($k,$v,$vorlageende);
}
$inhalt.=$vorlageende;

$pdf->AddPage();
$pdf->SetFont('DejaVuSans','',12);
$pdf->WriteHTML($inhalt,false,false,false,false,'C');

$pdf->Output($titel.'.pdf','D');
exit;
?>