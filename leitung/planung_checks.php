<?php
require_once 'check_login.php';
require_once 'DozentVerfuegbarkeit.php';
function check(&$toFill,$query,$params,$values) {
  global $db;
  $stmt=$db->prepare($query);
  if($params) {
    $stmt->bind_param($params,...$values);
  }
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $toFill[]=$row;
  }
  $result->free();
}
function vergleichen($m0,$m1) {
  return strcmp($m0->vergleich,$m1->vergleich);
}

$meldungen=array();

// Klasse hat 2 Kurse gleichzeitig
check($meldungen,"select distinct concat('Klasse ',k.bezeichnung,' hat zwei Kurse gleichzeitig') as meldung
    ,kk1.klasseid,greatest(k1.beginn,k2.beginn) as beginn,least(k1.ende,k2.ende) as ende
  from gpb_kurs_klasse kk1
  join gpb_kurs_view k1 on k1.id=kk1.kursid
  join gpb_kurs_klasse kk2 on kk2.klasseid=kk1.klasseid and kk2.kursid<>kk1.kursid
  join gpb_kurs_view k2 on k2.id=kk2.kursid
  join gpb_klasse k on k.id=kk1.klasseid
  where k1.beginn<=k2.ende and k1.ende>=k2.beginn
    and least(k1.ende,k2.ende)>=current_date()
    ".($ich->ort ? "and (k1.ort=? or k2.ort=?)" : ""),$ich->ort ? 'ss' : '',array($ich->ort,$ich->ort));
    
// Kurs vor Beginn der Klasse
check($meldungen,"select distinct concat('Klasse ',kl.bezeichnung,': Kurs vor Beginn') as meldung
    ,kk.klasseid,ku.beginn,least(date_add(ku.ende,interval 3 day),kl.beginn) as ende
  from gpb_kurs_klasse kk
  join gpb_klasse kl on kl.id=kk.klasseid
  join gpb_kurs_view ku on ku.id=kk.kursid
  where ku.beginn<kl.beginn
    and ku.ende>=current_date()
    ".($ich->ort ? "and (kl.ort=? or ku.ort=?)" : ""),$ich->ort ? 'ss' : '',array($ich->ort,$ich->ort));
    
// Kurs nach Ende der Klasse
check($meldungen,"select distinct concat('Klasse ',kl.bezeichnung,': Kurs nach Ende') as meldung
    ,kk.klasseid,greatest(ku.beginn,date_add(kl.ende,interval 3 day)) as beginn,ku.ende
  from gpb_kurs_klasse kk
  join gpb_klasse kl on kl.id=kk.klasseid
  join gpb_kurs_view ku on ku.id=kk.kursid
  where ku.ende>kl.ende
    and ku.ende>=current_date()
    ".($ich->ort ? "and (kl.ort=? or ku.ort=?)" : ""),$ich->ort ? 'ss' : '',array($ich->ort,$ich->ort));
    
// TODO Kurs ohne Klasse oder ohne Dozent oder ohne Raum?
// TODO Kurs ohne Klasse und ohne Dozent und ohne Raum?
        
// Dozent hat 2 Kurse gleichzeitig
check($meldungen,"select distinct concat('Dozent ',d.vorname,' ',d.nachname,' hat zwei Kurse gleichzeitig') as meldung
    ,kd1.dozentid,greatest(k1.beginn,k2.beginn) as beginn,least(k1.ende,k2.ende) as ende
  from gpb_kurs_dozent kd1
  join gpb_kurs_view k1 on k1.id=kd1.kursid
  join gpb_kurs_dozent kd2 on kd2.dozentid=kd1.dozentid and kd2.kursid<>kd1.kursid
  join gpb_kurs_view k2 on k2.id=kd2.kursid
  join gpb_dozent d on d.id=kd1.dozentid
  where k1.beginn<=k2.ende and k1.ende>=k2.beginn
    and least(k1.ende,k2.ende)>=current_date()
    ".($ich->ort ? "and (k1.ort=? or k2.ort=?)" : ""),$ich->ort ? 'ss' : '',array($ich->ort,$ich->ort));
    
// Dozent geplant aber nicht verfügbar
function add_zeitraum(&$zeitraeume,$beginn,$ende) {
  $beg=$beginn;
  $end=$ende;
  foreach($zeitraeume as $zr) {
    if($zr->ende<$beg || $zr->beginn>$end) continue;
    if($zr->beginn<=$beg && $zr->ende>=$beg) {
      $beg='9999-99-99';
      $end='0000-00-00';
      break;
    }
    if($beg<$zr->beginn) {
      $zr->beginn=$beg;
      $beg='9999-99-99';
    }
    if($end>$zr->ende) {
      $zr->ende=$end;
      $end='0000-00-00';
    }
  }
  if($beg<=$end) {
    $zeitraeume[]=(object)array('beginn'=>$beg,'ende'=>$end);
  }
}
function sub_zeitraum(&$zeitraeume,$beginn,$ende) {
  for($i=count($zeitraeume)-1;$i>=0;--$i) {
    $zr=$zeitraeume[$i];
    if($zr->ende<$beginn || $zr->beginn>$ende) continue;
    array_splice($zeitraeume,$i,1);
    if($zr->beginn<$beginn) {
      $zeitraeume[]=(object)array('beginn'=>$zr->beginn,'ende'=>strtotime('-1 day',$beginn));
    }
    if($zr->ende>$ende) {
      $zeitraeume[]=(object)array('beginn'=>strtotime('+1 day',$ende),'ende'=>$zr->ende);
    }
  }
}
$zupruefen=array();
$stmt=$db->prepare("select kd.*
  ,k.beginn,k.ende
  ,d.idrverfuegbar,d.vorname,d.nachname
from gpb_kurs_dozent kd
join gpb_kurs k on k.id=kd.kursid
join gpb_dozent d on d.id=kd.dozentid
where k.beginn is not null and k.beginn<>'0000-00-00' and k.ende is not null and k.ende>=current_date()".($ich->ort ? " and k.ort=?" : ""));
if($ich->ort) {
  $stmt->bind_param('s',$ich->ort);
}
$stmt->execute();
$result=$stmt->get_result();
while($row=$result->fetch_object()) {
  $row->nok=array();
  if(!$row->idrverfuegbar) {
    $row->nok[]=(object)array('beginn'=>$row->beginn,'ende'=>$row->ende);
  }
  if(isset($zupruefen[$row->dozentid])) {
    $zupruefen[$row->dozentid][]=$row;
  } else {
    $zupruefen[$row->dozentid]=[$row];
  }
}
$result->free();
if(!empty($zupruefen)) {
  $result=$db->query("select * from gpb_dozent_verfuegbarkeit where ende>=current_date() and dozentid in(".implode(',',array_keys($zupruefen)).")");
  while($row=$result->fetch_object()) {
    $modell=DozentVerfuegbarkeit::$instanzenByWert[$row->verfuegbar];
    if($modell->verfuegbar) {
      foreach($zupruefen[$row->dozentid] as $kurs) {
        sub_zeitraum($kurs->nok,$row->beginn,$row->ende);
      }
    } else {
      foreach($zupruefen[$row->dozentid] as $kurs) {
        if($kurs->beginn<=$row->ende && $kurs->ende>=$row->beginn) {
          add_zeitraum($kurs->nok,max($row->beginn,$kurs->beginn),min($row->ende,$row->ende));
        }
      }
    }
  }
  $result->free();
}
foreach($zupruefen as $dozentid=>$kurse) {
  foreach($kurse as $kurs) {
    foreach($kurs->nok as $zr) {
      $meldungen[]=(object)array('meldung'=>'Dozent '.$kurs->vorname.' '.$kurs->nachname.' eingeplant aber nicht verfügbar','beginn'=>$zr->beginn,'ende'=>$zr->ende/*,'dozentid'=>$dozentid*/,'kursid'=>$kurs->kursid);
    }
  }
}

    
// Raum hat 2 Kurse gleichzeitig
check($meldungen,"select distinct concat('Raum ',r.tuer,' hat zwei Kurse gleichzeitig') as meldung
    ,k1.raumid,greatest(k1.beginn,k2.beginn) as beginn,least(k1.ende,k2.ende) as ende
  from gpb_kurs_view k1
  join gpb_kurs_view k2 on k2.raumid=k1.raumid and k2.id<>k1.id
  join gpb_raum r on r.id=k1.raumid
  where k1.raumid is not null 
    and k1.beginn<=k2.ende and k1.ende>=k2.beginn
    and least(k1.ende,k2.ende)>=current_date()
    and r.anzahlPlaetze>0
    ".($ich->ort ? "and (k1.ort=? or k2.ort=?)" : ""),$ich->ort ? 'ss' : '',array($ich->ort,$ich->ort));

//TODO Raum wegen mehrere Kurse überfüllt (jeder einzelne Kurs OK)
    
// Raum überfüllt
check($meldungen,"select concat('Raum ',k.raum,' überfüllt') as meldung
    ,k.beginn,k.ende,k.raumid,k.raum as tuer
  from (select kv.*,atn.anzahlTN,aan.anzahlAnmeldungen
    from gpb_kurs_view kv
    left outer join (select k.id as kursid,count(*) as anzahlTN
      from gpb_kurs k
      join gpb_kurs_klasse kk on kk.kursid=k.id
      join gpb_klasse_tn ktn on ktn.klasseid=kk.klasseid
      and ktn.einstieg is not null and ktn.einstieg<>'0000-00-00' and ktn.einstieg<=k.ende
      and (ktn.ausstieg is null or ktn.ausstieg='0000-00-00' or ktn.ausstieg>=k.beginn)
      group by k.id) atn on atn.kursid=kv.id
    left outer join (select k.id as kursid,count(*) as anzahlAnmeldungen
      from gpb_kurs k
      join gpb_kurs_klasse kk on kk.kursid=k.id
      join gpb_klasse_tn ktn on ktn.klasseid=kk.klasseid
      group by k.id) aan on aan.kursid=kv.id
  ) k
  where case when k.beginn>current_date() then k.anzahlTN else k.anzahlAnmeldungen end>k.anzahlPlaetze
    and k.anzahlPlaetze>0
    and k.ende>=current_date()
    ".($ich->ort ? "and k.ort=?" : ""),$ich->ort ? 's' : '',array($ich->ort));
    
// Kurs enthält Ferien, die länger als 2 Tage dauern
check($meldungen,"select distinct concat('Kurs während der ',f.anlass) as meldung
    ,k.id as kursid,greatest(f.beginn,k.beginn) as beginn,least(f.ende,k.ende) as ende
  from gpb_ferien f
  join gpb_kurs_view k
  where datediff(f.ende,f.beginn)>2
    and k.beginn<=f.ende and k.ende>=f.beginn
    and f.art<>'Berliner Schulferien'
    and (f.art<>'Institutsferien' or k.ort=f.ort)
    and (f.art<>'Klassenferien' or k.id in(select kk.kursid from gpb_kurs_klasse kk where kk.klasseid in(select kf.klasseid from gpb_klasse_ferien kf where kf.ferienid=f.id)))
    ".($ich->ort ? " and (f.ort='' or f.ort=?)" : ""),$ich->ort ? 's' : '',array($ich->ort));

foreach($meldungen as $m) {
  if(isset($m->beginn) && isset($m->ende)) {
    $m->vergleich='1 '.$m->beginn.' '.$m->ende.' '.$m->meldung;
    $m->von=strtotime($m->beginn);
    $m->bis=strtotime($m->ende);
    if($m->von>0 && $m->bis>0) {
      $m->kw=array();
      for($wann=$m->von;$wann<=$m->bis;$wann=strtotime('+1 week',$wann)) {
        $m->kw[]=date('Y-W',$wann);
      }
    }
  } else {
    $m->vergleich='0 '.$m->meldung;
  }
  $m->vergleich=mb_strtolower($m->vergleich);
}
usort($meldungen,'vergleichen');
echo json_encode($meldungen);
exit;
?>