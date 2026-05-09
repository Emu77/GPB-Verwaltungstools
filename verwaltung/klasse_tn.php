<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungKlasse.php';
require_once 'VerwaltungTn.php';

$klasse=Klasse::eineLaden(isset($_GET['klasseid']) ? (int)$_GET['klasseid'] : 0,'VerwaltungKlasse');
if(empty($klasse)) {
  header('Location:klassen.php');
  exit;
}

$stmt=$db->prepare("select * from gpb_tn_view where id in(select tnid from gpb_klasse_tn where klasseid=".$klasse->id.") order by nachname,vorname");
$tns=new Liste('VerwaltungTn',$stmt);
VerwaltungTn::refsLaden($tns);

$tnmitisids=array();
foreach($tns->alle as $tn) {
  if($tn->moodleid>0) {
    $tnmitisids[]=$tn->moodleid;
  }
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Teilnehmer der Klasse '.$klasse->bezeichnung);
$seite->anfangGenerieren();
$klasse->makeSehen();
if(empty($tns->alle)) {
  echo "Keine TN gefunden.";
} else {
  if($klasse->moodleid>0) {
?>
<script>
function moodlesync() {
  let daten={
      'rolename':'student',
      'courseid':<?= $klasse->moodleid ?>,
      'userids':<?= json_encode($tnmitisids) ?>,
      'ersetzen':true
  };
  let req=new XMLHttpRequest();
  req.onreadystatechange=function() {
    if(this.readyState != 4) return;
    let ergebnis=JSON.parse(this.responseText);
    if(ergebnis=='ok' || ergebnis=='"ok"') {
      alert('Anmeldungen aktualisiert!');
    } else {
      console.log(ergebnis);
      alert(this.responseText);
    }
  };
  let url='<?= $moodleurl ?>webservice/rest/server.php?wstoken=<?= $moodletoken ?>&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='+encodeURIComponent(JSON.stringify(daten));
  req.open('GET',url);
  req.send();
}
</script>
<a href="javascript:moodlesync()" style="margin-top:1em;margin-bottom:1em;">Anmeldungen in Moodle synchronisieren</a>
<?php
  }
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
VerwaltungTn::makeHeaderTr();
foreach($tns->alle as $tn) {
  $tn->makeTr();
}
?>
</table>
<?php
}
$seite->endeGenerieren();
?>