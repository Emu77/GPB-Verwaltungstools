<?php
require_once 'check_login.php';
require_once '../tcpdf/tcpdf.php';

$modulid=isset($_GET['modulid']) ? (int)$_GET['modulid'] : 0;
$paketid=isset($_GET['paketid']) ? (int)$_GET['paketid'] : 0;

if($modulid>0) {
  $result=$db->query("select * from gpb_intrainmodul where id=".$modulid." limit 1");
  $modul=$result->fetch_object();
  $result->free();
  if(empty($modul)) {
    header('Location:intrain.php');
    exit;
  }
  
  $vorlage=file_get_contents('vorlagen/IntrainModulbeschreibung.html');
  $vorlage=substr($vorlage,strpos($vorlage,'<body>')+6);
  $vorlage=substr($vorlage,0,strrpos($vorlage,'</body>'));
  
  $ersetzungen=array(
    'INTERESSENT'=>''
    ,'TITEL'=>$modul->titel
    ,'BERUFLICHERNUTZEN'=>nl2br($modul->nutzen)
    ,'LERNINHALT'=>nl2br($modul->lerninhalte)
    ,'LERNZIEL'=>nl2br($modul->lernziele)
  );
  $inhalt=$vorlage;
  foreach($ersetzungen as $k=>$v) {
    $inhalt=str_replace($k,$v,$inhalt);
  }
  
  $pdf=new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
  $pdf->setCreator(PDF_CREATOR);
  $pdf->setAuthor('GPBmbH');
  $pdf->setTitle('GPB inTrain Modul '.$modul->titel);
  $pdf->setPrintHeader(false);
  $pdf->setPrintFooter(false);
  $pdf->SetMargins(20,10,20,true);
  $pdf->SetAutoPageBreak(false,10);
  
  $pdf->AddPage();
  $pdf->SetFont('DejaVuSans','',12);
  $pdf->WriteHTML($inhalt,false,false,false,false,'C');
  
  $pdf->Output('GPB inTrain Modul '.$modul->titel.'.pdf','D');
  exit;
}

if($paketid>0) {
  $result=$db->query("select * from gpb_intrainpaket where id=".$paketid." limit 1");
  $paket=$result->fetch_object();
  $result->free();
  if(empty($paket)) {
    header('Location:intrain.php');
    exit;
  }
  
  $module=array();
  $result=$db->query("select * from gpb_intrainmodul where id in(select modulid from gpb_intrainpaket_modul where paketid=".$paketid.") order by kurzbezeichnung");
  while($row=$result->fetch_object()) {
    $module[]=$row;
  }
  $result->free();
  
  $vorlage=file_get_contents('vorlagen/IntrainPaketbeschreibung.html');
  $vorlage=substr($vorlage,strpos($vorlage,'<body>')+6);
  $vorlage=substr($vorlage,0,strrpos($vorlage,'</body>'));
  $vorlagePaket=$vorlage;
  
  $vorlage=file_get_contents('vorlagen/IntrainModulbeschreibung.html');
  $vorlage=substr($vorlage,strpos($vorlage,'<body>')+6);
  $vorlage=substr($vorlage,0,strrpos($vorlage,'</body>'));
  $vorlageModul=$vorlage;
  
  $pdf=new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
  $pdf->setCreator(PDF_CREATOR);
  $pdf->setAuthor('GPBmbH');
  $pdf->setTitle('GPB inTrain Weiterbildung '.$paket->bezeichnung);
  $pdf->setPrintHeader(false);
  $pdf->setPrintFooter(false);
  $pdf->SetMargins(20,10,20,true);
  $pdf->SetAutoPageBreak(false,10);
  
  $ersetzungen=array(
    'INTERESSENT'=>''
    ,'TITEL'=>$paket->bezeichnung
    ,'BERUFLICHERNUTZEN'=>nl2br($paket->nutzen)
    ,'LERNINHALT'=>nl2br($paket->lerninhalte)
    ,'LERNZIEL'=>nl2br($paket->lernziele)
  );
  $inhalt=$vorlagePaket;
  foreach($ersetzungen as $k=>$v) {
    $inhalt=str_replace($k,$v,$inhalt);
  }
  $pdf->AddPage();
  $pdf->SetFont('DejaVuSans','',12);
  $pdf->WriteHTML($inhalt,false,false,false,false,'C');
  
  foreach($module as $modul) {
    $ersetzungen=array(
      'INTERESSENT'=>''
      ,'TITEL'=>$modul->titel
      ,'BERUFLICHERNUTZEN'=>nl2br($modul->nutzen)
      ,'LERNINHALT'=>nl2br($modul->lerninhalte)
      ,'LERNZIEL'=>nl2br($modul->lernziele)
    );
    $inhalt=$vorlageModul;
    foreach($ersetzungen as $k=>$v) {
      $inhalt=str_replace($k,$v,$inhalt);
    }
    $pdf->AddPage();
    $pdf->SetFont('DejaVuSans','',12);
    $pdf->WriteHTML($inhalt,false,false,false,false,'C');
  }
  
  $pdf->Output('GPB inTrain Weiterbildung '.$paket->bezeichnung.'.pdf','D');
  exit;
}

$interessent=isset($_SESSION['intrain_interessent']) ? $_SESSION['intrain_interessent'] : null;
$modulids=isset($_SESSION['intrain_modulids']) ? $_SESSION['intrain_modulids'] : array();
if(empty($interessent) || empty($modulids)) {
  header('Location:intrain.php');
  exit;
}
$module=array();
$result=$db->query("select * from gpb_intrainmodul where id in(".implode(',',array_keys($modulids)).") order by kurzbezeichnung");
while($row=$result->fetch_object()) {
  $module[]=$row;
}
$result->free();
if(empty($module)) {
  header('Location:intrain.php');
  exit;
}
$vorlage=file_get_contents('vorlagen/IntrainModulbeschreibung.html');
$vorlage=substr($vorlage,strpos($vorlage,'<body>')+6);
$vorlage=substr($vorlage,0,strrpos($vorlage,'</body>'));

$pdf=new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor('GPBmbH');
$pdf->setTitle('GPB inTrain Weiterbildung '.$interessent->vorname.' '.$interessent->nachname);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(20,10,20,true);
$pdf->SetAutoPageBreak(false,10);

foreach($module as $modul) {
  $ersetzungen=array(
    'INTERESSENT'=>$interessent->vorname.' '.$interessent->nachname
    ,'TITEL'=>$modul->titel
    ,'BERUFLICHERNUTZEN'=>nl2br($modul->nutzen)
    ,'LERNINHALT'=>nl2br($modul->lerninhalte)
    ,'LERNZIEL'=>nl2br($modul->lernziele)
  );
  $inhalt=$vorlage;
  foreach($ersetzungen as $k=>$v) {
    $inhalt=str_replace($k,$v,$inhalt);
  }
  $pdf->AddPage();
  $pdf->SetFont('DejaVuSans','',12);
  $pdf->WriteHTML($inhalt,false,false,false,false,'C');
}

$pdf->Output('GPB inTrain Weiterbilding '.$interessent->vorname.' '.$interessent->nachname.' '.date('d.m.Y').'.pdf','D');
exit;
?>