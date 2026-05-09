<?php
require_once 'check_login.php';

if(!$ich->berichtsheft_offen) {
  header('Location:kurse.php');
  exit;
}

$beginn=isset($_GET['datum']) ? strtotime($_GET['datum']) : 0;
if(empty($beginn)) {
  $beginn=strtotime(date('Y-m-d'));
}
if(date('D',$beginn)!='Mon') {
  $beginn=strtotime('last monday',$beginn);
}
$ende=strtotime('+6 days',$beginn);

$tagnamen=array('Mon'=>'Montag','Tue'=>'Dienstag','Wed'=>'Mittwoch','Thu'=>'Donnerstag','Fri'=>'Freitag','Sat'=>'Samstag','Sun'=>'Sonntag');

$eintraege=array();
$result=$db->query("select * from gpb_berichtsheft where tnid=".$ich->id." and datum>='".date('Y-m-d',$beginn)."' and datum<='".date('Y-m-d',$ende)."' order by datum");
while($row=$result->fetch_object()) {
  $row->date=strtotime($row->datum);
  $row->tagname=$tagnamen[date('D',$row->date)];
  $row->tag=date('d.m.Y',$row->date);
  $row->von=date('h:m',strtotime($row->beginn));
  $row->bis=date('h:m',strtotime($row->ende));
  $row->kw='KW '.date('W',$row->date);
  $row->vorschlagBeginn='';
  $row->vorschlagEnde='';
  $row->vorschlagPausen='';
  $row->vorschlagWas='';
  $eintraege[$row->tagname]=$row;
}
$result->free();

for($t=$beginn;$t<=$ende;$t=strtotime('+1 day',$t)) {
  $tagname=$tagnamen[date('D',$t)];
  if(!isset($eintraege[$tagname])) {
    $eintraege[$tagname]=(object)array(
        'datum'=>date('Y-m-d',$t),
        'date'=>$t,
        'tagname'=>$tagname,
        'tag'=>date('d.m.Y',$t),
        'beginn'=>'','von'=>'',
        'ende'=>'','bis'=>'',
        'pausen'=>'',
        'was'=>'',
        'vorschlagBeginn'=>'',
        'vorschlagEnde'=>'',
        'vorschlagPausen'=>'',
        'vorschlagWas'=>''
      );
  }
}

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
}
$result->free();
if(!empty($kurseById)) {
  $result=$db->query("select kd.kursid,concat(d.vorname,' ',d.nachname) as dozentname
    from gpb_kurs_dozent kd
    join gpb_dozent d on d.id=kd.dozentid
    where kd.kursid in(".implode(',',array_keys($kurseById)).")");
  while($row=$result->fetch_object()) {
    $kurseById[$row->kursid]->dozentname.=(empty($kurseById[$row->kursid]->dozentname) ? "" : "\n").$row->dozentname;
  }
  $result->free();
  $result=$db->query("select * from gpb_kurs_tagesbericht where kursid in(".implode(',',array_keys($kurseById)).") and tag>='".date('Y-m-d',$beginn)."' and tag<='".date('Y-m-d',$ende)."'");
  while($row=$result->fetch_object()) {
    $tagname=$tagnamen[date('D',strtotime($row->tag))];
    $eintrag=$eintraege[$tagname];
    if($tagname=='Freitag') {
      $eintrag->vorschlagBeginn='08:15';
      $eintrag->vorschlagEnde='13:15';
      $eintrag->vorschlagPausen=0.5;
    } else if($tagname!='Samstag' && $tagname!='Sonntag') {
      $eintrag->vorschlagBeginn='08:15';
      $eintrag->vorschlagEnde='16:15';
      $eintrag->vorschlagPausen=1;
    }
    if(!empty($row->themen)) {
      $eintrag->vorschlagWas.=(empty($eintrag->vorschlagWas) ? "" : "\n").$row->themen;
    }
    if(!empty($row->kguil)) {
      $eintrag->vorschlagWas.=(empty($eintrag->vorschlagWas) ? "" : "\n").$row->kguil;
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
  for($t=max($beginn,strtotime($f->beginn)),$e=min(strtotime('-2 days',$ende),strtotime($f->ende));$t<=$e;$t=strtotime('+1 day',$t)) {
    $tagname=$tagnamen[date('D',$t)];
    $eintraege[$tagname]->vorschlagWas=$f->anlass;
  }
}

$result=$db->query("select min(einstieg) as mindatum,max(case when 
  (ausstieg is null or ausstieg='0000-00-00') 
  and (einstieg is not null and einstieg<>'0000-00-00') then current_date() else ausstieg end) as maxdatum from gpb_massnahme_tn where tnid=".$ich->id." and einstieg is not null and einstieg<>'0000-00-00'");
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
if(date('D',$maxwoche)=='Mon') {
  $maxwoche=strtotime('+1 week',$maxwoche);
} else {
  $maxwoche=strtotime('next monday',$maxwoche);
}

require_once 'TnSeite.php';
$seite=Seite::$menueByUrl['berichtsheft.php'];
$seite->anfangGenerieren();
?>
<button type="button" onclick="location.href='berichtsheft.php?datum=<?= date('Y-m-d') ?>'">Diese Woche</button>
<button type="button" onclick="location.href='berichtsheft.php?datum=<?= date('Y-m-d',strtotime('-1 week')) ?>'">Letzte Woche</button>
Am&nbsp;<input type="date" value="<?= date('Y-m-d',$beginn) ?>" min="<?= date('Y-m-d',$minwoche) ?>" max="<?= date('Y-m-d',max(strtotime('-1 day',$maxwoche),strtotime('today'))) ?>" onchange="location.href='berichtsheft.php?datum='+this.value" />
<select id="woche_select" onchange="location.href='berichtsheft.php?datum='+this.value">
<?php
  for($w=$minwoche;$w<$maxwoche;$w=strtotime('+1 week',$w)) {
?>
  <option value="<?= date('Y-m-d',$w) ?>" <?= $w==$beginn ? 'selected' : '' ?>>KW <?= date('W',$w) ?> - <?= date('d.m.Y',$w) ?> - <?= date('d.m.Y',strtotime('+6 days',$w)) ?></option>
<?php
  }
?>
</select><br />
<br />
<style>
.zeit {
  text-align:right;
  padding-right:1ch;
  border-right:1px solid white;
}
</style>
<script>
function speichern(tagname) {
  let elem=document.getElementById('von_'+tagname);
  if(!elem.value) {
    alert('Bitte Arbeitsanfang eingeben');
    elem.focus();
    return;
  }
  elem=document.getElementById('bis_'+tagname);
  if(!elem.value) {
    alert('Bitte Arbeitsende eingeben');
    elem.focus();
    return;
  }
  elem=document.getElementById('was_'+tagname);
  if(!elem.value) {
    alert('Bitte Tätigkeit eingeben');
    elem.focus();
    return;
  }
  document.getElementById('form_'+tagname).submit();
}
</script>
<?php
foreach($kurseById as $kursid=>$kurs) {
?>
<h2>Unterricht: <?= empty($kurs->modultitel) ? $kurs->titel : $kurs->modultitel ?><?= empty($kurs->dozentname) ? '' : '<br />Dozent: '.$kurs->dozentname ?></h2>
<?php
}
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
foreach($tagnamen as $en=>$tagname) {
  $eintrag=$eintraege[$tagname];
?>
  <form id="form_<?= $tagname ?>" action="berichtsheft_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="datum" value="<?= $eintrag->datum ?>" />
  <tr>
    <th rowspan="3"><?= $tagname ?></th>
    <td rowspan="3" id="tag_<?= $tagname ?>"><?= $eintrag->tag ?></td>
    <td class="zeit">von</td>
    <td><input id="von_<?= $tagname ?>" type="time" name="von" value="<?= empty($eintrag->beginn) ? $eintrag->vorschlagBeginn : $eintrag->beginn ?>" /></td>
    <td rowspan="3"><textarea id="was_<?= $tagname ?>" name="was" style="width:300px;height:5em;"><?= empty($eintrag->was) ? $eintrag->vorschlagWas : $eintrag->was ?></textarea></td>
    <td rowspan="3">
<?php
  if(isset($_SESSION['berichtsheft'][$eintrag->datum])) {
    echo $_SESSION['berichtsheft'][$eintrag->datum];
    unset($_SESSION['berichtsheft'][$eintrag->datum]);
  }
?>
      <button type="button" onclick="speichern('<?= $tagname ?>')">Speichern</button>
    </td>
  </tr>
  <tr>
    <td class="zeit">bis</td>
    <td><input id="bis_<?= $tagname ?>" type="time" name="bis" value="<?= empty($eintrag->ende) ? $eintrag->vorschlagEnde : $eintrag->ende ?>" /></td>
  </tr>
  <tr>
    <td class="zeit">Pausen:</td>
    <td><input id="pausen_<?= $tagname ?>" type="number" name="pausen" value="<?= empty($eintrag->pausen) ? $eintrag->vorschlagPausen : $eintrag->pausen ?>" min="0" max="24" step="0.25" style="width:50px;" />h</td>
  </tr>
  </form>
<?php
}
?>
</table>
<br />
<h2>PDF generieren</h2>
<button type="button" onclick="location.href='berichtsheft_pdf.php?datum=<?= date('Y-m-d') ?>'">Diese Woche</button>
<button type="button" onclick="location.href='berichtsheft_pdf.php?datum=<?= date('Y-m-d',strtotime('-1 week')) ?>'">Letzte Woche</button>
<button type="button" onclick="location.href='berichtsheft_pdf.php?datum=<?= date('Y-m-d',$beginn) ?>'">Angezeigte Woche</button><br />
<br />
<button type="button" onclick="location.href='berichtsheft_pdf.php?von=<?= date('Y-m-d',$minwoche) ?>&bis=<?= date('Y-m-d',strtotime('last sunday',$maxwoche)) ?>'">Alles</button><br />
<br />
Von <input type="date" name="von" value="<?= date('Y-m-d',$minwoche) ?>" min="<?= date('Y-m-d',$minwoche) ?>" max="<?= date('Y-m-d',max(strtotime('-1 day',$maxwoche),strtotime('today'))) ?>" /> bis <input type="date" name="bis" value="<?= date('Y-m-d',strtotime('last sunday',$maxwoche)) ?>" min="<?= date('Y-m-d',$minwoche) ?>" max="<?= date('Y-m-d',max(strtotime('-1 day',$maxwoche),strtotime('today'))) ?>"  /> <button type="button" onclick="location.href='berichtsheft_pdf.php?zeitraum=alles'">PDF Herunterladen</button>
<?php
$seite->endeGenerieren();
?>