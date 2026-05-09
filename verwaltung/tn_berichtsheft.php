<?php
require_once 'check_login.php';
require_once 'VerwaltungTn.php';

$tn=VerwaltungTn::einenLaden(isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0,'VerwaltungTn');
if(empty($tn)) {
  header('Location:tn.php');
  exit;
}

$tagnamen=array('Mon'=>'Montag','Tue'=>'Dienstag','Wed'=>'Mittwoch','Thu'=>'Donnerstag','Fri'=>'Freitag','Sat'=>'Samstag','Sun'=>'Sonntag');

$result=$db->query("select min(einstieg) as mindatum,max(case when 
  (ausstieg is null or ausstieg='0000-00-00') 
  and (einstieg is not null and einstieg<>'0000-00-00') then current_date() else ausstieg end) as maxdatum from gpb_massnahme_tn where tnid=".$tn->id);
$minmax=$result->fetch_object();
$result->free();
if(empty($minmax->mindatum) || $minmax->mindatum=='0000-00-00') {
  $minwoche=$beginn;
} else {
  $minwoche=strtotime($minmax->mindatum);
}
if(date('D',$minwoche)!='Mon') {
  $minwoche=strtotime('last monday',$minwoche);
}
if(empty($minmax->maxdatum) || $minmax->maxdatum=='0000-00-00') {
  $maxwoche=strtotime('today');
} else {
  $maxwoche=strtotime($minmax->maxdatum);
}
$maxwoche=min($maxwoche,strtotime('today'));
if(date('D',$maxwoche)=='Mon') {
  $maxwoche=strtotime('+1 week',$maxwoche);
} else {
  $maxwoche=strtotime('next monday',$maxwoche);
}
$beginn=$minwoche;
$ende=strtotime('-1 day',$maxwoche);
$wochen=array();
$wochenByKw=array();
for($t=$beginn;$t<=$ende;$t=strtotime('+1 week',$t)) {
  $kw=date('Y-W',$t);
  $woche=(object)array(
    'display'=>'KW '.date('W',$t).' - '.date('d.m.Y',$t).' bis '.date('d.m.Y',strtotime('+6 days',$t)),
    'tage'=>array(),
    'kurs'=>null
  );
  for($i=0;$i<7;++$i) {
    $tag=strtotime('+'.$i.' days',$t);
    $woche->tage[$tagnamen[date('D',$tag)]]=(object)array(
      'tag'=>date('d.m.Y',$tag),
      'eintrag'=>null,
      'vorschlag'=>null,
      'ferien'=>null
    );
  }
  $wochen[]=$woche;
  $wochenByKw[$kw]=$woche;
}

$result=$db->query("select * from gpb_berichtsheft where tnid=".$tn->id." and datum>='".date('Y-m-d',$beginn)."' and datum<='".date('Y-m-d',$ende)."' order by datum");
while($row=$result->fetch_object()) {
  $row->von=substr($row->beginn,0,5);
  $row->bis=substr($row->beginn,0,5);
  $tag=strtotime($row->datum);
  $tagname=$tagnamen[date('D',$tag)];
  $kw=date('Y-W',$tagname=='Montag' ? $tag : strtotime('last monday',$tag));
  $wochenByKw[$kw]->tage[$tagname]=$row;
}
$result->free();

$kurseById=array();
$result=$db->query("select ku.*
  from gpb_kurs_view ku
  join gpb_kurs_klasse kukl on kukl.kursid=ku.id
  join gpb_klasse_tn kltn on kltn.klasseid=kukl.klasseid
  where ku.beginn is not null and ku.beginn<>'0000-00-00' and ku.beginn<='".date('Y-m-d',$ende)."'
    and ku.ende is not null and ku.ende<>'0000-00-00' and ku.ende>='".date('Y-m-d',$beginn)."'
    and kltn.tnid=".$tn->id." and kltn.einstieg is not null and kltn.einstieg<>'0000-00-00' 
    and kltn.einstieg<=ku.ende and (kltn.ausstieg is null or kltn.ausstieg='0000-00-00' or kltn.ausstieg>=ku.beginn)");
while($row=$result->fetch_object()) {
  $row->dozentname='';
  $kurseById[$row->id]=$row;
  $b=strtotime($row->beginn);
  for($t=date('D',$b)=='Mon' ? $b : strtotime('last monday',$b),$e=strtotime($row->ende);$t<=$e;$t=strtotime('+1 week',$t)) {
    $kw=date('Y-W',$t);
    if(isset($wochenByKw[$kw])) {
      $wochenByKw[$kw]->kurs=$row;
    }
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
    $tag=strtotime($row->tag);
    $tagname=$tagnamen[date('D',$tag)];
    $kw=date('Y-W',$tagname=='Montag' ? $tag : strtotime('last monday',$tag));
    $vorschlag=$wochenByKw[$kw]->tage[$tagname]->vorschlag;
    if(empty($vorschlag)) {
      $vorschlag=$wochenByKw[$kw]->tage[$tagname]->vorschlag=(object)array(
        'von'=>'',
        'bis'=>'',
        'pausen'=>'',
        'was'=>''
      );
    }
    if($tagname=='Freitag') {
      $vorschlag->von='08:15';
      $vorschlag->bis='13:15';
      $vorschlag->pausen=0.5;
    } else if($tagname!='Samstag' && $tagname!='Sonntag') {
      $vorschlag->von='08:15';
      $vorschlag->bis='16:15';
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
      $kw=date('Y-W',$tagname=='Montag' ? $t : strtotime('last monday',$t));
      $wochenByKw[$kw]->tage[$tagname]->ferien=$f;
      $vorschlag=$wochenByKw[$kw]->tage[$tagname]->vorschlag;
      if(!empty($vorschlag)) {
        $vorschlag->von='';
        $vorschlag->bis='';
        $vorschlag->pausen='';
      }
    }
  }
}


require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Berichtsheft '.$tn->tnname);
$seite->anfangGenerieren();
$tn->makeSehen('sehen');
if(empty($wochen)) {
?>
Noch keine Einträge<br />
<?php
} else {
?>
<script>
const tagnamen=<?= json_encode(array_values($tagnamen)) ?>;
const wochenByKw=<?= json_encode($wochenByKw) ?>;
function woche_anzeigen() {
  let sel=document.getElementById('woche_select');
  let w=wochenByKw[sel.value];
  document.getElementById('kurs').href=w.kurs ? 'kurs_sehen.php?kursid='+w.kurs.id : '#';
  document.getElementById('kurs').innerText=w.kurs ? 'Unterricht: '+(w.kurs.modultitel || w.kurs.titel) : '';
  document.getElementById('dozent').innerText=w.kurs && w.kurs.dozentname ? 'Dozent: '+w.kurs.dozentname : '';
  for(let tagname of tagnamen) {
    let t=w.tage[tagname];
    document.getElementById('ferien_'+tagname).innerText=t.ferien ? t.ferien.anlass : '';
    document.getElementById('tag_'+tagname).innerText=t.tag;
    document.getElementById('von_'+tagname).innerText=(t.eintrag ? t.eintrag.von : '') || (t.vorschlag ? t.vorschlag.von : '');
    document.getElementById('bis_'+tagname).innerText=(t.eintrag ? t.eintrag.bis : '') || (t.vorschlag ? t.vorschlag.bis : '');
    document.getElementById('pausen_'+tagname).innerText=(t.eintrag ? t.eintrag.pausen : '') || (t.vorschlag ? t.vorschlag.pausen : '');
    document.getElementById('was_'+tagname).innerText=(t.eintrag ? t.eintrag.was : '') || (t.vorschlag ? t.vorschlag.was : '');
  }
}
document.body.onload=woche_anzeigen;
</script>
<select id="woche_select" onchange="woche_anzeigen()">
<?php
  $jetzt=date('D')=='Mon' ? strtotime('last monday') : strtotime('-1 week',strtotime('last monday'));
  for($w=$beginn;$w<=$ende;$w=strtotime('+1 week',$w)) {
?>
  <option value="<?= date('Y-W',$w) ?>" <?= $w==$jetzt ? 'selected' : '' ?>>KW <?= date('W',$w) ?> - <?= date('d.m.Y',$w) ?> - <?= date('d.m.Y',strtotime('+6 days',$w)) ?></option>
<?php
  }
?>
</select>
<style>
.zeit {
  text-align:right;
  padding-right:1ch;
  border-right:1px solid white;
}
</style>
<h2><a id="kurs" href="#"></a><div id="dozent"></div></h2>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
foreach($tagnamen as $en=>$tagname) {
?>
  <tr>
    <th rowspan="3"><?= $tagname ?></th>
    <td rowspan="3" id="tag_<?= $tagname ?>"></td>
    <td class="zeit">von</td>
    <td id="von_<?= $tagname ?>"></td>
    <td rowspan="3"><div id="ferien_<?= $tagname ?>" class="ferien"></div><div id="was_<?= $tagname ?>"></div></td>
  </tr>
  <tr>
    <td class="zeit">bis</td>
    <td id="bis_<?= $tagname ?>"></td>
  </tr>
  <tr>
    <td class="zeit">Pausen:</td>
    <td><span id="pausen_<?= $tagname ?>"></span>&nbsp;h</td>
  </tr>
<?php
}
?>
</table>
<?php
}
?>
<br />
<a href="tn.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>