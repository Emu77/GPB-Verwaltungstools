<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'LeitungKurs.php';
require_once '../Bewertung.php';

$defaultbis=strtotime('last sunday');
$defaultvon=date('Y-m-d',strtotime('-1 week',$defaultbis));
$defaultbis=date('Y-m-d',$defaultbis);
$suche=isset($_SESSION['bewertung_kurse_suche']) ? $_SESSION['bewertung_kurse_suche'] : new Suche('am','beginn_von','beginn_bis','ende_von','ende_bis','titel','modultitel','ort','raum','ohneraum','klassebez','dozentname','moodleid','todo');
if(empty($suche->where)) {
  $suche->ende_von=$defaultvon;
  $suche->addKriterium("ende>=?",$defaultvon,'s');
  $suche->ende_bis=$defaultbis;
  $suche->addKriterium("ende<=?",$defaultbis,'s');
}

$kurse=new Liste('LeitungKurs',$suche->prepare("select * from gpb_kurs_view where","order by beginn,ende,modultitel,titel limit 50"));
Kurs::refsLaden($kurse);
LeitungKurs::massnahmenLaden($kurse);

$werte=array('summe'=>0);
$anzahlen=array('summe'=>0);
if(!empty($kurse->byId)) {
  $result=$db->query("select * from gpb_bewertung where not stumm and kursid in(".implode(',',array_keys($kurse->byId)).")");
  while($row=$result->fetch_object()) {
    $kurs=$kurse->byId[$row->kursid];
    if($row->wert>0 || !empty($row->feedback)) {
      if(!isset($kurs->anzahlen)) {
        $kurs->anzahlen=array('summe'=>0);
      }
      if(isset($kurs->anzahlen[$row->frage])) {
        $kurs->anzahlen[$row->frage]++;
      } else {
        $kurs->anzahlen[$row->frage]=1;
      }
      if(isset($anzahlen[$row->frage])) {
        $anzahlen[$row->frage]++;
      } else {
        $anzahlen[$row->frage]=1;
      }
    }
    if($row->frage=='info' && !empty($row->feedback)) {
      if(isset($kurs->feedbacks)) {
        $kurs->feedbacks[]=$row->feedback;
      } else {
        $kurs->feedbacks=array($row->feedback);
      }
    } else if($row->frage!='info' && $row->wert>0) {
      if(!isset($kurs->bewerterids)) {
        $kurs->bewerterids=array();
      }
      $kurs->bewerterids[$row->tnid]=true;
      $kurs->anzahlen['summe']++;
      $anzahlen['summe']++;
      if(!isset($kurs->werte)) {
        $kurs->werte=array('summe'=>0);
      }
      if(isset($kurs->werte[$row->frage])) {
        $kurs->werte[$row->frage]+=$row->wert;
      } else {
        $kurs->werte[$row->frage]=$row->wert;
      }
      $kurs->werte['summe']+=$row->wert;
      if(isset($werte[$row->frage])) {
        $werte[$row->frage]+=$row->wert;
      } else {
        $werte[$row->frage]=$row->wert;
      }
      $werte['summe']+=$row->wert;
    }
  }
  $result->free();
}
$anzahlBewerter=0;
foreach($kurse->alle as $kurs) {
  if(isset($kurs->bewerterids)) {
    $anzahlBewerter+=count($kurs->bewerterids);
  }
}

$dateiname='Kursbewertungen'.date('d.m.Y');

$html='<table border="0.5" cellspacing="0" cellpadding="1" style="border-collapse:collapse;">';
$html.='<tr>
    <td width="200" align="left" valign="top"><font size="8"><b>Suchkriterien:';
if(!empty($suche->beginn_von)) {
  $html.='<br />Kursbeginn ab '.date('d.m.Y',strtotime($suche->beginn_von));
}
if(!empty($suche->beginn_bis)) {
  $html.='<br />Kursbeginn bis '.date('d.m.Y',strtotime($suche->beginn_bis));
}
if(!empty($suche->ende_von)) {
  $html.='<br />Kursende ab '.date('d.m.Y',strtotime($suche->ende_von));
}
if(!empty($suche->ende_bis)) {
  $html.='<br />Kursende bis '.date('d.m.Y',strtotime($suche->ende_bis));
}
if(!empty($suche->modultitel)) {
  $html.='<br />Modultitel enthält "'.$suche->modultitel.'"';
}
if(!empty($suche->titel)) {
  $html.='<br />Kurstitel enthält "'.$suche->kurstitel.'"';
}
if(!empty($suche->ort)) {
  $html.='<br />Ort: '.$suche->ort;
}
if(!empty($suche->titel)) {
  $html.='<br />Maßnahmekürzel enthält "'.$suche->massnahmekuerzel.'"';
}
if(!empty($suche->klassebez)) {
  $html.='<br />Klassenbezeichnung enthält "'.$suche->klassebez.'"';
}
if(!empty($suche->dozentname)) {
  $html.='<br />Dozentenname enthält "'.$suche->dozentname.'"';
}
$html.='</font></b></td>';
foreach(Bewertung::$fragen as $frage=>$fragentext) {
  $html.='<td class="bewertungsfrage" width="37">'.$fragentext.'</td>'; //TODO Rotation
}
$html.='<td class="bewertungsfrage" width="37"><b>Alle Fragen zusammen</b></td>';
$html.='<td class="bewertungsfrage" width="*">Feedbacks</td>';
$html.='</tr>';
$html.='<tr class="summen">';
$html.='<td align="right"><b>Durchschnitt</b></td>';
foreach(Bewertung::$fragen as $frage=>$fragentext) {
  $html.='<td align="center" valign="top"><b>'.(isset($anzahlen[$frage]) ? sprintf('%0.1f',$werte[$frage]/$anzahlen[$frage]) : '').'</b></td>';
}
$html.='<td align="center"><b>'.($anzahlen['summe']>0 ? sprintf('%0.1f',$werte['summe']/$anzahlen['summe']) : '').'</b></td>';
$html.='<td><b> </b></td>';
$html.='</tr>';
$html.='<tr class="summen">';
$html.='<td align="right"><b>Anzahl Bewertungen</b></td>';
foreach(Bewertung::$fragen as $frage=>$fragentext) {
  $html.='<td align="center" valign="top"><b>'.(isset($anzahlen[$frage]) ? $anzahlen[$frage] : '').'</b></td>';
}
$html.='<td align="center"><b>'.($anzahlBewerter>0 ? $anzahlBewerter.'&nbsp;TN' : '').'</b></td>';
$html.='<td align="center"><b>'.(isset($anzahlen['info']) ? $anzahlen['info'] : '').'</b></td>';
$html.='</tr>';
foreach($kurse->alle as $kurs) {
  $html.='<tr>';
  $html.='<td valign="top">';
  $html.=''.$kurs->titel.'';
  $html.='<div align="right">'.$kurs->anzahlTN.'&nbsp;TN</div>';
  $html.='</td>';
  foreach(Bewertung::$fragen as $frage=>$fragentext) {
    $html.='<td align="center" valign="top">'.(isset($kurs->anzahlen[$frage]) ? sprintf('%0.1f',$kurs->werte[$frage]/$kurs->anzahlen[$frage]).'<br />'.$kurs->anzahlen[$frage].'&nbsp;('.round(100*$kurs->anzahlen[$frage]/$kurs->anzahlTN).'&nbsp;%)' : '').'</td>';
  }
  $html.='<td align="center"><b>'.(isset($kurs->anzahlen) && $kurs->anzahlen['summe']>0 ? sprintf('%0.1f',$kurs->werte['summe']/$kurs->anzahlen['summe']).'<br />'.count($kurs->bewerterids).'&nbsp;('.round(100*count($kurs->bewerterids)/$kurs->anzahlTN).'%)' : '').'</b></td>';
  $html.='<td valign="top" align="left" class="klappbar">';
  if(isset($kurs->feedbacks)) {
    $html.='<div class="klapp"><br />'.$kurs->anzahlen['info'].'&nbsp;('.round(100*$kurs->anzahlen['info']/$kurs->anzahlTN).'&nbsp;%)</div>';
    $html.='<i>';
    foreach($kurs->feedbacks as $fb) {
      $html.='<br />„'.nl2br(trim($fb)).'”';
    }
    $html.='</i>';
  }
  $html.='</td>';
  $html.='</tr>';
}
$html.='</table>';

$mm2pt=2.83465;

require_once '../tcpdf/tcpdf.php';
$pdf=new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor('GPBmbH');
$pdf->setTitle($dateiname);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(true,10);

$pdf->AddPage();

$pdf->SetXY(10,10);
$pdf->SetFont('DejaVuSans','B',$mm2pt*4);
$pdf->Cell(0,0,'Kursbewertungen am '.date('d.m.Y'));

$pdf->SetXY(10,20);
$pdf->SetFont('DejaVuSans','',$mm2pt*2.5);
$pdf->WriteHTML($html,false,false,false,false,'C');

$pdf->Output($dateiname.'.pdf','D');
exit;
?>

