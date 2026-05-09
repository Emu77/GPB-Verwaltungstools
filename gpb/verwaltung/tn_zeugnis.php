<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungTn.php';
require_once 'VerwaltungKurs.php';
require_once '../tcpdf/tcpdf.php';

$tn=VerwaltungTn::einenLaden(isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0,'VerwaltungTn');
if(empty($tn)) {
  header('Location:tn.php');
  exit;
}
$gender=isset($_GET['gender']) && $_GET['gender']!='N';

// Zeitraum laden
$result=$db->query("select min(mtn.einstieg) as einstieg,max(mtn.ausstieg) as ausstieg,max(m.ende) as ende
  from gpb_massnahme_tn mtn
  join gpb_massnahme m on m.id=mtn.massnahmeid
  where mtn.tnid=".$tn->id);
$zeitraum=$result->fetch_object();
$result->free();
// Beruf laden
$result=$db->query("select b.* from gpb_beruf b where b.id=".$tn->berufid);
$beruf=$result->fetch_object();
$result->free();

$kurse=new Liste('VerwaltungKurs',$db->prepare("
  select distinct k.*,n.*
    ,case when k.zeugnisgewichtung>=0 then k.zeugnisgewichtung else case when k.einzeltage then ceil((datediff(k.ende,k.beginn)+1)/7) else ceil(datediff(k.ende,k.beginn)/7) end end as kursdauer
    from gpb_klasse_tn ktn 
    join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
    join gpb_kurs_view k on k.id=kk.kursid
    left outer join gpb_note n on n.kursid=k.id and n.tnid=".$tn->id."
    where k.zeugnisrelevant
    and ktn.tnid=".$tn->id."
    and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
    and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)
    order by beginn,ende,titel"));
Kurs::refsLaden($kurse);

//Kurse nach Modul-ID gruppieren
class Kursgruppe {
  function __construct($ersterkurs) {
    $this->modulid=$ersterkurs->modulid;
    $this->modultitel=$ersterkurs->modultitel;
    $this->kurse=array();
    $this->kursedauer=0;
    $this->notenstatus='keine';
    $this->punktesumme=0;
    $this->punktedauer=0;
    $this->addKurs($ersterkurs);
  }
  function addKurs($kurs) {
    $this->kurse[]=$kurs;
    $this->kursedauer+=$kurs->kursdauer;
    if($kurs->notenstatus=='keine') return;
    if($kurs->notenstatus=='todo') {
      if($this->notenstatus=='keine') {
         $this->notenstatus='todo';
      }
      return;
    }
    if(!$kurs->inauszugsichtbar) {
      $this->notenstatus='unsichtbar';
      return;
    }
    if(empty($kurs->note) && empty($kurs->nachnote)) return;
    if(empty($kurs->nachnote) && $kurs->fehlt) {
      return; 
    }
    // der Kurs wird in die Modul-Note aufgenommen!
    if($this->notenstatus!='unsichtbar') {
      $this->notenstatus='ok';
    }
    $kurs->punkte=$kurs->nachnote>0 ? $kurs->nachnote : $kurs->note;
    $this->punktesumme+=$kurs->punkte*$kurs->kursdauer;
    $this->punktedauer+=$kurs->kursdauer;
    // Modul-Durchschnitt aktualisieren
    $this->punkte=$this->punktesumme/$this->punktedauer;
  }
}
$kursgruppen=array();
$kursgruppenByModulid=array();
foreach($kurse->alle as $k) {
  if(isset($kursgruppenByModulid[$k->modulid])) {
    $kursgruppenByModulid[$k->modulid]->addKurs($k);
  } else {
    $kg=new Kursgruppe($k);
    $kursgruppen[]=$kg;
    $kursgruppenByModulid[$k->modulid]=$kg;
  }
}

//Ausbildungsteile der Klassen holen, wo der TN angemeldet ist
// + einen Teil für die Kurse ohne Modul und die Module, die nicht im Beruf des TNs sind
class Ausbildung {
  function __construct() {
    $this->kursgruppen=array();
    $this->kursedauer=0;
    $this->punktesumme=0;
    $this->punktedauer=0;
  }
  function addKursgruppe($kursgruppe) {
    global $kursedauer,$punktesumme,$punktedauer;
    $this->kursgruppen[]=$kursgruppe;
    $this->kursedauer+=$kursgruppe->kursedauer;
    $kursedauer+=$kursgruppe->kursedauer;
    if($kursgruppe->notenstatus=='keine' || $kursgruppe->notenstatus=='todo' || $kursgruppe->notenstatus=='unsichtbar') return;
    if(!isset($kursgruppe->punkte)) return;
    $this->punktesumme+=$kursgruppe->punkte*ceil($kursgruppe->punktedauer);
    $this->punktedauer+=ceil($kursgruppe->punktedauer);
    $punktesumme+=$kursgruppe->punkte*ceil($kursgruppe->punktedauer);
    $punktedauer+=ceil($kursgruppe->punktedauer);
  }
}
$ausbildungen=array();
$ausbildungenById=array();
$result=$db->query("select distinct b.*
  ,f.bezeichnung as familiebez
from gpb_klasse_tn ktn
join gpb_klasse k on k.id=ktn.klasseid
join gpb_beruf b on b.id=k.berufid
join gpb_berufsfamilie f on f.id=b.familieid
where ktn.tnid=".$tn->id."
and ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null
order by ktn.einstieg");
while($row=$result->fetch_object('Ausbildung')) {
  $ausbildungen[]=$row;
  $ausbildungenById[$row->id]=$row;
}
$result->free();
$zusaetzlich=new Ausbildung();
$zusaetzlich->bezeichnung='ZUSÄTZLICHE MODULE';
$zusaetzlich->bezeichnungfrau='';
$zusaetzlich->bezeichnungmann='';
$zusaetzlich->kuerzel='';
$ausbildungen[]=$zusaetzlich;

$kursedauer=0;
$punktesumme=0;
$punktedauer=0;
foreach($kursgruppen as $kg) {
  if(empty($kg->modulid) || empty($ausbildungenById)) {
    $ausb=$zusaetzlich;
  } else {
    // Modul mit diesem Titel in den Berufen des TN suchen
    $stmt=$db->prepare("select distinct b.id
      from gpb_modul m
      join gpb_beruf_modul bm on bm.modulid=m.id and bm.berufid in(".implode(',',array_keys($ausbildungenById)).")
      join gpb_beruf b on b.id=bm.berufid
      where m.titel=?");
    $stmt->bind_param('s',$kg->modultitel);
    $stmt->execute();
    $result=$stmt->get_result();
    $row=$result->fetch_object();
    $result->free();
    if($row && isset($ausbildungenById[$row->id])) {
      $ausb=$ausbildungenById[$row->id];
    } else {
      $ausb=$zusaetzlich;
    }
  }
  $ausb->addKursgruppe($kg);
}

$anzahlZeilen=0;
foreach($ausbildungen as $ausb) {
  $ausb->anzahlZeilen=0;
  if(empty($ausb->kursgruppen)) continue;
  $ausb->anzahlZeilen; //1 oder 2 für den Titel der Ausbildung
  $anzahlZeilen+=2; 
  foreach($ausb->kursgruppen as $kg) {
    if($kg->modulid<=0) { // Kurse ohne Modul werden nicht gruppiert
      foreach($kg->kurse as $kurs) {
        $ausb->anzahlZeilen+=2; //und haben lange Titel
        $anzahlZeilen+=2;
      }
    } else {
      $ausb->anzahlZeilen+=1;
      $anzahlZeilen+=1;
    }
  }
}
$maxAnzahlZeilen=32;
$anzahlSeiten=2;

$mm2pt=2.83465;
$x0=46;
$wrandr=43;
$xpunkte=210-$wrandr;

$pdf=new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor('GPBmbH');
$pdf->setTitle('GPB Zeugnis '.($gender ? $tn->tnname : $tn->mitisvorname.' '.$tn->nachname).' am '.date('d.m.Y'));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->AddPage();
$pdf->Image('../GPBLogo.png',154,15,40);

$pdf->Image('../Quadrate.png',$x0,53,16);
$pdf->SetFont('DejaVuSans','B',$mm2pt*10);
$pdf->setColor('text',234,184,15);
$pdf->SetXY($x0+19,76.5);
$pdf->Cell(0,0,'Zeugnis');

$pdf->SetFont('DejaVuSans','B',$mm2pt*6);
$pdf->setColor('text',0,0,0);
$pdf->SetXY($x0,97);
$pdf->Cell(0,0,$gender ? $tn->tnname : $tn->mitisvorname.' '.$tn->nachname);

$pdf->SetFont('DejaVuSans','',$mm2pt*4);
$pdf->SetXY($x0,112);
$pdf->Cell(0,0,'geboren am '.date('d.m.Y',strtotime($tn->geburtsdatum)));

$pdf->SetXY($x0,127);
$pdf->Cell(0,0,'nimmt in der Zeit vom '.date('d.m.Y',strtotime($zeitraum->einstieg)).' bis zum '.date('d.m.Y',strtotime(empty($zeitraum->ausstieg) || $zeitraum->ausstieg=='0000-00-00' ? $zeitraum->ende : $zeitraum->ausstieg)),0,2,'L');
$pdf->Cell(0,0,'an der Qualifizierung:');

$bez=$beruf->bezeichnung;
if(($gender ? $tn->anrede : $tn->mitisanrede)=='Frau' && !empty($beruf->bezeichnungfrau)) {
  $bez=$beruf->bezeichnungfrau;
} else if(($gender ? $tn->anrede : $tn->mitisanrede)=='Herr' && !empty($beruf->bezeichnungmann)) {
  $bez=$beruf->bezeichnungmann;
}
$pdf->SetFont('DejaVuSans','B',$mm2pt*6);
$pdf->SetXY($x0,142);
$anz=$pdf->MultiCell(210-$x0-$wrandr,0,$bez,0,'L',false,2,null,null,false,0,false,false,0,'T',false);
$pdf->SetFont('DejaVuSans','',$mm2pt*4);
$pdf->SetXY($x0,142+$anz*6+5);
$pdf->Cell(0,0,'teil.');

$pdf->SetXY($x0,175);
$txt='Bis zum ';
$w=$pdf->GetStringWidth($txt);
$pdf->Cell($w,0,$txt,0,0,'L');
$pdf->SetFont('DejaVuSans','B',$mm2pt*4);
$pdf->Cell(0,0,date('d.m.Y'));
$pdf->SetFont('DejaVuSans','',$mm2pt*4);
$pdf->SetXY($x0,181);
$pdf->Cell(210-$x0-$wrandr,0,'wurden folgende Block- und Gesamtbewertungen erreicht:');

$pdf->SetFont('DejaVuSans','B',$mm2pt*4);
$y=194;
foreach($ausbildungen as $ausb) {
  if(empty($ausb->kursgruppen)) {
     continue; 
  }
  if($ausb==$zusaetzlich) {
    $titel=$ausb->bezeichnung;
  } else if(strrchr($ausb->kuerzel,'_')=='_BQ') {
    $titel= 'BERUFSÜBERGREIFENDE QUALIFIKATION'."\n".$ausb->familiebez;
  } else {
    $titel='BERUFSSPEZIFISCHE QUALIFIKATION'."\n".$bez;
  }
  if($ausb->punktedauer>0) {
    $txt=sprintf('%d',$ausb->punktesumme/$ausb->punktedauer).' Punkte';
    $w=$pdf->GetStringWidth($txt);
    $pdf->SetXY($x0,$y);
    $anz=$pdf->MultiCell($xpunkte-$w-4-$x0,0,$titel,0,'L',false,2);
    $pdf->SetXY($xpunkte-$w,$y);
    $pdf->Cell(0,4,$txt);
  } else {
    $w=$pdf->GetStringWidth('100 Punkte');
    $pdf->SetXY($x0,$y);
    $anz=$pdf->MultiCell($xpunkte-$w-4-$x0,0,$titel,0,'L',false,2);
    $pdf->SetXY($xpunkte-$w,$y);
    $pdf->Cell($w,4,'****',0,0,'C');
  }
  
  $y+=$anz*4+4;
}

$y+=4;
$pdf->SetFont('DejaVuSans','B',$mm2pt*6);
$pdf->SetXY($x0,$y);
$anz=$pdf->MultiCell($xpunkte-$w-4-$x0,0,'Gesamt',0,'L',false,2);
if($punktedauer>0) {
  $txt=sprintf('%d',$punktesumme/$punktedauer).' Punkte';
  $w=$pdf->GetStringWidth($txt);
  $pdf->SetXY($xpunkte-$w,$y);
  $pdf->Cell(0,0,$txt);
  $y+=$anz*6+6;
}


$x0=20;
$wrandr=20;
$wpunkte=25;
$wdauer=20;
$xpunkte=210-$wrandr;
$xdauer=$xpunkte-$wpunkte-4;
$wtitel=$xdauer-$wdauer-4-$x0;

$pdf->AddPage();

$pdf->Image('../Quadrate.png',$x0,20,10);
$pdf->SetFont('DejaVuSans','B',$mm2pt*8);
$pdf->setColor('text',234,184,15);
$pdf->SetXY($x0+12,33);
$pdf->Cell(0,0,'Zeugnis');

$y=55;
$pdf->SetFont('DejaVuSans','B',$mm2pt*4);
$pdf->setColor('text',0,0,0);
$pdf->SetXY($x0,$y);
$pdf->Cell(0,0,$gender ? $tn->tnname : $tn->mitisvorname.' '.$tn->nachname);
$pdf->SetXY($xdauer-$wdauer,$y);
$pdf->Cell($wdauer,0,'Dauer',0,0,'C');
$pdf->SetXY($xpunkte-$wpunkte,$y);
$pdf->Cell($wpunkte,0,'Ergebnisse',0,0,'C');

$y+=8;

$pdf->SetFont('DejaVuSans','B',$mm2pt*4);
$wtextwochen=$pdf->GetStringWidth(' Wochen');
$pdf->SetFont('DejaVuSans','',$mm2pt*4);
$wtextpunkte=$pdf->GetStringWidth(' Punkte');

foreach($ausbildungen as $ausb) {
  if(empty($ausb->kursgruppen)) continue;
  if($ausb->anzahlZeilen+3>$maxAnzahlZeilen) { //+3 für Gesamt
    
    $y=257;
    $pdf->SetFont('DejaVuSans','',$mm2pt*3.5);
    $pdf->SetXY($x0,$y);
    $anz=$pdf->MultiCell(210-$wrandr-$x0,0,'Punktesystem: 100-92 = sehr gut, 91-81 = gut, 80-67 = befriedigend, 66-50 = ausreichend,'
    ."\n".'                         49-30 = mangelhaft, 29-0 ungenügend'
    ."\n".'**** ohne/noch ohne Bewertung'
    ."\n".'Die Punktzahlen der Block- und Gesamtbewertung werden mittels der Wochenzahlen gewichtet',0,'L',false,2);
    
    $pdf->AddPage();
    $anzahlSeiten=3;

    $pdf->Image('../Quadrate.png',$x0,20,10);
    $pdf->SetFont('DejaVuSans','B',$mm2pt*8);
    $pdf->setColor('text',234,184,15);
    $pdf->SetXY($x0+12,33);
    $pdf->Cell(0,0,'Zeugnis');
    
    $y=55;
    $pdf->SetFont('DejaVuSans','B',$mm2pt*4);
    $pdf->setColor('text',0,0,0);
    $pdf->SetXY($x0,$y);
    $pdf->Cell(0,0,$gender ? $tn->tnname : $tn->mitisvorname.' '.$tn->nachname);
    $pdf->SetXY($xdauer-$wdauer,$y);
    $pdf->Cell($wdauer,0,'Dauer',0,0,'C');
    $pdf->SetXY($xpunkte-$wpunkte,$y);
    $pdf->Cell($wpunkte,0,'Ergebnisse',0,0,'C');
    
    $y+=8;
    
    $maxAnzahlZeilen=32;
  }
  $pdf->SetFont('DejaVuSans','B',$mm2pt*4);
  $y+=2;
  $pdf->SetXY($x0,$y);
  if($ausb==$zusaetzlich) {
    $titel=$ausb->bezeichnung;
  } else if(strrchr($ausb->kuerzel,'_')=='_BQ') {
    $titel= 'BERUFSÜBERGREIFENDE QUALIFIKATION'."\n".$ausb->familiebez;
  } else {
    $titel='BERUFSSPEZIFISCHE QUALIFIKATION'."\n".$bez;
  }
  $anz=$pdf->MultiCell($wtitel,0,$titel,0,'L',false,2);
  $pdf->SetXY($xdauer-$wtextwochen-$pdf->GetStringWidth($ausb->kursedauer),$y);
  $pdf->Cell(0,0,$ausb->kursedauer.($ausb->kursedauer>1 ? ' Wochen' : ' Woche'));
  if($ausb->punktedauer>0) {
    $pdf->SetXY($xpunkte-$wtextpunkte-$pdf->GetStringWidth(round($ausb->punktesumme/$ausb->punktedauer)),$y);
    $pdf->Cell(0,0,round($ausb->punktesumme/$ausb->punktedauer).' Punkte');
  } else {
    $pdf->SetXY($xpunkte-$wtextpunkte,$y);
    $pdf->Cell(0,0,' ****');
  }
  
  $y+=$anz*6;
  $maxAnzahlZeilen-=$anz;
  $pdf->SetFont('DejaVuSans','',$mm2pt*4);
  foreach($ausb->kursgruppen as $kg) {
    if($kg->modulid<=0) { // Kurse ohne Modul werden nicht gruppiert
      foreach($kg->kurse as $kurs) {
        $pdf->SetXY($x0,$y);
        $anz=$pdf->MultiCell($wtitel,0,$kurs->titel,0,'L',false,2);
        $pdf->SetXY($xdauer-$wtextwochen-$pdf->GetStringWidth(ceil($kurs->kursdauer)),$y);
        $pdf->Cell(0,0,ceil($kurs->kursdauer).(ceil($kurs->kursdauer)>1 ? ' Wochen' : ' Woche'));
        if(isset($kurs->punkte)) {
          $pdf->SetXY($xpunkte-$wtextpunkte-$pdf->GetStringWidth(round($kurs->punkte)),$y);
          $pdf->Cell(0,0,round($kurs->punkte).' Punkte');
        } else {
          $pdf->SetXY($xpunkte-$wtextpunkte,$y);
          $pdf->Cell(0,0,' ****');
        }
        $y+=$anz*6;
        $maxAnzahlZeilen-=$anz;
      }
    } else {
      $pdf->SetXY($x0,$y);
      $anz=$pdf->MultiCell($wtitel,0,empty($kg->modultitel) ? 'Modul ohne Titel' : $kg->modultitel,0,'L',false,2);
      $pdf->SetXY($xdauer-$wtextwochen-$pdf->GetStringWidth(ceil($kg->kursedauer)),$y);
      $pdf->Cell(0,0,ceil($kg->kursedauer).(ceil($kg->kursedauer)>1 ? ' Wochen' : ' Woche'));
      if(isset($kg->punkte)) {
        $pdf->SetXY($xpunkte-$wtextpunkte-$pdf->GetStringWidth(round($kg->punkte)),$y);
        $pdf->Cell(0,0,round($kg->punkte).' Punkte');
      } else {
        $pdf->SetXY($xpunkte-$wtextpunkte,$y);
        $pdf->Cell(0,0,' ****');
      }
      $y+=$anz*6;
      $maxAnzahlZeilen-=$anz;
    }
  }
}
$y+=4;
$pdf->SetFont('DejaVuSans','B',$mm2pt*4);
$pdf->SetXY($x0,$y);
$anz=$pdf->MultiCell($wtitel,0,'Gesamt',0,'L',false,2);
$pdf->SetXY($xdauer-$wtextwochen-$pdf->GetStringWidth($kursedauer),$y);
$pdf->Cell(0,0,$kursedauer.($kursedauer>1 ? ' Wochen' : ' Woche'));
if($punktedauer>0) {
  $pdf->SetXY($xpunkte-$wtextpunkte-$pdf->GetStringWidth(round($punktesumme/$punktedauer)),$y);
  $pdf->Cell(0,0,round($punktesumme/$punktedauer).' Punkte');
} else {
  $pdf->SetXY($xpunkte-$wtextpunkte,$y);
  $pdf->Cell(0,0,' ****');
}

$y+=$anz*6;
$maxAnzahlZeilen-=$anz;

$y=257;
$pdf->SetFont('DejaVuSans','',$mm2pt*3.5);
$pdf->SetXY($x0,$y);
$anz=$pdf->MultiCell(210-$wrandr-$x0,0,'Punktesystem: 100-92 = sehr gut, 91-81 = gut, 80-67 = befriedigend, 66-50 = ausreichend,'
."\n".'                         49-30 = mangelhaft, 29-0 ungenügend'
."\n".'**** ohne/noch ohne Bewertung'
."\n".'Die Punktzahlen der Block- und Gesamtbewertung werden mittels der Wochenzahlen gewichtet',0,'L',false,2);

$pdf->Output('GPB Zeugnis '.($gender ? $tn->tnname : $tn->mitisvorname.' '.$tn->nachname).' am '.date('d.m.Y').'.pdf','D');
exit;
?>