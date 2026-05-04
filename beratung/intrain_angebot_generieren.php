<?php
require_once 'check_login.php';
require_once 'intrain_teilzeit_wochenue.php';
require_once 'angebot_funktionen.php';
require_once '../tcpdf/tcpdf.php';

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

$interessent=isset($_SESSION['intrain_interessent']) ? $_SESSION['intrain_interessent'] : null;
$zeitmodell=isset($_SESSION['intrain_zeitmodell']) && !empty($_SESSION['intrain_zeitmodell']) ? $_SESSION['intrain_zeitmodell'] : 'Vollzeit';
$wochenue=isset($_SESSION['intrain_wochenue']) && !empty($_SESSION['intrain_wochenue']) ? $_SESSION['intrain_wochenue'] : 30;
$arbeitszeiten=isset($_SESSION['intrain_arbeitszeiten']) && !empty($_SESSION['intrain_arbeitszeiten']) ? $_SESSION['intrain_arbeitszeiten'] 
  : array(
    'Mo'=>(object)array('von'=>'08:15','bis'=>($zeitmodell=='Vollzeit' ? '16:00' : '12:45')),
    'Di'=>(object)array('von'=>'08:15','bis'=>($zeitmodell=='Vollzeit' ? '16:00' : '12:45')),
    'Mi'=>(object)array('von'=>'08:15','bis'=>($zeitmodell=='Vollzeit' ? '16:00' : '12:45')),
    'Do'=>(object)array('von'=>'08:15','bis'=>($zeitmodell=='Vollzeit' ? '14:30' : '12:45')),
    'Fr'=>(object)array('von'=>'08:15','bis'=>($zeitmodell=='Vollzeit' ? '13:45' : '12:45'))
  );
$anfangsdatum=isset($_SESSION['intrain_anfangsdatum']) && !empty($_SESSION['intrain_anfangsdatum']) ? date('Y-m-d',max(strtotime('next monday'),strtotime($_SESSION['intrain_anfangsdatum']))) : date('Y-m-d',strtotime('next monday'));
$modulids=isset($_SESSION['intrain_modulids']) ? $_SESSION['intrain_modulids'] : array();
$modulanfaenge=isset($_SESSION['intrain_modulanfaenge']) ? $_SESSION['intrain_modulanfaenge'] : array();

$zertifsById=array();
$result=$db->query("select * from gpb_intrainzertif ");
while($row=$result->fetch_object()) {
  $row->zertif_von=strtotime($row->zertif_beginn);
  $row->zertif_bis=strtotime($row->zertif_ende);
  $zertifsById[$row->id]=$row;
}
$result->free();
$module=array();
$moduleById=array();
$result=$db->query("select m.* from gpb_intrainmodul m order by m.titel");
while($row=$result->fetch_object()) {
  $module[]=$row;
  $moduleById[$row->id]=$row;
}
$result->free();

// Aus der DB verschwundene Module entfernen
foreach(array_keys($modulids) as $modid) {
  if(!isset($moduleById[$modid])) {
    unset($modulids[$modid]);
    $_SESSION['intrain_modulids']=$modulids;
    if(isset($modulanfaenge[$modid])) {
      unset($modulanfaenge[$modid]);
      $_SESSION['intrain_anfaenge']=$modulanfaenge;
    }
  }
}

$preis=0.0;
$anzahlue=0;
foreach($modulids as $modid=>$dummy) {
  $mod=$moduleById[$modid];
  $preis+=$mod->preis;
  $anzahlue+=$mod->anzahlue;
  if(isset($modulanfaenge[$mod->id])) {
    $mod->anfang=$modulanfaenge[$mod->id];
  } else {
    $mod->anfang='';
  }
}

if($zeitmodell=='Vollzeit') {
  $vollzeit_wochenue=0;
  foreach($modulids as $modid=>$dummy) {
    $mod=$moduleById[$modid];
    if(!empty($mod->vollzeit_wochenue)) {
      if($vollzeit_wochenue==0) {
        $vollzeit_wochenue=$mod->vollzeit_wochenue;
      } else if($vollzeit_wochenue>0 && $vollzeit_wochenue!=$mod->vollzeit_wochenue) {
        $vollzeit_wochenue=-1;
      }
    }
  }
  if($vollzeit_wochenue==0) {
    $vollzeit_wochenue=40;
  }
}
$tagnamen=array(
  'Mon'=>'Mo',
  'Tue'=>'Di',
  'Wed'=>'Mi',
  'Thu'=>'Do',
  'Fri'=>'Fr'
);
foreach($arbeitszeiten as $tagname=>$zeit) {
  $v=60*(int)substr($zeit->von,0,2)+(int)substr($zeit->von,3);
  $b=60*(int)substr($zeit->bis,0,2)+(int)substr($zeit->bis,3);
  $m=$b-$v;
  $zeit->anzahlue=ceil($m/45);
  $zeit->pause=0;
  if($zeit->anzahlue*45>=6*60) {
    $zeit->pause=60;
    $zeit->anzahlue=ceil(($m-60)/45);
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
$letztertag=$tag;
$kal=array();
foreach($modulids as $modid=>$dummy) {
  $mod=$moduleById[$modid];
  if($zeitmodell=='Vollzeit') {
    $woue=empty($mod->vollzeit_wochenue) ? 40 : $mod->vollzeit_wochenue;
  }
  if(!empty($mod->anfang)) {
    $tag=max($tag,skip_ferien(strtotime($mod->anfang)));
    $letztertag=$tag;
  }
  $mod->von=$tag;
  if(!isset($erstertag)) {
    $erstertag=$tag;
  }
  for($dauer=0;$dauer<$mod->anzahlue;/*nichts*/) {
    $kal[date('Y-m-d',$tag)]=$mod;
    $mod->bis=$tag;
    $letztertag=$tag;
    $dauer+=$arbeitszeiten[$tagnamen[date('D',$tag)]]->anzahlue;
    $tag=skip_ferien(strtotime('+1 day',$tag));
  }
  $mod->anzahlmonate=((int)date('Y',strtotime('+1 week',$mod->bis))-(int)date('Y',$mod->von))*12+(int)date('m',strtotime('+1 week',$mod->bis))-(int)date('m',$mod->von)+1;
}
$anzahlmonate=((int)date('Y',strtotime('+1 week',$letztertag))-(int)date('Y',$erstertag))*12+(int)date('m',strtotime('+1 week',$letztertag))-(int)date('m',$erstertag)+1;
if(isset($erstertag)) {
  $kalbis=strtotime('next month',$tag);
} else {
  $erstertag=$tag;
  $kalbis=strtotime('+6 months',$tag);
}

$moduleNachAnfang=array();
foreach($modulids as $modid=>$dummy) {
  $moduleNachAnfang[]=$moduleById[$modid];
}
function moduleVergleichen($mod0,$mod1) {
  if($mod0->von<$mod1->von) return -1;
  if($mod0->von>$mod1->von) return 1;
  return 0;
}
usort($moduleNachAnfang,'moduleVergleichen');
$angebote=array();
$ang=null;
foreach($moduleNachAnfang as $mod) {
  if($ang==null || $ang->zertifid!=$mod->zertifid) {
    $ang=(object)array(
      'zertifid'=>$mod->zertifid,
      'preis'=>$mod->preis,
      'anzahlue'=>$mod->anzahlue,
      'von'=>$mod->von,
      'bis'=>$mod->bis,
      'url'=>null,
      'betroffenerUrlaub'=>array(),
      'urlaubstage'=>0,
      'feier'=>null,
      'betroffeneFerien'=>array(),
      'feiertage'=>0,
      'module'=>array($mod)
    );
    $angebote[]=$ang;
  } else {
    $ang->module[]=$mod;
    $ang->preis+=$mod->preis;
    $ang->anzahlue+=$mod->anzahlue;
    $ang->bis=$mod->bis;
  }
}

$betroffenerUrlaub=array();
$urlaubstage=0;
$betroffeneFerien=array();
$feiertage=0;
$url=null;
$feier=null;
for($tag=$erstertag;$tag<=$letztertag;$tag=strtotime('+1day',$tag)) {
  $f=get_ferien($tag);
  if(empty($f) || $f->art=='Berliner Schulferien') {
    continue;
  }
  if($f->art=='Urlaub') {
    ++$urlaubstage;
    if($url!==$f) {
      $url=$f;
      $betroffenerUrlaub[]=$f;
    }
    foreach($angebote as $ang) {
      if($tag>=$ang->von && $tag<=$ang->bis) {
        $ang->urlaubstage++;
        if($ang->url!=$f) {
          $ang->url=$f;
          $ang->betroffenerUrlab[]=$f;
        }
      }
    }
  } else {
    ++$feiertage;
    if($feier!==$f) {
      $feier=$f;
      $betroffeneFerien[]=$f;
    }
    foreach($angebote as $ang) {
      if($tag>=$ang->von && $tag<=$ang->bis) {
        $ang->feiertage++;
        if($ang->feier!=$f) {
          $ang->feier=$f;
          $ang->betroffeneFerien[]=$f;
        }
      }
    }
  }
}

$pdf=new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor('GPBmbH');
$pdf->setTitle('GPB Qualifizierungsangebot '.$interessent->vorname.' '.$interessent->nachname.' '.date('d.m.Y'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15,10,15,true);
$pdf->SetAutoPageBreak(false,10);

foreach($angebote as $ang) {
  $zertif=isset($zertifsById[$ang->zertifid]) ? $zertifsById[$ang->zertifid] : null;
  $massnnr=empty($zertif) ? '' : ($zeitmodell=='Vollzeit' ? $zertif->vollzeit_massnnr : $zertif->teilzeit_massnnr);
  // Berechnung BG-Dauer: genug Spielraum lassen, damit der TN ein isschen später anfangen kann.
  // Bei 1 Wo Angebot: 1 Monat, bei 2 Wo oder 3 Wo: 2 Monate, längeres Angebot: die betroffenen Kalendermonate mit 1 Woche Puffer extra.
  $anzwochen=ceil(($ang->bis+24*60*60-$ang->von)/7/24/60/60);
  $anzmonate=$anzwochen<=1 ? 1 :
             ($anzwochen<=3 ? 2 :
              ((int)date('Y',strtotime('+1 week',$ang->bis))-(int)date('Y',$ang->von))*12+(int)date('m',strtotime('+1 week',$ang->bis))-(int)date('m',$ang->von)+1);
  $ersetzungen=array(
    'INTERESSENTVORNAME INTERESSENTNACHNAME'=>''
    ,'INTERESSENTANREDE'=>''
    ,'INTERESSENTVORNAME'=>''
    ,'INTERESSENTNACHNAME'=>''
    ,'INTERESSENTSTRASSE'=>''
    ,'INTERESSENTPLZ'=>''
    ,'INTERESSENTORT'=>''
    ,'URLAUBSTAGE'=>''
    ,'URLAUB'=>''
    ,'FERIEN'=>''
    ,'FEIERTAGE'=>''
    ,'ARBEITSZEITEN'=>''
    ,'BERATUNGSDATUM'=>date('d.m.Y')
    ,'AUSBILDUNGSPREIS'=>format_preis($ang->preis)
    ,'AUSBILDUNGSANFANG'=>date('d.m.Y',$ang->von)
    ,'AUSBILDUNGSENDE'=>date('d.m.Y',$ang->bis)
    ,'AUSBILDUNGSDAUER'=>$ang->anzahlue
    ,'AUSBILDUNGSMASSNAHMENUMMER'=>(empty($massnnr) ? 'wird nach Bildungsgutschein erteilt' : $massnnr)
    ,'AUSBILDUNGSZERTIFZEITRAUM'=>(empty($zertif) || empty($zertif->zertif_beginn) || $zertif->zertif_beginn=='0000-00-00' || empty($zertif->zertif_ende) || $zertif->zertif_ende=='0000-00-00' ? 'in Zertifizierung' : date('d.m.Y',$zertif->zertif_von).' bis '.date('d.m.Y',$zertif->zertif_bis))
    ,'ZEITMODELL'=>$zeitmodell
    ,'ANZAHLUEPROWOCHE'=>($zeitmodell=='Vollzeit' ? $vollzeit_wochenue : $wochenue)
    ,'WOCHENSTUNDEN'=>str_replace('.',',',(0.75*($zeitmodell=='Vollzeit' ? $vollzeit_wochenue : $wochenue)))
    ,'AUSBILDUNGSMONATE'=>$anzmonate.($anzmonate<=1 ? ' Monat' : ' Monate')
    ,'BERATERNAME'=>$ich->vorname.' '.$ich->nachname
    ,'BERATERTEL'=>$ich->tel
  );
  if($interessent) {
    $ersetzungen['INTERESSENTVORNAME INTERESSENTNACHNAME']=null;
    $ersetzungen['INTERESSENTANREDE']=$interessent->anrede;
    $ersetzungen['INTERESSENTVORNAME']=$interessent->vorname;
    $ersetzungen['INTERESSENTNACHNAME']=$interessent->nachname;
    $ersetzungen['INTERESSENTSTRASSE']=$interessent->strasse;
    $ersetzungen['INTERESSENTPLZ']=$interessent->plz;
    $ersetzungen['INTERESSENTORT']=$interessent->ort;
  }
  $ersetzungen['ARBEITSZEITEN']='<table border="0">';
  foreach($arbeitszeiten as $tag=>$zeit) {
    if($zeit->bis>$zeit->von) {
      $ersetzungen['ARBEITSZEITEN'].="<tr><td width=\"40\">".$tag."</td><td>".$zeit->von." - ".$zeit->bis."</td></tr>";
    }
  }
  $ersetzungen['ARBEITSZEITEN'].='</table>';
  if($ang->urlaubstage>0) {
    $ersetzungen['URLAUBSTAGE']='Innerhalb der Laufzeit '.$ang->urlaubstage.' Urlaubstage<br />';
  }
  if(!empty($ang->betroffenerUrlaub)) {
    $ersetzungen['URLAUB']="<br />\n<b>Individuell abgesprochener Urlaub:</b><br />\n<br />\n";
    foreach($ang->betroffenerUrlaub as $u) {
      $ersetzungen['URLAUB'].="&nbsp; &nbsp; ".date('d.m.Y',$u->von)
        .(empty($u->ende) || $u->ende=='0000-00-00' || $u->ende==$u->beginn ? '' : ' - '.date('d.m.Y',$u->bis))
        ."<br />\n";
    }
  }
  if($ang->feiertage>0) {
    $ersetzungen['FEIERTAGE']='Innerhalb der Laufzeit '.$ang->feiertage.' Schließtage<br />';
  }
  if(!empty($ang->betroffeneFerien)) {
    $ersetzungen['FERIEN']="<br />\n<b>Schließtage:</b><br />\n<br />\n";
    foreach($ang->betroffeneFerien as $f) {
      $ersetzungen['FERIEN'].=date('d.m.Y',$f->von)
        .(empty($f->ende) || $f->ende=='0000-00-00' || $f->ende==$f->beginn ? '' : ' - '.date('d.m.Y',$f->bis))
        ." ".$f->art
        ."<br />\n";
    }
  }
  
  $inhalt=file_get_contents('vorlagen/QualifizierungsangebotIntrain.html');
  foreach($ersetzungen as $k=>$v) {
    if($v===null) continue;
    $inhalt=str_replace($k,$v,$inhalt);
  }
  
  $i0=mb_strpos($inhalt,'<ul id="module">')+mb_strlen('<ul id="module">');
  $i1=mb_strpos($inhalt,'</ul>',$i0);
  $modultext=mb_substr($inhalt,$i0,$i1-$i0);
  $inhaltanfang=mb_substr($inhalt,0,$i0);
  $inhaltende=mb_substr($inhalt,$i1);
  foreach($ang->module as $mod) {
    $mtext=$modultext;
    $mtext=str_replace('MODULTITEL',$mod->titel,$mtext);
    $mtext=str_replace('MODULDAUER',$mod->anzahlue,$mtext);
    $mtext=str_replace('MODULPREIS',format_preis($mod->preis),$mtext);
    $mtext=str_replace('MODULKATALOGNUMMER',$mod->katalognummer,$mtext);
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
  $i0=mb_strpos($inhalt,'<section id="seite3">')+mb_strlen('<section id="seite3">');
  $i1=mb_strpos($inhalt,'</section>',$i0);
  $seite3=mb_substr($inhalt,$i0,$i1-$i0);
  
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
  
  $pdf->AddPage();
  $pdf->SetFont('DejaVuSans','',11);
  $pdf->WriteHTML($header,false,false,false,false,'C');
  $pdf->SetFont('DejaVuSans','',11);
  $pdf->WriteHTML($seite3,false,false,false,false,'C');
  $pdf->SetXY(0,265);
  $pdf->SetFont('DejaVuSans','',8);
  $pdf->WriteHTML($footer,false,false,false,false,'C');
}

if(!empty($ich->signaturbild) && file_exists('signaturbilder/'.$ich->signaturbild)) {
  for($n=$pdf->getNumPages(),$i=1;$i<=$n;$i+=3) {
    $pdf->setPage($i);
    $pdf->Image('signaturbilder/'.$ich->signaturbild,50,175,40);
  }
}

$pdf->Output('GPB Qualifizierungsangebot '.$interessent->vorname.' '.$interessent->nachname.' '.date('d.m.Y').'.pdf','D');
?>