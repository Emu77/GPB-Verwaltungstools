<?php
require_once 'check_login.php';
require_once '../tcpdf/tcpdf.php';
require_once 'DozentKurs.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'DozentKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}

$art=isset($_GET['art']) ? $_GET['art'] : 'Klausur';

$mm2pt=2.83465;
$x0=20; 
$x1=79; 
$x3=192;
$h0=8;
$x2=40;
$y2=240;
$h2=6;

$pdf=new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor('GPBmbH');
$pdf->setTitle($kurs->titel.' '.$art.'-Deckblatt');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->AddPage();

$pdf->Image('../GPBLogo.png',154,15,40);

$pdf->setFont('DejaVuSans','B',20);
$pdf->SetXY($x0,54);
$pdf->Cell($x3-$x0,10,$art,0,1,'C');

$y=70;
$pdf->setFont('DejaVuSans','B',12);
$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,$h0,'Kurs:',0,0,'L');
$t=$kurs->titel;
while($t) {
  $tt=$t;
  while($pdf->GetStringWidth($tt)>$x3-$x1) {
    $i=strrpos($tt,' ');
    if($i===false) {
      break;
    }
    $tt=trim(substr($tt,0,$i));
  }
  $pdf->Cell($x3-$x1,$h0,$tt,0,0,'L');
  $y+=$h0;
  $pdf->SetXY($x1,$y);
  $t=trim(mb_substr($t,mb_strlen($tt)));
}
$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,$h0,count($kurs->klassen)>1 ? 'Klassen' : 'Klasse',0,0,'L');
foreach($kurs->klassen as $klasse) {
  $pdf->Cell($x3-$x1,$h0,$klasse->bezeichnung,0,2,'L');
  $y+=$h0;
}
$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,$h0,'Trainer:',0,0,'L');
$t=array();
foreach($kurs->dozenten as $dozent) {
  $t[]=$dozent->vorname.' '.$dozent->nachname;
}
$pdf->Cell($x3-$x1,$h0,implode(', ',$t),0,2,'L');
$y+=$h0;
$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,$h0,'Teilklausur:',0,0,'L');
$pdf->Cell($x3-$x1,$h0,'JA  /  NEIN',0,2,'L');
$y+=$h0;
$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,$h0,'Klausurtag:',0,0,'L');
$y+=$h0;
$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,$h0,'Anzahl Aufgaben:',0,0,'L');
$y+=$h0;
$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,$h0,'Zeitdauer:',0,0,'L');
$pdf->Cell($x3-$x1,$h0,'______ Minuten',0,2,'L');
$y+=$h0;
$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,$h0,'Unterlagen:',0,0,'L');
$pdf->Cell($x3-$x1,$h0,'Unterlagen dürfen ________ genutzt werden',0,2,'L');
$y+=$h0;
$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,$h0,'Erlaubte Hilfsmittel:',0,0,'L');
$y+=$h0;
$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,$h0,'Erreichbare Punktzahl:',0,0,'L');
$y+=$h0;

$y+=5;
$pdf->setFont('DejaVuSans','B',14);
$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,16,'Name Teilnehmer/in:',0,0,'L');
$y+=16;

$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,16,'Erreichte Punktzahl:',1,0,'L');
$pdf->Cell($x3-$x1,16,'          ',1,0,'L');
$y+=16;

$y+=12;
$pdf->Line($x0,$y+4,$x3,$y+4);
$pdf->setFont('DejaVuSans','',12);
$pdf->SetXY($x0,$y);
$pdf->Cell($x1-$x0,16,'Datum',0,0,'L');
$pdf->Cell($x3-$x1,16,'Unterschrift Trainer',0,0,'L');

$y=$y2;
$pdf->setFont('DejaVuSans','',10);
$pdf->SetXY($x2,$y);
$pdf->Cell($x3-$x2,$h2,'100  -  92  sehr gut',0,1,'L');
$y+=$h2;
$pdf->SetXY($x2,$y);
$pdf->Cell($x3-$x2,$h2,'  91  -  81  gut',0,1,'L');
$y+=$h2;
$pdf->SetXY($x2,$y);
$pdf->Cell($x3-$x2,$h2,'  80  -  67  befriedigend',0,1,'L');
$y+=$h2;
$pdf->SetXY($x2,$y);
$pdf->Cell($x3-$x2,$h2,'  66  -  50  ausreichend',0,1,'L');
$y+=$h2;
$pdf->SetXY($x2,$y);
$pdf->Cell($x3-$x2,$h2,'  49  -  30  mangelhaft',0,1,'L');
$y+=$h2;
$pdf->SetXY($x2,$y);
$pdf->Cell($x3-$x2,$h2,'  29  -    0  ungenügend',0,1,'L');

$pdf->Output($kurs->titel.' '.$art.'-Deckblatt.pdf','D');
exit;
?>