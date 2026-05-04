<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'DozentKurs.php';
require_once 'DozentTn.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'DozentKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}

$stmt=empty($kurs->klassenids) ? null : $db->prepare("select distinct tn.*
  from gpb_klasse_tn ktn
  join gpb_tn_view tn on tn.id=ktn.tnid
  where ktn.klasseid in(".implode(',',$kurs->klassenids).")
  and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<='".$kurs->ende."')
  and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>='".$kurs->beginn."') 
  order by ".$ich->tnsortierung);
$tns=new Liste('DozentTn',$stmt);

if($kurs->istPV) {
  $kurs->pvLaden();
  if(!empty($kurs->klassenids) && !empty($tns->alle)) {
    $result=$db->query("select *
      from gpb_klasse_tn ktn
      where ktn.klasseid in(".implode(',',$kurs->klassenids).") and ktn.tnid in(".implode(',',array_keys($tns->byId)).")
        and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<='".$kurs->ende."')
        and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>='".$kurs->beginn."') 
      order by ktn.einstieg,ktn.ausstieg");
    while($row=$result->fetch_object()) {
      $tn=$tns->byId[$row->tnid];
      $klasse=$kurs->klassenById[$row->klasseid];
      if(isset($klasse->pv)) {
        $tn->pv=$klasse->pv;
      }
    }
    $result->free();
  }
}

require_once 'DozentSeite.php';
$seite=new DozentSeite('Teilnehmer des Kurses '.$kurs->titel);
$seite->anfangGenerieren();
$kurs->makeSehen();
if(empty($kurs->klassen)) {
  echo "Keine Klassen.<br />\n";
} else if(empty($tns->alle)) {
  echo "Keine TN.<br />\n";
} else {
?>
Sortierung: <a href="tnsortierung_aendern.php?url=<?= urlencode('kurs_tn.php?kursid='.$kurs->id) ?>&kriterium=vorname">VN</a>
  <a href="tnsortierung_aendern.php?url=<?= urlencode('kurs_tn.php?kursid='.$kurs->id) ?>&kriterium=nachname">NN</a><br />
<a href="kurs_tn_csv.php?kursid=<?= $kurs->id ?>">Liste herunterladen</a>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
  DozentTn::makeHeaderTr($kurs->istPV);
  foreach($tns->alle as $tn) {
    $tn->makeTr($kurs->istPV);
  }
?>
</table>
<?php
}
?>
<br />
<a href="kurse.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>