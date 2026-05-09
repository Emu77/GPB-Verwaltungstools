<?php
require_once 'check_login.php';
require_once 'angebot_funktionen.php';
require_once 'spez_teilzeit_wochenue.php';
require_once '../tcpdf/tcpdf.php';

$nureineausbildungid=isset($_GET['nureineausbildungid']) ? (int)$_GET['nureineausbildungid'] : 0;
$nureinmodulid=isset($_GET['nureinmodulid']) ? (int)$_GET['nureinmodulid'] : 0;
$nureinanfangsdatum=isset($_GET['nureinanfangsdatum']) ? $_GET['nureinanfangsdatum'] : false;

$wochensek=strtotime('2025-01-08')-strtotime('2025-01-01');
$monatsnamen=array(
  '01'=>'Januar',
  '02'=>'Februar',
  '03'=>'März',
  '04'=>'April',
  '05'=>'Mai',
  '06'=>'Juni',
  '07'=>'Juli',
  '08'=>'August',
  '09'=>'September',
  '10'=>'Oktober',
  '11'=>'November',
  '12'=>'Dezember'
);

$interessent=isset($_SESSION['spez_interessent']) ? $_SESSION['spez_interessent'] : null;
$zeitmodell=isset($_SESSION['spez_zeitmodell']) ? $_SESSION['spez_zeitmodell'] : 'Vollzeit';
$wochenue=isset($_SESSION['spez_wochenue']) ? $_SESSION['spez_wochenue'] : 24;
$ausbildungids=isset($_SESSION['spez_ausbildungids']) ? $_SESSION['spez_ausbildungids'] : array();
$modulids=isset($_SESSION['spez_modulids']) ? $_SESSION['spez_modulids'] : array();
$anfangsdatum=isset($_SESSION['spez_anfangsdatum']) && !empty($_SESSION['spez_anfangsdatum']) ? date('Y-m-d',max(strtotime('next monday'),strtotime($_SESSION['spez_anfangsdatum']))) : date('Y-m-d',strtotime('next monday'));
$modulanfaenge=isset($_SESSION['spez_modulanfaenge']) ? $_SESSION['spez_modulanfaenge'] : array();

if($nureineausbildungid>0) {
  $ausbildungids=array($nureineausbildungid=>$ausbildungids[$nureineausbildungid]);
  $modulids=array();
} else if($nureinmodulid>0) {
  $ausbildungids=array();
  $modulids=array($nureinmodulid=>true);
  if(!empty($nureinanfangsdatum)) {
    $anfangsdatum=$nureinanfangsdatum;
  }
}

$ausbildungen=array();
$ausbildungenById=array();
$result=$db->query("select * from gpb_spezausbildung");
while($row=$result->fetch_object()) {
  $row->zertif_von=strtotime($row->zertif_beginn);
  $row->zertif_bis=strtotime($row->zertif_ende);
  $ausbildungen[]=$row;
  $ausbildungenById[$row->id]=$row;
}
$result->free();

$module=array();
$moduleById=array();
$result=$db->query("select m.* from gpb_spezmodul m order by m.titel");
while($row=$result->fetch_object()) {
  $row->zertif_von=strtotime($row->zertif_beginn);
  $row->zertif_bis=strtotime($row->zertif_ende);
  $module[]=$row;
  $moduleById[$row->id]=$row;
}
$result->free();

$preis=0.0;
$anzahlue=0;
foreach($ausbildungids as $ausid=>$modids) {
  $aus=$ausbildungenById[$ausid];
  $aus->preis=0.0;
  $aus->anzahlue=0;
  $aus->module=array();
  foreach($modids as $modid=>$dummy) {
    if(!isset($moduleById[$modid])) continue;
    $mod=$moduleById[$modid];
    $aus->preis+=$mod->preis;
    $aus->anzahlue+=$mod->anzahlue;
    if(isset($modulanfaenge[$mod->id])) {
      $mod->anfang=$modulanfaenge[$mod->id];
      $mod->von=strtotime($mod->anfang);
    } else {
      $mod->anfang='';
      $mod->von=0;
    }
    $aus->module[]=$mod;
  }
  $preis+=$aus->preis;
  $anzahlue+=$aus->anzahlue;
  usort($aus->module,'module_vergleichen');
}
$einzelmodule=array();
foreach($modulids as $modid=>$dummy) {
  if(!isset($moduleById[$modid])) continue;
  $mod=$moduleById[$modid];
  $preis+=$mod->preis;
  $anzahlue+=$mod->anzahlue;
  if(isset($modulanfaenge[$mod->id])) {
    $mod->anfang=$modulanfaenge[$mod->id];
    $mod->von=strtotime($mod->anfang);
  } else {
    $mod->anfang='';
    $mod->von=0;
  }
  $einzelmodule[]=$mod;
}
usort($einzelmodule,'module_vergleichen');

if($zeitmodell=='Vollzeit') {
  $vollzeit_wochenue=0;
  foreach($ausbildungids as $ausid=>$modids) {
    $aus=$ausbildungenById[$ausid];
    if(!empty($aus->vollzeit_wochenue)) {
      if($vollzeit_wochenue==0) {
        $vollzeit_wochenue=$aus->vollzeit_wochenue;
      } else if($vollzeit_wochenue>0 && $vollzeit_wochenue!=$aus->vollzeit_wochenue) {
        $vollzeit_wochenue=-1;
      }
    }
  }
  foreach($einzelmodule as $mod) {
    if(empty($mod->vollzeit_wochenue) && $mod->zertifmit>0) {
      $aus=$ausbildungenById[$mod->zertifmit];
      $mod->vollzeit_wochenue=$aus->vollzeit_wochenue;
    }
    if(!empty($mod->vollzeit_wochenue)) {
      if($vollzeit_wochenue==0) {
        $vollzeit_wochenue=$mod->vollzeit_wochenue;
      } else if($vollzeit_wochenue>0 && $vollzeit_wochenue!=$mod->vollzeit_wochenue) {
        $vollzeit_wochenue=-1;
      }
    }
  }
}

$ferien=array();
$result=$db->query("select * from gpb_ferien where art in('Feiertag','Berliner Schulferien') and ende>=current_date() order by beginn,ende");
while($row=$result->fetch_object()) {
  $row->von=strtotime($row->beginn);
  $row->bis=strtotime($row->ende);
  $ferien[]=$row;
}
$result->free();
$urlaub=array();
foreach($interessent->urlaub as $row) {
  $u=(object)array(
    'beginn'=>$row->beginn,
    'von'=>strtotime($row->beginn),
    'ende'=>$row->ende,
    'bis'=>strtotime($row->ende),
    'art'=>'Urlaub',
    'anlass'=>'Urlaub'
  );
  $urlaub[]=$u;
  $ferien[]=$u;
}

$woue=$wochenue;
$kalvon=strtotime(date('Y-m-01'));
$tag=skip_ferien(strtotime($anfangsdatum));
$erstertag=$tag;
$letztertag=$tag;
$kal=array();
foreach($ausbildungids as $ausid=>$modids) {
  $aus=$ausbildungenById[$ausid];
  $aus->von=$tag;
  if($zeitmodell=='Vollzeit') {
    $woue=empty($ausbildung->vollzeit_wochenue) ? 38 : $ausbildung->vollzeit_wochenue;
  }
  foreach($aus->module as $mod) {
    if(!empty($mod->anfang)) {
      $tag=max($tag,skip_ferien(strtotime($mod->anfang)));
      $letztertag=$tag;
    }
    $mod->von=$tag;
    if(!isset($aus->von)) {
      $aus->von=$tag;
    }
    if(!isset($erstertag)) {
      $erstertag=$tag;
    }
    for($dauer=0;$dauer<$mod->anzahlue;$dauer+=$woue/5) {
      $kal[date('Y-m-d',$tag)]=$mod;
      $mod->bis=$tag;
      $aus->bis=$tag;
      $letztertag=$tag;
      $tag=skip_ferien(strtotime('+1 day',$tag));
    }
    $mod->anzahlmonate=((int)date('Y',strtotime('+1 week',$mod->bis))-(int)date('Y',$mod->von))*12+(int)date('m',strtotime('+1 week',$mod->bis))-(int)date('m',$mod->von)+1;
  }
  $aus->anzahlmonate=((int)date('Y',strtotime('+2 weeks',$aus->bis))-(int)date('Y',$aus->von))*12+(int)date('m',strtotime('+2 weeks',$aus->bis))-(int)date('m',$aus->von)+1;
}
foreach($einzelmodule as $mod) {
  if($zeitmodell=='Vollzeit') {
    $woue=empty($mod->vollzeit_wochenue) ? 38 : $mod->vollzeit_wochenue;
  }
  if(!empty($mod->anfang)) {
    $tag=max($tag,skip_ferien(strtotime($mod->anfang)));
    $letztertag=$tag;
  }
  $mod->von=$tag;
  if(!isset($erstertag)) {
    $erstertag=$tag;
  }
  for($dauer=0;$dauer<$mod->anzahlue;$dauer+=$woue/5) {
    $kal[date('Y-m-d',$tag)]=$mod;
    $mod->bis=$tag;
    $letztertag=$tag;
    $tag=skip_ferien(strtotime('+1 day',$tag));
  }
  $mod->anzahlmonate=((int)date('Y',strtotime('+1 week',$mod->bis))-(int)date('Y',$mod->von))*12+(int)date('m',strtotime('+1 week',$mod->bis))-(int)date('m',$mod->von)+1;
}

if(isset($erstertag)) {
  $kalbis=strtotime('next month',$tag);
} else {
  $erstertag=$tag;
  $kalbis=strtotime('+6 months',$tag);
}

$pdf=new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor('GPBmbH');
$pdf->setTitle('GPB Qualifizierungsangebot '.$interessent->vorname.' '.$interessent->nachname.' '.date('d.m.Y'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15,10,15,true);
$pdf->SetAutoPageBreak(false,10);

foreach($ausbildungids as $ausid=>$modids) {
  $aus=$ausbildungenById[$ausid];
  
  $ersetzungen=array(
    'INTERESSENTVORNAME INTERESSENTNACHNAME'=>''
    ,'INTERESSENTANREDE'=>''
    ,'INTERESSENTVORNAME'=>''
    ,'INTERESSENTNACHNAME'=>''
    ,'INTERESSENTSTRASSE'=>''
    ,'INTERESSENTPLZ'=>''
    ,'INTERESSENTORT'=>''
    ,'SEINE'=>'ihre/seine'
    ,'URLAUB'=>''
    ,'BERATUNGSDATUM'=>date('d.m.Y')
    ,'AUSBILDUNGSTITEL'=>$aus->titel
    ,'AUSBILDUNGSMASSNAHMENUMMER'=>($zeitmodell=='Vollzeit' ? $aus->vollzeit_massnnr : $aus->teilzeit_massnnr)
    ,'AUSBILDUNGSPREIS'=>format_preis($preis)
    ,'ANZAHLMODULE'=>count($modids)
    ,'AUSBILDUNGSANFANG'=>date('d.m.Y',$aus->von)
    ,'AUSBILDUNGSENDE'=>date('d.m.Y',$aus->bis)
    ,'AUSBILDUNGSDAUER'=>$anzahlue
    ,'AUSBILDUNGSZERTIFZEITRAUM'=>empty($aus->zertif_beginn) || $aus->zertif_beginn=='0000-00-00' || empty($aus->zertif_ende) || $aus->zertif_ende=='0000-00-00' ? 'in Zertifizierung' : date('d.m.Y',$aus->zertif_von).' bis '.date('d.m.Y',$aus->zertif_bis)
    ,'ZEITMODELL'=>$zeitmodell
    ,'ANZAHLUEPROWOCHE'=>($zeitmodell=='Vollzeit' ? $aus->vollzeit_wochenue : $wochenue)
    ,'AUSBILDUNGSMONATE'=>$aus->anzahlmonate
    ,'BERATERNAME'=>''
    ,'BERATERTEL'=>''
  );
  if($interessent) {
    $ersetzungen['INTERESSENTVORNAME INTERESSENTNACHNAME']=null;
    $ersetzungen['INTERESSENTANREDE']=$interessent->anrede;
    $ersetzungen['INTERESSENTVORNAME']=$interessent->vorname;
    $ersetzungen['INTERESSENTNACHNAME']=$interessent->nachname;
    $ersetzungen['INTERESSENTSTRASSE']=$interessent->strasse;
    $ersetzungen['INTERESSENTPLZ']=$interessent->plz;
    $ersetzungen['INTERESSENTORT']=$interessent->ort;
    $ersetzungen['SEINE']=$interessent->anrede=='Herr' ? 'seine' : 'ihre';
  }
  if(!empty($urlaub)) {
    $ersetzungen['URLAUB']="<br />\nIndividuell abgesprochener Urlaub:<br />\n";
    foreach($urlaub as $u) {
      $ersetzungen['URLAUB'].="&nbsp; &nbsp; ".date('d.m.Y',$u->von)
        .(empty($u->ende) || $u->ende=='0000-00-00' || $u->ende==$u->beginn ? '' : ' - '.date('d.m.Y',$u->bis))
        ."<br />\n";
    }
  }
  $ersetzungen['BERATERNAME']=$ich->vorname.' '.$ich->nachname;
  $ersetzungen['BERATERTEL']=$ich->tel;
  
  $inhalt=file_get_contents('vorlagen/QualifizierungsangebotSpezialistGanzeAusbildung.html');
  foreach($ersetzungen as $k=>$v) {
    if($v===null) continue;
    $inhalt=str_replace($k,$v,$inhalt);
  }
  
  $i0=mb_strpos($inhalt,'<ul id="module">')+mb_strlen('<ul id="module">');
  $i1=mb_strpos($inhalt,'</ul>',$i0);
  $modultext=mb_substr($inhalt,$i0,$i1-$i0);
  $inhaltanfang=mb_substr($inhalt,0,$i0);
  $inhaltende=mb_substr($inhalt,$i1);
  foreach($aus->module as $mod) {
    $mtext=$modultext;
    $mtext=str_replace('MODULTITEL',$mod->titel,$mtext);
    $mtext=str_replace('MODULDAUER',$mod->anzahlue,$mtext);
    $mtext=str_replace('MODULPREIS',format_preis($mod->preis),$mtext);
    $mtext=str_replace('MODULANFANG',date('d.m.Y',$mod->von),$mtext);
    $mtext=str_replace('MODULENDE',date('d.m.Y',$mod->bis),$mtext);
    $inhaltanfang.=$mtext;
  }
  $inhalt=$inhaltanfang.$inhaltende;
  
  $i0=mb_strpos($inhalt,'<section id="header">')+mb_strlen('<section id="header">');
  $i1=mb_strpos($inhalt,'</section>',$i0);
  $header=mb_substr($inhalt,$i0,$i1-$i0);
  $i0=mb_strpos($inhalt,'<section id="footer">')+mb_strlen('<section id="footer">');
  $i1=mb_strpos($inhalt,'</section>',$i0);
  $footer=mb_substr($inhalt,$i0,$i1-$i0);
  $i0=mb_strpos($inhalt,'<section id="seite1">')+mb_strlen('<section id="seite1">');
  $i1=mb_strpos($inhalt,'</section>',$i0);
  $seite1=mb_substr($inhalt,$i0,$i1-$i0);
  $i0=mb_strpos($inhalt,'<section id="seite2">')+mb_strlen('<section id="seite2">');
  $i1=mb_strpos($inhalt,'</section>',$i0);
  $seite2=mb_substr($inhalt,$i0,$i1-$i0);
  
  $pdf->AddPage();
  $pdf->SetFont('DejaVuSans','',11);
  $pdf->WriteHTML($header,false,false,false,false,'C');
  $pdf->SetFont('DejaVuSans','',11);
  $pdf->WriteHTML($seite1,false,false,false,false,'C');
  $pdf->SetXY(0,265);
  $pdf->SetFont('DejaVuSans','',8);
  $pdf->WriteHTML($footer,false,false,false,false,'C');
  
  $pdf->AddPage();
  $pdf->SetFont('DejaVuSans','',11);
  $pdf->WriteHTML($header,false,false,false,false,'C');
  $pdf->SetFont('DejaVuSans','',11);
  $pdf->WriteHTML($seite2,false,false,false,false,'C');
  $pdf->SetXY(0,265);
  $pdf->SetFont('DejaVuSans','',8);
  $pdf->WriteHTML($footer,false,false,false,false,'C');
}

foreach($einzelmodule as $mod) {
   
    $ersetzungen=array(
      'INTERESSENTVORNAME INTERESSENTNACHNAME'=>''
      ,'INTERESSENTANREDE'=>''
      ,'INTERESSENTVORNAME'=>''
      ,'INTERESSENTNACHNAME'=>''
      ,'INTERESSENTSTRASSE'=>''
      ,'INTERESSENTPLZ'=>''
      ,'INTERESSENTORT'=>''
      ,'SEINE'=>'seine/ihre'
      ,'URLAUB'=>''
      ,'BERATUNGSDATUM'=>date('d.m.Y')
      ,'MODULTITEL'=>$mod->titel
      ,'MODULDAUER'=>$mod->anzahlue
      ,'MODULPREIS'=>format_preis($mod->preis)
      ,'MODULANFANG'=>date('d.m.Y',$mod->von)
      ,'MODULENDE'=>date('d.m.Y',$mod->bis)
      ,'MODULMONATE'=>$mod->anzahlmonate
      ,'MODULMASSNAHMENUMMER'=>($zeitmodell=='Vollzeit' ? $mod->vollzeit_massnnr : $mod->teilzeit_massnnr)
      ,'MODULZERTIFZEITRAUM'=>(empty($mod->zertif_beginn) || $mod->zertif_beginn=='0000-00-00' || empty($mod->zertif_ende) || $mod->zertif_ende=='0000-00-00' ? 'in Zertifizierung' : date('d.m.Y',$mod->zertif_von).' bis '.date('d.m.Y',$mod->zertif_bis))
      ,'ZEITMODELL'=>$zeitmodell
      ,'ANZAHLUEPROWOCHE'=>($zeitmodell=='Vollzeit' ? $mod->vollzeit_wochenue : $wochenue)
      ,'MODULMONATE'=>(1+12*((int)date('Y',$mod->bis)-(int)date('Y',$mod->von))+(int)date('m',$mod->bis)-(int)date('m',$mod->von))
      ,'BERATERNAME'=>''
      ,'BERATERTEL'=>''
    );
    if($interessent) {
      $ersetzungen['INTERESSENTVORNAME INTERESSENTNACHNAME']=null;
      $ersetzungen['INTERESSENTANREDE']=$interessent->anrede;
      $ersetzungen['INTERESSENTVORNAME']=$interessent->vorname;
      $ersetzungen['INTERESSENTNACHNAME']=$interessent->nachname;
      $ersetzungen['INTERESSENTSTRASSE']=$interessent->strasse;
      $ersetzungen['INTERESSENTPLZ']=$interessent->plz;
      $ersetzungen['INTERESSENTORT']=$interessent->ort;
      $ersetzungen['SEINE']=$interessent->anrede=='Herr' ? 'seine' : 'ihre';
    }
    if(!empty($urlaub)) {
      $ersetzungen['URLAUB']="<br />\nIndividuell abgesprochener Urlaub:<br />\n";
      foreach($urlaub as $u) {
        $ersetzungen['URLAUB'].="&nbsp; &nbsp; ".date('d.m.Y',$u->von)
          .(empty($u->ende) || $u->ende=='0000-00-00' || $u->ende==$u->beginn ? '' : ' - '.date('d.m.Y',$u->bis))
          ."<br />\n";
      }
    }
    $ersetzungen['BERATERNAME']=$ich->vorname.' '.$ich->nachname;
    $ersetzungen['BERATERTEL']=$ich->tel;
    
    $inhalt=file_get_contents('vorlagen/QualifizierungsangebotSpezialistEinzelnesModul.html');
    foreach($ersetzungen as $k=>$v) {
      if($v===null) continue;
      $inhalt=str_replace($k,$v,$inhalt);
    }
    
    $i0=mb_strpos($inhalt,'<section id="header">')+mb_strlen('<section id="header">');
    $i1=mb_strpos($inhalt,'</section>',$i0);
    $header=mb_substr($inhalt,$i0,$i1-$i0);
    $i0=mb_strpos($inhalt,'<section id="footer">')+mb_strlen('<section id="footer">');
    $i1=mb_strpos($inhalt,'</section>',$i0);
    $footer=mb_substr($inhalt,$i0,$i1-$i0);
    $i0=mb_strpos($inhalt,'<section id="seite1">')+mb_strlen('<section id="seite1">');
    $i1=mb_strpos($inhalt,'</section>',$i0);
    $seite1=mb_substr($inhalt,$i0,$i1-$i0);
    $i0=mb_strpos($inhalt,'<section id="seite2">')+mb_strlen('<section id="seite2">');
    $i1=mb_strpos($inhalt,'</section>',$i0);
    $seite2=mb_substr($inhalt,$i0,$i1-$i0);
    
    $pdf->AddPage();
    $pdf->SetFont('DejaVuSans','',11);
    $pdf->WriteHTML($header,false,false,false,false,'C');
    $pdf->SetFont('DejaVuSans','',11);
    $pdf->WriteHTML($seite1,false,false,false,false,'C');
    $pdf->SetXY(0,255);
    $pdf->SetFont('DejaVuSans','',8);
    $pdf->WriteHTML($footer,false,false,false,false,'C');
    
    $pdf->AddPage();
    $pdf->SetFont('DejaVuSans','',11);
    $pdf->WriteHTML($header,false,false,false,false,'C');
    $pdf->SetFont('DejaVuSans','',11);
    $pdf->WriteHTML($seite2,false,false,false,false,'C');
    $pdf->SetXY(0,255);
    $pdf->SetFont('DejaVuSans','',8);
    $pdf->WriteHTML($footer,false,false,false,false,'C');
}

if(!empty($ich->signaturbild) && file_exists('signaturbilder/'.$ich->signaturbild)) {
  for($n=$pdf->getNumPages(),$i=1;$i<=$n;$i+=2) {
    $pdf->setPage($i);
    $pdf->Image('signaturbilder/'.$ich->signaturbild,50,230,40); 
  }
}

$pdf->Output('GPB Qualifizierungsangebot '.$interessent->vorname.' '.$interessent->nachname.' '.date('d.m.Y').'.pdf','D');
?>