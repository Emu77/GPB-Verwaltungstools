<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'TnSelfkurs.php';
require_once 'TnKurs.php';
require_once 'TnPVTermin.php';

$selfsuche=isset($_SESSION['selfkurse_suche']) ? $_SESSION['selfkurse_suche'] : new Suche('von','bis','titel','moodleid','nurich');
if(empty($selfsuche->where)) {
  $selfsuche->nurich=true;
  $selfsuche->addKriterium("(sktn.tnid=? and sktn.einstieg is not null and sktn.einstieg<>'0000-00-00')",$ich->id,'i');
}
$selfkurse=new Liste('TnSelfkurs',$selfsuche->prepare("select * 
  from gpb_selfkurs_view sk
  left outer join gpb_selfkurs_tn sktn on sktn.selfkursid=sk.id and sktn.tnid=".$ich->id."
  where","order by sktn.einstieg,sktn.ausstieg,sk.titel"));

$suche=isset($_SESSION['kurse_suche']) ? $_SESSION['kurse_suche'] : new Suche('von','bis','titel','ort','raum','klassebez','dozentname','nurich','moodleid');
if(empty($suche->where)) {
  $suche->nurich=true;
  $suche->addKriterium("id in(select k.id
      from gpb_klasse_tn ktn 
      join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
      join gpb_kurs_view k on k.id=kk.kursid
      where ktn.tnid=?
      and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
      and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn))",$ich->id,'i');
}

$kurse=new Liste('TnKurs',$suche->prepare("select * from gpb_kurs_view where (moodleid>0 or sichtbar) and","order by beginn,ende limit 50"));
Kurs::refsLaden($kurse);
TnKurs::vonMirLaden($kurse);

$pvtermine=new Liste('TnPVTermin',$db->prepare("select distinct k.titel,k.moodleid
    ,pvk.hatMuendliche and k.hatProjektantrag as hatProjektantrag
    ,pvt.*
  from gpb_klasse_tn ktn 
  join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
  join gpb_kurs_view k on k.id=kk.kursid
  join gpb_pruefungsvorbereitung_klasse pvk on pvk.kursid=k.id and pvk.klasseid=ktn.klasseid
  join gpb_pruefungsvorbereitung_termin pvt on pvt.kursid=k.id and ((pvk.hatAP1 and pvt.betrifftAP1) or (pvk.hatAP2 and pvt.betrifftAP2) or (pvk.hatMuendliche and pvt.betrifftMuendliche))
  where ktn.tnid=".$ich->id."
   and k.istPV and (k.sichtbar or k.moodleid>0)
   and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
   and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)
  and ifnull(pvt.ende,pvt.beginn)>=current_date()
  order by beginn,ende,titel"));
$pvtermineByKursid=array();
foreach($pvtermine->alle as $termin) {
  if(isset($pvtermineByKursid[$termin->kursid])) {
    $pvtermineByKursid[$termin->kursid][]=$termin;
  } else {
    $pvtermineByKursid[$termin->kursid]=array($termin);
  }
}

require_once 'TnSeite.php';
$seite=Seite::$menueByUrl['kurse.php'];
$seite->anfangGenerieren('');
if(!empty($pvtermine->alle)) {
?>
<h2>Prüfungs-Termine</h2>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
<?php
  TnPVTermin::makeHeaderTr(true);
  foreach($pvtermineByKursid as $kursid=>$termine) {
    $erst=true;
    foreach($termine as $termin) {
      $termin->makeTr($erst ? count($termine) : 0);
      $erst=false;
    }
  }
?>
</table>
<?php
}
?>
<h2>inTrain-Kurse</h2>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
  <form action="selfkurse_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
<?php
TnSelfkurs::makeHeaderTr();
?>
  <tr>
<?php
$selfsuche->makeVonBisInput(3);
$selfsuche->makeStringInput('titel');
$selfsuche->makeStringInput('moodleid',1,100,$moodleisttest ? 'testmoodle' : 'moodle');
?>
    <td>
      <input type="submit" value="Suchen" />
    </td>
  </tr>
  </form>
<?php
foreach($selfkurse->alle as $selfkurs) {
  $selfkurs->makeTr();
}
?>
</table>

<h2>Klassen-Kurse</h2>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
<?php
TnKurs::makeHeaderTr(false);
?>
  <form action="kurse_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <tr>
<?php
$suche->makeVonBisInput(3);
$suche->makeStringInput('titel');
?>
    <td><select name="ort">
      <option value="">Alle</option>
      <option value="-" <?= $suche->ort=='-' ? 'selected' : '' ?>>-</option>
      <option value="Mitte" <?= $suche->ort=='Mitte' ? 'selected' : '' ?>>Mitte</option>
      <option value="Neukölln" <?= $suche->ort=='Neukölln' ? 'selected' : '' ?>>Neukölln</option>
    </select></td>
<?php
$suche->makeStringInput('raum');
$suche->makeStringInput('klassebez');
$suche->makeStringInput('dozentname');
$suche->makeStringInput('moodleid',1,100,$moodleisttest ? 'testmoodle' : 'moodle');
?>
    <td>
      <input type="checkbox" name="nurich" value="J" <?= $suche->nurich ? 'checked' : '' ?> /> Nur meine Kurse<br />
    </td>
    <td>
      <input type="submit" value="Suchen" />
    </td>
  </tr>
  </form>
<?php
foreach($kurse->alle as $kurs) {
  $kurs->makeTr(false);
}
?>
</table>
<?php
$seite->endeGenerieren();
exit;
?>