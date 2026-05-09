<?php
require_once 'check_login.php';
require_once 'angebot_funktionen.php';
require_once 'spez_teilzeit_wochenue.php';

$interessent=isset($_SESSION['spez_interessent']) ? $_SESSION['spez_interessent'] : null;
if(empty($interessent)) {
  header('Location:spezialisten.php');
  exit;
}
$zeitmodell=isset($_SESSION['spez_zeitmodell']) ? $_SESSION['spez_zeitmodell'] : 'Vollzeit';
$wochenue=isset($_SESSION['spez_wochenue']) ? $_SESSION['spez_wochenue'] : 24;
$anfangsdatum=isset($_SESSION['spez_anfangsdatum']) && !empty($_SESSION['spez_anfangsdatum']) ? date('Y-m-d',max(strtotime('next monday'),strtotime($_SESSION['spez_anfangsdatum']))) : date('Y-m-d',strtotime('next monday'));
$ausbildungids=isset($_SESSION['spez_ausbildungids']) ? $_SESSION['spez_ausbildungids'] : array();
$modulids=isset($_SESSION['spez_modulids']) ? $_SESSION['spez_modulids'] : array();
$modulanfaenge=isset($_SESSION['spez_modulanfaenge']) ? $_SESSION['spez_modulanfaenge'] : array();

$ausbildungen=array();
$ausbildungenById=array();
$ausbildungenByMid=array();
$result=$db->query("select * from gpb_spezausbildung");
while($row=$result->fetch_object()) {
  $ausbildungen[]=$row;
  $ausbildungenById[$row->id]=$row;
  if(!empty($row->mitisid)) {
    $ausbildungenByMid[$row->mitisid]=$row;
  }
}
$result->free();

$module=array();
$moduleById=array();
$moduleByMid=array();
$result=$db->query("select m.* from gpb_spezmodul m order by m.titel");
while($row=$result->fetch_object()) {
  $module[]=$row;
  $moduleById[$row->id]=$row;
  if(!empty($row->mitisid)) {
    $moduleByMid[$row->mitisid]=$row;
  }
}
$result->free();

// Schon angelegte Maßnahmen in die betroffenen Objekte registrieren
foreach($interessent->interessenausmitis as $interesse) {
  foreach($interesse->Module as $mitismod) {
    $mod=isset($moduleByMid[$mitismod->ModulID]) ? $moduleByMid[$mitismod->ModulID] : null;
    if(empty($mod)) continue;
    $ausbildung=isset($ausbildungenByMid[$mitismod->PaketID]) ? $ausbildungenByMid[$mitismod->PaketID] : null;
    if(empty($ausbildung)) {
      $mod->MassnahmeID=$interesse->MassnahmeID;
    } else {
      $ausbildung->MassnahmeID=$interesse->MassnahmeID;
    }
  }
}

$anzahlue=0;
foreach($ausbildungids as $ausid=>$modids) {
  $aus=$ausbildungenById[$ausid];
  $aus->anzahlue=0;
  foreach($modids as $modid=>$dummy) {
    if(!isset($moduleById[$modid])) continue;
    $mod=$moduleById[$modid];
    $aus->anzahlue+=$mod->anzahlue;
    if(isset($modulanfaenge[$mod->id])) {
      $mod->anfang=$modulanfaenge[$mod->id];
      $mod->von=strtotime($mod->anfang);
    } else {
      $mod->anfang='';
      $mod->von=0;
    }
  }
  $anzahlue+=$aus->anzahlue;
}
foreach($modulids as $modid=>$dummy) {
  if(!isset($moduleById[$modid])) continue;
  $mod=$moduleById[$modid];
  $anzahlue+=$mod->anzahlue;
  if(isset($modulanfaenge[$mod->id])) {
    $mod->anfang=$modulanfaenge[$mod->id];
  } else {
    $mod->anfang='';
  }
}

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
  foreach($modulids as $modid=>$dummy) {
    if(!isset($moduleById[$modid])) continue;
    $mod=$moduleById[$modid];
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
  foreach($modids as $modid=>$dummy) {
    if(!isset($moduleById[$modid])) continue;
    $mod=$moduleById[$modid];
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
foreach($modulids as $modid=>$dummy) {
  if(!isset($moduleById[$modid])) continue;
  $mod=$moduleById[$modid];
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



function formatName($vorname,$nachname) {
  $search=explode(',',' ,,À,Á,Â,Ã,Ä,Å,Æ,Ç,Ć,Č,È,É,Ê,Ë,Ì,Í,Î,Ï,Ñ,Ò,Ó,Ô,Õ,Ö,Ś,Š,Ù,Ú,Û,Ü,Ý,ß,à,á,â,ã,ä,å,æ,ç,ć,č,è,é,ê,ë,ì,í,î,ï,ñ,ò,ó,ô,õ,ö,ś,š,ş,ù,ú,û,ü,ý,ÿ,\',#,+,%,/,:,;,\\,-');
  $replace=explode(',','_,_,A,A,A,A,AE,A,AE,C,C,C,E,E,E,E,I,I,I,I,N,O,O,O,O,OE,S,S,U,U,U,UE,Y,ss,a,a,a,a,ae,a,ae,c,c,c,e,e,e,e,i,i,i,i,n,o,o,o,o,oe,s,s,s,u,u,u,ue,y,y,_,_,_,_,_,_,_,_,_');
  $nn=str_replace($search,$replace,$nachname);
  if(strlen($nn)>=13) {
    return substr($nn,0,14);
  }
  $vn=str_replace($search,$replace,$vorname);
  return substr($vn,0,13-strlen($nn)).' '.$nn;
}

$interessenfuermitis=array();
$heute=date('d.m.Y');
$name=formatName($interessent->vorname,$interessent->nachname);
$nummer=0;
foreach($ausbildungids as $ausid=>$modids) {
  $aus=$ausbildungenById[$ausid];
  ++$nummer;
  $interesse=array(
    'Datum'=>$heute
    ,'Hauptinteresse'=>1
    ,'MassnahmeID'=>(isset($aus->MassnahmeID) ? $aus->MassnahmeID : 0)
    ,'aktiv'=>1
    ,'Titel'=>'Ausbildung '.$name.' '.date('d.m.Y',$aus->von).' BG'.$nummer
    ,'Beginn'=>date('d.m.Y',$aus->von)
    ,'Ende'=>date('d.m.Y',$aus->bis)
    ,'VzTz'=>$zeitmodell
    ,'AA_Nummer'=>($zeitmodell=='Vollzeit' ? $aus->vollzeit_massnnr : $aus->teilzeit_massnnr)
    ,'NiederlassungID'=>23
    ,'KoordinatorID'=>0 //Berater-Id wird MITIS automatisch hinzufügen (= Importeur)
    ,'Module'=>array()
  );
  foreach($modids as $modid=>$dummy) {
    if(!isset($moduleById[$modid])) continue;
    $mod=$moduleById[$modid];
    $interesse['Module'][]=array(
      'ModulID'=>$mod->mitisid
      ,'PaketID'=>$aus->mitisid
      //nicht übergeben wegen MITIS-Bug "FEHLER: Modul-Ende-Datum ('09.12.2025') liegt vor Modul-Beginn-Datum ('01.12.2025')."
//      ,'Beginn'=>date('d.m.Y',$mod->von)
//      ,'Ende'=>date('d.m.Y',$mod->bis)
    );
  }
  $interessenfuermitis[]=$interesse;
}
foreach($modulids as $modid=>$dummy) {
  if(!isset($moduleById[$modid])) continue;
  $mod=$moduleById[$modid];
  ++$nummer;
  $interesse=array(
    'Datum'=>$heute
    ,'Hauptinteresse'=>1
    ,'MassnahmeID'=>(isset($mod->MassnahmeID) ? $mod->MassnahmeID : 0)
    ,'aktiv'=>1
    ,'Titel'=>'Kurs '.$name.' '.date('d.m.Y',$mod->von).' BG'.$nummer
    ,'Beginn'=>date('d.m.Y',$mod->von)
    ,'Ende'=>date('d.m.Y',$mod->bis)
    ,'VzTz'=>$zeitmodell
    ,'AA_Nummer'=>($zeitmodell=='Vollzeit' ? $mod->vollzeit_massnnr : $mod->teilzeit_massnnr)
    ,'NiederlassungID'=>23
    ,'KoordinatorID'=>0 //Berater-Id wird MITIS automatisch hinzufügen (= Importeur)
    ,'Module'=>array(array(
      'ModulID'=>$mod->mitisid
      ,'PaketID'=>192 //192=Dummy-PaketID für inTrain-Module
      //nicht übergeben wegen MITIS-Bug "FEHLER: Modul-Ende-Datum ('09.12.2025') liegt vor Modul-Beginn-Datum ('01.12.2025')."
//      ,'Beginn'=>date('d.m.Y',$mod->von)
//      ,'Ende'=>date('d.m.Y',$mod->bis)
    )) 
  );
  $interessenfuermitis[]=$interesse;
}
$interessent->interessenfuermitis=$interessenfuermitis;

$stmt=$db->prepare("update gpb_basket set interessenbereich='spez',zeitmodell=?,wochenue=?,arbeitszeiten='',anfangsdatum=?,beraterid=?,ausbildungids=?,modulids=?,modulanfaenge=?,interessenfuermitis=? where mitisid=?");
$ausbids=json_encode($ausbildungids);
$modids=json_encode($modulids);
$modanfs=json_encode($modulanfaenge);
$inters=json_encode($interessenfuermitis);
$stmt->bind_param('sisissssi',$zeitmodell,$wochenue,$anfangsdatum,$ich->id,$ausbids,$modids,$modanfs,$inters,$interessent->mitisid);
$stmt->execute();
$interessent->zeitmodell=$zeitmodell;
$interessent->wochenue=$wochenue;
$interessent->arbeitszeiten='';
$interessent->anfangsdatum=$anfangsdatum;
$interessent->beraterid=$ich->id;
$interessent->ausbildungids=$ausbildungids;
$interessent->modulids=$modulids;
$interessent->modulanfaenge=$modulanfaenge;


header('Location:spezialisten.php');
exit;
?>