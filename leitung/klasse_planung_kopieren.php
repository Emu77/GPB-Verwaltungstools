<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once '../Klasse.php';
require_once '../Ferien.php';
require_once '../Kurs.php';

$redirect=!isset($_GET['redirect']) || $_GET['redirect']!='N';
$partnerid=isset($_GET['partnerid']) ? (int)$_GET['partnerid'] : 0;
if($partnerid<=0) {
  if($redirect) {
    header('Location:klassen.php');
    exit;
  } else {
    echo 'Bitte Modell-Klasse auswählen';
    exit;
  }
}

$klasse=Klasse::eineLaden(isset($_GET['klasseid']) ? (int)$_GET['klasseid'] : 0,'Klasse');
if(empty($klasse)) {
  if($redirect) {
    header('Location:klassen.php');
    exit;
  } else {
    echo 'Bitte Klasse auswählen';
    exit;
  }
}
if($klasse->von<=0) {
  if($redirect) {
    header('Location:klasse_kurse.php');
    exit;
  } else {
    echo 'Klasse hat kein Startdatum';
    exit;
  }
}

$stmt=$db->prepare("select * from gpb_kurs_view where id in(select kursid from gpb_kurs_klasse where klasseid=".$klasse->id.") order by beginn,ende,titel");
$kurse=new Liste('Kurs',$stmt);
Kurs::refsLaden($kurse);

$kurseByKw=array();
foreach($kurse->alle as $kurs) {
  if(!empty($kurs->modulid) && isset($moduleById[$kurs->modulid])) {
    $moduleById[$kurs->modulid]->kurse[]=$kurs;
    $moduleById[$kurs->modulid]->restdauer-=count($kurs->kw);
  }
  foreach($kurs->kw as $kw) {
    if(isset($kurseByKw[$kw])) {
      $kurseByKw[$kw][]=$kurs;
    } else {
      $kurseByKw[$kw]=array($kurs);
    }
  }
}

//Institut- und Klassenferien sind schon in der Klasse geladen
$stmt=$db->prepare("select * from gpb_ferien where art<>'Klassenferien' and art<>'Institutsferien' and ende>='".date('Y-m-d',$klasse->von)."' order by beginn,ende");
$ferien=new Liste('Ferien',$stmt);

$ferienByKw=array();
foreach($klasse->ferien as $f) {
  foreach($f->kw as $w) {
    if(isset($ferienByKw[$w])) {
      $ferienByKw[$w][]=$f;
    } else {
      $ferienByKw[$w]=array($f);
    }
  }
}
foreach($ferien->alle as $f) {
  foreach($f->kw as $w) {
    if(isset($ferienByKw[$w])) {
      $ferienByKw[$w][]=$f;
    } else {
      $ferienByKw[$w]=array($f);
    }
  }
}

function istBelegt($wann,$woche) {
  global $kurseByKw,$ferienByKw;
  if(isset($kurseByKw[$woche])) return true;
  if(!isset($ferienByKw[$woche])) return false;
  $arbeitstage=array();
  for($t=0;$t<5;++$t) {
    $ww=strtotime('+'.$t.' days',$wann);
    $arbeitstage[date('Y-m-d',$ww)]=$ww;
  }
  foreach($ferienByKw[$woche] as $ferien) {
    foreach($arbeitstage as $t=>$ww) {
      if($ww>=$ferien->von && $ww<=$ferien->bis) {
        unset($arbeitstage[$t]);
      }
    }
  }
  return empty($arbeitstage);
}
function skipBelegte($ab) {
  $res=$ab;
  while(istBelegt($res,date('Y-W',$res))) $res=strtotime('+1 week',$res);
  return $res;
}

$modellkurse=new Liste('Kurs',$db->prepare("select k.* 
  from gpb_kurs_view k 
  where k.id in(select kursid from gpb_kurs_klasse where klasseid=".$partnerid.")
    and k.id not in(select kursid from gpb_kurs_klasse where klasseid=".$klasse->id.")
  order by beginn,ende,titel"));
Kurs::dozentenLaden($modellkurse);

$erstelltekursids=array();
$wann=skipBelegte(max($klasse->von,strtotime('next monday')));
foreach($modellkurse->alle as $modell) {
  $beginn=date('Y-m-d',$wann);
  $bis=$wann+($modell->bis-$modell->von);
  $ende=date('Y-m-d',$bis);
  $titel=$beginn.' '.$klasse->bezeichnung.' '.$modell->modultitel;
  $raumid=$modell->raumid;
  $modulid=$modell->modulid;
  $notenstatus=$modell->notenstatus=='keine' ? 'keine' : 'todo';
  $planungnotiz=$modell->planungnotiz;
  $stmt=$db->prepare("insert into gpb_kurs(titel,beginn,ende,raumid,modulid,notenstatus,planungnotiz) values(?,?,?,?,?,?,?)");
  $stmt->bind_param('sssiiss',$titel,$beginn,$ende,$raumid,$modulid,$notenstatus,$planungnotiz);
  $stmt->execute();
  $kursid=$db->insert_id;
  $erstelltekursids[]=$kursid;
  $stmt=$db->prepare("insert into gpb_kurs_klasse(kursid,klasseid) values(?,?)");
  $stmt->bind_param('ii',$kursid,$klasse->id);
  $stmt->execute();
  foreach($modell->dozentenids as $dozentid) {
    $stmt=$db->prepare("insert into gpb_kurs_dozent(kursid,dozentid) values(?,?)");
    $stmt->bind_param('ii',$kursid,$dozentid);
    $stmt->execute();
  }
  $wann=skipBelegte(strtotime('next monday',$bis));
}

if($redirect) {
  header('Location:klasse_kurse.php?klasseid='.$klasse->id);
  exit;
}

$erstelltekurse=new Liste('Kurs',empty($erstelltekursids) ? null : $db->prepare("select k.* from gpb_kurs_view k where k.id in(".implode(',',$erstelltekursids).")"));
Kurs::refsLaden($erstelltekurse);
echo 'OK';
echo json_encode($erstelltekurse->alle);
exit;
?>