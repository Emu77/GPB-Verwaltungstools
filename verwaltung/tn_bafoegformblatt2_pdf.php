<?php
require_once 'check_login.php';
require_once 'VerwaltungTn.php';

$tn=VerwaltungTn::einenLaden(isset($_POST['tnid']) ? (int)$_POST['tnid'] : 0,'VerwaltungTn');
if(empty($tn) || empty($tn->EABmassnahme)) {
  header('Location:tn.php');
  exit;
}

$schuljahr=isset($_POST['schuljahr']) ? (int)$_POST['schuljahr'] : 0;
if($schuljahr<2000) {
  $schuljahr=(int)date('Y')+(date('m')>='08' ? 0 : -1);
}

if($tn->berufid>0){
  $result=$db->query("select b.*
      ,bf.ort
    from gpb_beruf b
    left outer join gpb_berufsfamilie bf on bf.id=b.familieid
    where b.id=".$tn->berufid." limit 1");
  $beruf=$result->fetch_object();
  $result->free();
} else {
  $beruf=false;
}

function printText($txt,$x,$y) {
  global $pdf;
  $pdf->SetXY($x,$y);
  $pdf->Cell(0,0,$txt);
}
function printDatum($dbdatum,$x,$y) {
  global $pdf;
  $dat=date('dmY',strtotime($dbdatum));
  for($i=strlen($dat)-1;$i>=0;--$i) {
    $pdf->SetXY($x+4*$i,$y);
    $pdf->Cell(0,0,substr($dat,$i,1));
  }
}
function ankreuzen($x,$y) {
  global $pdf;
  $pdf->SetXY($x,$y);
  $pdf->Cell(0,0,'X');
}

require_once '../tcpdf/tcpdf.php';
require_once '../fpdi/src/autoload.php';
$mm2pt=2.83465;

use setasign\Fpdi\Tcpdf\Fpdi;
$pdf=new Fpdi();
$pdf->SetAutoPageBreak(false);
$pdf->setSourceFile('vorlagen/BafoegFormblatt2.pdf');

$pdf->AddPage();
$seite=$pdf->importPage(1);
$pdf->useTemplate($seite,0,0,210);

$pdf->SetFont('DejaVuSans','',$mm2pt*3);

printText($tn->nachname,27,54);
printText($tn->vorname,104,54);
printDatum($tn->geburtsdatum,20,66);
printText($tn->geburtsort,104,66);
if($beruf && $beruf->ort=='Neukölln') {
  printText('GPB Berlin-Neukölln',95,91);
  printText('Juliusstr. 2, 12051 Berlin',95,99.5);
  printText('(Schulnummer: 01E25)',95,103.5);
} else {
  printText('GPB Berlin'.(empty($beruf) || empty($beruf->ort) ? '' : '-'.$beruf->ort),95,91);
  printText('Beuthstr. 8, 10117 Berlin',95,99.5);
  printText('(Schulnummer: 01E25)',95,103.5);
}
//TODO Bafög-Amt
printText($schuljahr-2000,61.5,135);
printText($schuljahr-2000+1,77,135);
ankreuzen(96,166.5); // Checkbox "einen berufsqualifizierenden Abschluss vermittelt"
if($beruf) {
  printText($beruf->bezeichnung,20,193);
}
ankreuzen(96,208); // Checkbox "Klasse/Jahrgang wird wiederholt: nein"
printDatum(empty($tn->EABmassnahme->einstieg) || $tn->EABmassnahme->einstieg=='0000-00-00' ? $tn->EABmassnahme->beginn : $tn->EABmassnahme->einstieg,96,222);
printDatum(empty($tn->EABmassnahme->ausstieg) || $tn->EABmassnahme->ausstieg=='0000-00-00' ? $tn->EABmassnahme->ende : $tn->EABmassnahme->ausstieg,96,232);
printText('IHK / staatlich anerkannt',131,222);
ankreuzen(116,248.5); // Checkbox "Vollzeit-Ausbildung: ja"
ankreuzen(96,257.5); // Checkbox "Teile der Ausbildung im Ausland: nein"

$pdf->AddPage();
$seite=$pdf->importPage(2);
$pdf->useTemplate($seite,0,0,210);
printText($tn->vorname.' '.$tn->nachname,42,10);
printText('keine',148,45.5); //Kostenfreie Monate
printText('A',132,265); //Ausgefüllter Abschnitt
printText(date('d.m.Y'),23,276); //Ausgefüllter Abschnitt

$pdf->Output();
exit;
?>