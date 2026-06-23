<?php
/**
 *  MITIS übergibt POST-Daten:
 *  action 'alles', 'anwesenheit' oder 'antwort'
 *
 *  Bei 'alles' oder 'anwesenheit'
 *  Klasse_ID
 *  MITIS_GUID  6B2AA285-C665-4967-AC6C-89CEB4D3F9A8
 *  START_DATUM
 *                            
 *  Rückgabe  im json Format:   
 *  TransID   Eindeutige Übertragungs-ID für Rückmeldung
 *  Klasse    ID der Klasse zur Kontrolle 
 *  Array mit Anwesenheits-Einträgen
 *  Array mit Soll-Stunden
 *  Array mit Klassenbuch-Einträgen
 *
 *  Bei 'antwort'
 *  TransID bei der vorigen Aktion gegebene unique id
 *  Meldungen Meldungen von MITIS zur vorigen Aktion
 */

$MITIS_GUID=isset($_POST['MITIS_GUID']) ? $_POST['MITIS_GUID'] : (isset($_GET['MITIS_GUID']) ? $_GET['MITIS_GUID'] : false);
if(empty($MITIS_GUID) /*$MITIS_GUID!='6B2AA285-C665-4967-AC6C-89CEB4D3F9A8'*/) {
  exit;
}
$action=isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : false);
if(strtolower($action)=='antwort') {
  $TransID=isset($_POST['TransID']) ? $_POST['TransID'] : (isset($_GET['TransID']) ? $_GET['TransID'] : false);
  $Meldungen=isset($_POST['Meldungen']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['Meldungen'])) : (isset($_GET['Meldungen']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_GET['Meldungen'])) : false);
  //todo eventuell unique id speichern und loggen
  exit;
}

$klassemitisid=isset($_POST['Klasse_ID']) ? (int)$_POST['Klasse_ID'] : (isset($_GET['Klasse_ID']) ? (int)$_GET['Klasse_ID'] : 0);
$startdatum=isset($_POST['START_DATUM']) ? $_POST['START_DATUM'] : (isset($_GET['START_DATUM']) ? $_GET['START_DATUM'] : '');

require_once '../../db.php';

$daten=array();
if(!empty($startdatum) && !empty($klassemitisid)) {
  $stmt=$db->prepare("select a.anfang,a.ende,a.mitis,a.tag
      ,date_format(a.geaendertam,'%Y-%m-%dT%T') as changed,tn.mitisid
    from gpb_anwesenheit a
    join gpb_tn tn on tn.id=a.tnid 
    join gpb_klasse_tn ktn on ktn.tnid=a.tnid and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=a.tag) and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=a.tag) 
    join gpb_klasse k on k.id=ktn.klasseid 
    where a.geaendertam>=? and a.tag<current_date() and k.mitisid=?");
  $stmt->bind_param('si',$startdatum,$klassemitisid);
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $row->mitisid=(int)$row->mitisid;
    $daten[]=$row;
  }
  $result->free();
}

$mitisanw=array();
$sollstunden=array();
$ueprotag=array(
  '1'=>(object)array('vorm'=>5,'nachm'=>4,'anzahlue'=>9), //Mo
  '2'=>(object)array('vorm'=>5,'nachm'=>4,'anzahlue'=>9), //Di
  '3'=>(object)array('vorm'=>5,'nachm'=>4,'anzahlue'=>9), //Mi
  '4'=>(object)array('vorm'=>4,'nachm'=>3,'anzahlue'=>7), //Do
  '5'=>(object)array('vorm'=>3,'nachm'=>3,'anzahlue'=>6), //Fr
);
foreach($daten as $row) {
  $tag=date('w',strtotime($row->tag));
  if(!isset($ueprotag[$tag])) { //So,Sa
    continue;
  }
  
  // Anwesend Anfang oder Ende => Anwesend den ganzen Tag (wird mit Kommt-Geht-Liste abgedeckt)
  if($row->anfang=='F') {
    if($row->ende=='A' || $row->ende=='O') {
      $row->anfang=$row->ende;
    }
  }
  if($row->ende=='F') {
    if($row->anfang=='A' || $row->anfang=='O') {
      $row->ende=$row->anfang;
    }
  }
  
  if(!empty($row->mitis)) {
	  // schön wär's, wenn MITIS den Buchstaben für den ganzen Tag importieren ließe
	  //$datumvorhanden=true;
	  //Vereinbarung: array($datum,$uenummer,(int)$tnmitisid,$buchstabe,(int)$eintragermitisid,$changed);
      //Eintrager: die MITIS-ID vom Dozent oder Verwalter, der die Anwesenheit eingetragen hat. Diese Info haben wir hier nicht, wir nehmen R. Mouton.
      //$mitisanw[]=array($row->tag,0,$row->mitisid,$row->mitis,76068,$row->changed);
  }
  
  $uenummer=1;
  $datumvorhanden=false;
  if(empty($row->anfang)) {
    $uenummer+=$ueprotag[$tag]->vorm;
  } else {
    $datumvorhanden=true;
    for($i=0;$i<$ueprotag[$tag]->vorm;++$i,++$uenummer) {
      //Vereinbarung: array($datum,$uenummer,(int)$tnmitisid,$buchstabe,(int)$eintragermitisid,$changed);
      //Eintrager: die MITIS-ID vom Dozent oder Verwalter, der die Anwesenheit eingetragen hat. Diese Info haben wir hier nicht, wir nehmen R. Mouton.
      $mitisanw[]=array($row->tag,$uenummer,$row->mitisid,$row->anfang,76068,$row->changed);
    }
  }
  if(empty($row->ende)) {
    $uenummer+=$ueprotag[$tag]->nachm;
  } else {
    $datumvorhanden=true;
    for($i=0;$i<$ueprotag[$tag]->nachm;++$i,++$uenummer) {
      //Vereinbarung: array($datum,$uenummer,(int)$tnmitisid,$buchstabe,(int)$eintragermitisid,$changed);
      //Eintrager: die MITIS-ID vom Dozent oder Verwalter, der die Anwesenheit eingetragen hat. Diese Info haben wir hier nicht, wir nehmen R. Mouton.
      $mitisanw[]=array($row->tag,$uenummer,$row->mitisid,$row->ende,76068,$row->changed);
    }
  }
  if($datumvorhanden) {
    //Vereinbarung: array($datum,$anzahlue)
    $sollstunden[]=array($row->tag,$ueprotag[$tag]->anzahlue);
  }
}

//todo eventuell unique id speichern und loggen
$data=(object)array(
  'TransID'=>uniqid()
  ,'Klasse'=>$klassemitisid
  ,'Anwesenheiten'=>$mitisanw
  ,'Sollstunden'=>$sollstunden
  ,'Klassenbuch'=>array()
);
echo json_encode($data);
exit;
?>