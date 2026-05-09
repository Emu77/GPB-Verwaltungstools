<?php
require_once 'check_login.php';
require_once 'intrain_teilzeit_wochenue.php';
require_once 'angebot_funktionen.php';

if(isset($_POST['intrain_neuesinteresse'])) {
  $_SESSION['intrain_neuesinteresse']=$_POST['intrain_neuesinteresse']!='N';
}

$interessent=isset($_SESSION['intrain_interessent']) ? $_SESSION['intrain_interessent'] : null;
if(empty($interessent)) {
  require_once 'intrain_seite.php';
  exit;
}
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
$neuesinteresse=isset($_SESSION['intrain_neuesinteresse']) ? $_SESSION['intrain_neuesinteresse'] : false;

$zertifsById=array();
$result=$db->query("select * from gpb_intrainzertif ");
while($row=$result->fetch_object()) {
  $row->zertif_von=strtotime($row->zertif_beginn);
  $row->zertif_bis=strtotime($row->zertif_ende);
  $zertifsById[$row->id]=$row;
}
$result->free();

$pakete=array();
$paketeById=array();
$paketeByMid=array();
$result=$db->query("select * from gpb_intrainpaket");
while($row=$result->fetch_object()) {
  $pakete[]=$row;
  $paketeById[$row->id]=$row;
  if(!empty($paket->mitisid)){
    $paketeByMid[$row->mitisid]=$row;
  }
}
$result->free();
$module=array();
$moduleById=array();
$moduleByMid=array();
$result=$db->query("select m.* from gpb_intrainmodul m order by m.titel");
while($row=$result->fetch_object()) {
  $module[]=$row;
  $moduleById[$row->id]=$row;
  if(!empty($row->mitisid)) {
    $moduleByMid[$row->mitisid]=$row;
  }
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

$anzahlue=0;
foreach($modulids as $modid=>$dummy) {
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
  $zeit->anzahlue=ceil($m/60);
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
    $dauer+=$arbeitszeiten[$tagnamen[date('D')]]->anzahlue;
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
      'zertif'=>$mod->zertifid>0 && isset($zertifsById[$mod->zertifid]) ? $zertifsById[$mod->zertifid] : null,
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

$massnahmeids=array();
if(!$neuesinteresse) {
  foreach($interessent->interessenausmitis as $interesse) {
    $massnahmeids[]=$interesse->MassnahmeID;
  }
}

$interessenfuermitis=array();
$heute=date('d.m.Y');
$name=formatName($interessent->vorname,$interessent->nachname);
$nummer=0;
foreach($angebote as $ang) {
  ++$nummer;
  $interesse=array(
    'Datum'=>$heute
    ,'Hauptinteresse'=>1
    ,'MassnahmeID'=>empty($massnahmeids) ? 0 : array_shift($massnahmeids)
    ,'aktiv'=>1
    ,'Titel'=>'Kurs '.$name.' '.date('d.m.Y',$ang->von).' BG'.$nummer
    ,'Beginn'=>date('d.m.Y',$ang->von)
    ,'Ende'=>date('d.m.Y',$ang->bis)
    ,'VzTz'=>$zeitmodell
    ,'AA_Nummer'=>(empty($ang->zertif) ? '' : ($zeitmodell=='Vollzeit' ? $ang->zertif->vollzeit_massnnr : $ang->zertif->teilzeit_massnnr))
    ,'NiederlassungID'=>23
    ,'KoordinatorID'=>0 //Berater-Id wird MITIS automatisch hinzufügen (= Importeur)
    ,'Module'=>array() 
  );
  foreach($ang->module as $mod) {
    $interesse['Module'][]=array(
      'ModulID'=>$mod->mitisid
      ,'PaketID'=>192 //192=Dummy-PaketID für inTrain-Module
      //nicht übergeben wegen MITIS-Bug "FEHLER: Modul-Ende-Datum ('09.12.2025') liegt vor Modul-Beginn-Datum ('01.12.2025')."
//      ,'Beginn'=>date('d.m.Y',$mod->von)
//      ,'Ende'=>date('d.m.Y',$mod->bis)
    );
  }
  $interessenfuermitis[]=$interesse;
}
$interessent->interessenfuermitis=$interessenfuermitis;

$stmt=$db->prepare("update gpb_basket set interessenbereich='intrain',zeitmodell=?,wochenue=?,arbeitszeiten=?,anfangsdatum=?,beraterid=?,ausbildungids='',modulids=?,modulanfaenge=?,interessenfuermitis=? where mitisid=?");
$zeiten=json_encode($arbeitszeiten);
$modids=json_encode($modulids);
$modanfs=json_encode($modulanfaenge);
$inters=json_encode($interessenfuermitis);
$stmt->bind_param('sississsi',$zeitmodell,$wochenue,$zeiten,$anfangsdatum,$ich->id,$modids,$modanfs,$inters,$interessent->mitisid);
$stmt->execute();

$interessent->zeitmodell=$zeitmodell;
$interessent->wochenue=$wochenue;
$interessent->arbeitszeiten=$arbeitszeiten;
$interessent->anfangsdatum=$anfangsdatum;
$interessent->beraterid=$ich->id;
$interessent->ausbildungids='';
$interessent->modulids=$modulids;
$interessent->modulanfaenge=$modulanfaenge;

require_once 'intrain_seite.php';
exit;
?>