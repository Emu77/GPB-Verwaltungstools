<?php
require_once 'check_login.php';

if(!$ich->berichtsheft_offen) {
  header('Location:kurse.php');
  exit;
}

$datum=isset($_GET['datum']) ? $_GET['datum'] : false;
$von=isset($_GET['von']) ? $_GET['von'] : false;
$bis=isset($_GET['bis']) ? $_GET['bis'] : false;
if(!empty($datum)) {
  $von=$bis=$datum;
}
$beginn=strtotime($von);
$ende=strtotime($bis);
if($beginn<=0 || $ende<=0) {
  header('Location:berichtsheft.php');
  exit;
}
if(date('D',$beginn)!='Mon') {
  $beginn=strtotime('last monday',$beginn);
}
if(date('D',$ende)!='Sun') {
  $ende=strtotime('next sunday',$ende);
}
if($beginn>$ende) {
  header('Location:berichtsheft.php');
  exit;
}

$tagnamen=array('Mon'=>'Montag','Tue'=>'Dienstag','Wed'=>'Mittwoch','Thu'=>'Donnerstag','Fri'=>'Freitag','Sat'=>'Samstag','Sun'=>'Sonntag');

$eintraegeByDatum=array();
$result=$db->query("select * from gpb_berichtsheft where tnid=".$ich->id." and datum>='".date('Y-m-d',$beginn)."' and datum<='".date('Y-m-d',$ende)."' order by datum");
while($row=$result->fetch_object()) {
  $eintraegeByDatum[$row->datum]=$row;
}
$result->free();

$vorschlaegeByDatum=array();

$kurseByKw=array();
$kurseById=array();
$result=$db->query("select ku.*
  from gpb_kurs_view ku
  join gpb_kurs_klasse kukl on kukl.kursid=ku.id
  join gpb_klasse_tn kltn on kltn.klasseid=kukl.klasseid
  where ku.beginn is not null and ku.beginn<>'0000-00-00' and ku.beginn<='".date('Y-m-d',$ende)."'
    and ku.ende is not null and ku.ende<>'0000-00-00' and ku.ende>='".date('Y-m-d',$beginn)."'
    and kltn.tnid=".$ich->id." and kltn.einstieg is not null and kltn.einstieg<>'0000-00-00' 
    and kltn.einstieg<=ku.ende and (kltn.ausstieg is null or kltn.ausstieg='0000-00-00' or kltn.ausstieg>=ku.beginn)");
while($row=$result->fetch_object()) {
  $row->dozentname='';
  $kurseById[$row->id]=$row;
  $b=strtotime($row->beginn);
  for($t=date('D',$b)=='Mon' ? $b : strtotime('last monday',$b),$e=strtotime($row->ende);$t<=$e;$t=strtotime('+1 week',$t)) {
    $kurseByKw[date('Y-W',$t)]=$row;
  }
}
$result->free();
if(!empty($kurseById)) {
  $result=$db->query("select kd.kursid,concat(d.vorname,' ',d.nachname) as dozentname
    from gpb_kurs_dozent kd
    join gpb_dozent d on d.id=kd.dozentid
    where kd.kursid in(".implode(',',array_keys($kurseById)).")");
  while($row=$result->fetch_object()) {
    $kurseById[$row->kursid]->dozentname.=(empty($kurseById[$row->kursid]->dozentname) ? "" : ", ").$row->dozentname;
  }
  $result->free();
  
  $result=$db->query("select * from gpb_kurs_tagesbericht where kursid in(".implode(',',array_keys($kurseById)).") and tag>='".date('Y-m-d',$beginn)."' and tag<='".date('Y-m-d',$ende)."'");
  while($row=$result->fetch_object()) {
    if(isset($vorschlaegeByDatum[$row->tag])) {
      $vorschlag=$vorschlaegeByDatum[$row->tag];
    } else {
      $vorschlag=$vorschlaegeByDatum[$row->tag]=(object)array(
        'beginn'=>'',
        'ende'=>'',
        'pausen'=>'',
        'was'=>''
      );
    }
    $tagname=$tagnamen[date('D',strtotime($row->tag))];
    if($tagname=='Freitag') {
      $vorschlag->beginn='08:15';
      $vorschlag->ende='13:15';
      $vorschlag->pausen=0.5;
    } else if($tagname!='Samstag' && $tagname!='Sonntag') {
      $vorschlag->beginn='08:15';
      $vorschlag->ende='16:15';
      $vorschlag->pausen=1;
    }
    if(!empty($row->themen)) {
      $vorschlag->was.=(empty($vorschlag->was) ? "" : "\n").$row->themen;
    }
    if(!empty($row->kguil)) {
      $vorschlag->was.=(empty($vorschlag->was) ? "" : "\n").$row->kguil;
    }
  }
  $result->free();
}

$ferien=array();
$result=$db->query("select * from gpb_ferien where (art='Feiertag' or art='GPB Ferien') and beginn<='".date('Y-m-d',$ende)."' and ende>='".date('Y-m-d',$beginn)."' order by beginn,ende,art");
while($row=$result->fetch_object()) {
  $ferien[]=$row;
}
$result->free();
$result=$db->query("select * 
  from (select f.*
    ,case when ktn.einstieg is null or ktn.einstieg='0000-00-00' then k.beginn else ktn.einstieg end as einstieg
    ,case when ktn.ausstieg is null or ktn.ausstieg='0000-00-00' then k.ende else ktn.ausstieg end as ausstieg
  from gpb_ferien f
  join gpb_klasse k on f.art='Institutsferien' and k.ort=f.ort
  join gpb_klasse_tn ktn on ktn.tnid=".$ich->id." and ktn.klasseid=k.id and ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null) a
  where a.beginn<=a.ausstieg and a.ende>=a.einstieg and a.beginn<='".date('Y-m-d',$ende)."' and a.ende>='".date('Y-m-d',$beginn)."'
  order by a.beginn,a.ende,a.art");
while($row=$result->fetch_object()) {
  $ferien[]=$row;
}
$result->free();
$result=$db->query("select * 
  from (select f.*
    ,case when ktn.einstieg is null or ktn.einstieg='0000-00-00' then k.beginn else ktn.einstieg end as einstieg
    ,case when ktn.ausstieg is null or ktn.ausstieg='0000-00-00' then k.ende else ktn.ausstieg end as ausstieg
    ,kf.klasseid
  from gpb_ferien f
  join gpb_klasse_ferien kf on f.art='Klassenferien' and kf.ferienid=f.id
  join gpb_klasse_tn ktn on ktn.tnid=".$ich->id." and ktn.klasseid=kf.klasseid and ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null
  join gpb_klasse k on k.id=ktn.klasseid) a
  where a.beginn<=a.ausstieg and a.ende>=a.einstieg and a.beginn<='".date('Y-m-d',$ende)."' and a.ende>='".date('Y-m-d',$beginn)."'
  order by a.beginn,a.ende,a.art");
while($row=$result->fetch_object()) {
  $ferien[]=$row;
}
$result->free();
foreach($ferien as $f) {
  for($t=max($beginn,strtotime($f->beginn)),$e=min($ende,strtotime($f->ende));$t<=$e;$t=strtotime('+1 day',$t)) {
    $tagname=$tagnamen[date('D',$t)];
    if($tagname!='Samstag' && $tagname!='Sonntag') {
      $tag=date('Y-m-d',$t);
      if(isset($vorschlaegeByDatum[$tag])) {
        $vorschlag=$vorschlaegeByDatum[$tag];
      } else {
        $vorschlag=$vorschlaegeByDatum[$tag]=(object)array(
          'beginn'=>'',
          'ende'=>'',
          'pausen'=>'',
          'was'=>''
        );
      }
      $vorschlag->was=$f->anlass.(empty($vorschlag->was) ? "" : "\n".$vorschlag->was);
    }
  }
}

$result=$db->query("select * from gpb_tn_view tn left outer join gpb_beruf b on b.id=tn.berufid where tn.id=".$ich->id." limit 1");
$tn=$result->fetch_object();
$result->free();

$titel='Berichtsheft '.$tn->vorname.' '.$tn->nachname;
$untertitel='Stand '.date('d.m.Y');
$dateiname=$titel.' '.date('d.m.Y',$beginn).' bis '.date('d.m.Y',$ende).' - '.$untertitel;

require_once '../tcpdf/tcpdf.php';

$mm2pt=2.83465;
$x0=46;
//$wrandr=43;

$pdf=new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor($tn->tnname);
$pdf->setTitle($dateiname);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(true,10);

for($woche=$beginn;$woche<=$ende;$woche=strtotime('+1 week',$woche)) {
  $kw=date('Y-W',$woche);
  $kurs=isset($kurseByKw[$kw]) ? $kurseByKw[$kw] : null;
  
  $pdf->AddPage();
  $pdf->Image('../GPBLogo.png',154,15,40);

  $pdf->Image('../Quadrate.png',$x0,15,16);
  $pdf->SetFont('DejaVuSans','B',$mm2pt*10);
  $pdf->setColor('text',234,184,15);
  $pdf->SetXY($x0+19,38.5);
  $pdf->Cell(0,0,'Berichtsheft');

  $pdf->SetFont('DejaVuSans','B',$mm2pt*6);
  $pdf->setColor('text',0,0,0);
  $pdf->SetXY($x0,59);
  $pdf->Cell(0,0,$tn->tnname);
  
//  $bez=$tn->bezeichnung;
//  if($tn->anrede=='Frau' && !empty($tn->bezeichnungfrau)) {
//    $bez=$tn->bezeichnungfrau;
//  } else if($tn->anrede=='Herr' && !empty($tn->bezeichnungmann)) {
//    $bez=$tn->bezeichnungmann;
//  }
//  $pdf->SetFont('DejaVuSans','B',$mm2pt*6);
//  $pdf->SetXY($x0,142);
//  $anz=$pdf->MultiCell(210-$x0-$wrandr,0,$bez,0,'L',false,2,null,null,false,0,false,false,0,'T',false);
  
//  $pdf->SetFont('DejaVuSans','B',$mm2pt*6);
//  $pdf->setColor('text',0,0,0);
//  $pdf->SetXY($x0,80);
//  $pdf->Cell(0,0,'KW '.date('W',$woche).'     '.date('d.m.Y',$woche).'-'.date('d.m.Y',strtotime('+6 days',$woche)));
  
  $leer=(object)array(
    'beginn'=>'','ende'=>'','pausen'=>'','was'=>''
  );
  
  $html='<table border="1" cellpadding="1">
    <tr>
      <th colspan="5"><br /><br />
        <font size="'.($mm2pt*4).'"><b>KW '.date('W',$woche).'     '.date('d.m.Y',$woche).'-'.date('d.m.Y',strtotime('+6 days',$woche)).'</b></font><br />
        '.($kurs ? '<font size="'.($mm2pt*4).'">Unterricht: '.(empty($kurs->modultitel) ? $kurs->titel : $kurs->modultitel)
        .(empty($kurs->dozentname) ? '' : '<br />Dozent: '.$kurs->dozentname).'</font><br />' : '').'
      </th>
    </tr>';
  for($t=0;$t<7;++$t) {
    $tag=strtotime('+'.$t.' days',$woche);
    $datum=date('Y-m-d',$tag);
    $eintrag=isset($eintraegeByDatum[$datum]) ? $eintraegeByDatum[$datum] : $leer;
    if($t>=5 && empty($eintrag->was)) continue;
    $vorschlag=isset($vorschlaegeByDatum[$datum]) ? $vorschlaegeByDatum[$datum] : $leer;
    $html.='<tr>
        <th rowspan="4" width="63"><b>'.$tagnamen[date('D',$tag)].'</b></th>
        <td rowspan="4" width="56">'.date('d.m.Y',$tag).'</td>
        <td width="38">Von&nbsp;</td>
        <td width="34">'.substr(empty($eintrag->beginn) ? $vorschlag->beginn : $eintrag->beginn,0,5).'</td>
        <td rowspan="4" width="*" align="left">'.nl2br(empty($eintrag->was) ? $vorschlag->was : $eintrag->was).'</td>
      </tr>
      <tr>
        <td>Bis&nbsp;</td>
        <td>'.substr(empty($eintrag->ende) ? $vorschlag->ende : $eintrag->ende,0,5).'</td>
      </tr>
      <tr>
        <td>Pausen&nbsp;</td>
        <td>'.(empty($eintrag->pausen) ? (empty($vorschlag->pausen) ? '' : $vorschlag->pausen.' h') : $eintrag->pausen.' h').'</td>
      </tr>
      <tr>
        <td></td>
        <td></td>
      </tr>';
  }
  $html.='</table>';
  $html.='<br /><br /><br /><br />';
  $html.='<table><tr><td align="left"><b>Bemerkungen des Ausbilders</b></td></tr></table>';
  $html.='<table border="1" style="width:100%;"><tr><td><br /><br /><br /><br /><br /></td></tr></table>';
  $html.='<br /><br />';
  $html.='<table><tr><td align="left"><b>Unterschriften</b></td></tr></table>';
  $html.='<table style="width:100%;"><tr><td width="50%" style="font-size:7;" align="left">Für die Richtigkeit aller Angaben<br />Auszubildende/r, Unterschrift und Datum</td>';
  $html.='<td style="font-size:7;" align="left">Für die Richtigkeit der Angaben zur Betrieblichen Ausbildung<br />Betrieblicher Ausbilder/in, Unterschrift und Datum</td></tr></table>';
  $html.='<table border="1" style="width:100%;"><tr><td width="50%"><br /><br /><br /><br /><br /></td><td><br /><br /><br /><br /><br /></td></tr></table>';
  
  $pdf->SetXY(10,80);
  $pdf->SetFont('DejaVuSans','',$mm2pt*3);
  $pdf->WriteHTML($html,false,false,false,false,'C');
  
  $pdf->SetFont('DejaVuSans','',$mm2pt*4);
  $pdf->setColor('text',0,0,0);
  $pdf->SetXY($x0+45,280);
  $pdf->Cell(0,0,$untertitel);
}

$pdf->Output($dateiname.'.pdf','D');
exit;
?>