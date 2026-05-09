<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'DozentKurs.php';
require_once 'DozentTn.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'DozentKurs');
if(empty($kurs) || !$kurs->hatProjektantrag) {
  header('Location:kurse.php');
  exit;
}
$betreuer=array();
$betreuerById=array();
foreach($kurs->dozenten as $dozent) {
  $betreuer[]=$dozent;
  $betreuerById[$dozent->id]=$dozent;
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
    for($i=count($tns->alle)-1;$i>=0;--$i) {
      if(isset($tns->alle[$i]->pv) && !$tns->alle[$i]->pv->hatMuendliche) {
        unset($tns->byId[$tns->alle[$i]->id]);
        array_splice($tns->alle,$i,1);
      }
    }
  }
}

$leererAntrag=(object)array(
  'betreuerid'=>null,
  'ampel'=>'red',
  'bezeichnung'=>'',
  'bezeichnung_geaendertam'=>null,
  'beschreibung'=>'',
  'beschreibung_geaendertam'=>null,
  'zielsetzung'=>'',
  'zielsetzung_geaendertam'=>null,
  'zeitplan'=>'',
  'zeitplan_geaendertam'=>null,
  'daten_geaendertam'=>null,
  'hinweise'=>'',
  'hinweise_geaendertam'=>null,
  'hinweise_geaendertvon'=>'',
);

if(!empty($tns->byId)) {
  $betreuerids=array();
  $result=$db->query("select *,greatest(ifnull(bezeichnung_geaendertam,''),ifnull(beschreibung_geaendertam,''),ifnull(zielsetzung_geaendertam,''),ifnull(zeitplan_geaendertam,'')) as daten_geaendertam from gpb_ihkprojektantrag where kursid=".$kurs->id." and tnid in(".implode(',',array_keys($tns->byId)).")");
  while($row=$result->fetch_object()) {
    if($row->betreuerid && !isset($betreuerById[$row->betreuerid])) {
      $betreuerids[]=$row->betreuerid;
    }
    $tns->byId[$row->tnid]->antrag=$row;
  }
  $result->free();
  if(!empty($betreuerids)) {
    $result=$db->query("select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent where id in(".implode(',',$betreuerids).") order by nachname,vorname");
    while($row=$result->fetch_object()) {
      $betreuer[]=$row;
      $betreuerById[$row->id]=$row;
    }
    $result->free();
  }
}
function makeDatenTd($antrag,$spalte) {
  $sp=$spalte.'_geaendertam';
?>
    <td <?= empty($antrag->$sp) || (!empty($antrag->hinweise_geaendertam) && $antrag->hinweise_geaendertam>=$antrag->$sp) ? '' : 'class="geaendert"' ?>><?= $spalte=='bezeichnung' ? $antrag->$spalte : mb_strlen($antrag->$spalte).' Z.' ?></td>
<?php
}

require_once 'DozentSeite.php';
$seite=new DozentSeite('IHK-Projektantrag '.$kurs->titel);
$seite->anfangGenerieren();
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
<?php
DozentKurs::makeHeaderTr(false);
$kurs->makeTr(false);
?>
</table>
<?php
if(empty($kurs->klassen)) {
  echo "Keine Klassen.<br />\n";
} else if(empty($tns->alle)) {
  echo "Keine TN.<br />\n";
} else {
?>
<script>
var tnsortierung=<?= json_encode(isset($ich->ihkprojektantrag_tnsortierung) ? $ich->ihkprojektantrag_tnsortierung : explode(',',$ich->tnsortierung)) ?>;
const sortierspalten={
  'vorname':1,'nachname':2,'beruf':3,'betreuer':<?= $kurs->istPV ? 12 : 11 ?>
};
function compare_tn(tr0,tr1) {
  for(let s of tnsortierung) {
    if(s=='ktn.klasseid') continue;
    let t0=tr0.cells[sortierspalten[s]].innerText;
    let t1=tr1.cells[sortierspalten[s]].innerText;
    if(t0<t1) return -1;
    if(t0>t1) return 1;
  }
  return 0;
}
function tn_sortieren() {
  let table=document.getElementById('antraege');
  let trs=[];
  for(let i=table.rows.length-1;i>0;--i) { //0 ist header
    trs.push(table.rows[i]);
    table.rows[i].remove();
  }
  trs.sort(compare_tn);
  for(let tr of trs) {
    table.appendChild(tr);
  }
}
async function tn_sortierung_aendern(sortierung) {
  let response=await fetch('ihkprojektantrag_tnsortierung_aendern.php?sortierung='+sortierung);
  if (!response.ok) {
    alert('Konnte Sortierung nicht speichern :-(');
    return;
  }
  let txt=await response.text();
  if(!txt.startsWith('OK')) {
    alert(txt);
    return;
  }
  tnsortierung=JSON.parse(txt.substring(2));
  tn_sortieren();
}
async function ampel_wechseln(tnid) {
  let div=document.getElementById('ampel_'+tnid);
  let farbe=div.style.backgroundColor;
  farbe=farbe=='red' ? 'yellow' : farbe=='yellow' ? 'green' : 'red';
  let response=await fetch('ihkprojektantrag_ampel_speichern.php?kursid=<?= $kurs->id ?>&tnid='+tnid+'&ampel='+farbe);
  if (!response.ok) {
    alert('Konnte Ampel nicht speichern :-(');
    return;
  }
  let txt=await response.text();
  if(txt!='OK') {
    alert(txt);
    return;
  }
  div.style.backgroundColor=farbe;
}
</script>
<style>
.geaendert {
  font-weight:bold;
}
</style>
<table border="1" cellspacing="0" style="border-collapse:collapse;" id="antraege">
  <tr>
    <th>Anrede</th>
    <th><a href="javascript:tn_sortierung_aendern('vorname')">Vorname</a></th>
    <th><a href="javascript:tn_sortierung_aendern('nachname')">Nachname</a></th>
    <th><a href="javascript:tn_sortierung_aendern('beruf')">Beruf</a></th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
<?php
    if($kurs->istPV) {
?>
    <th>PV</th>
<?php
    }
?>
    <th>Projektbezeichnung</th>
    <th>Beschreibung</th>
    <th>Zielsetzung</th>
    <th>Zeitplan</th>
    <th>Ansehen</th>
    <th>Stand</th>
    <th><a href="javascript:tn_sortierung_aendern('betreuer')">Betreuung</a></th>
    <th>Zuletzt bearbeitet</th>
  </tr>
<?php
  foreach($tns->alle as $tn) {
    $antrag=isset($tn->antrag) ? $tn->antrag : $leererAntrag;
    $betreuer=$antrag->betreuerid ? $betreuerById[$antrag->betreuerid] : null;
?>
  <tr>
<?php
    $tn->makeNameTds();
    $tn->makeBerufTd();
    $tn->makeMoodleTd();
    if($kurs->istPV) {
      $pv='';
      if(isset($tn->pv)) {
        $pv=$tn->pv->hatAP1 ? 'AP1' : '';
        $pv.=$tn->pv->hatAP2 ? (empty($pv) ? 'AP2' : ' + AP2') : '';
        $pv.=$tn->pv->hatMuendliche ? (empty($pv) ? 'Projekt' : ' + Projekt') : '';
      }
?>
    <td><?= $pv ?></td>
<?php
    }
    makeDatenTd($antrag,'bezeichnung');
    makeDatenTd($antrag,'beschreibung');
    makeDatenTd($antrag,'zielsetzung');
    makeDatenTd($antrag,'zeitplan');
?>
    
    <td><a href="tn_ihkprojektantrag.php?kursid=<?= $kurs->id ?>&tnid=<?= $tn->id ?>" style="display:block;text-align:center;text-decoration:none;" target="ihkprojektantrag">&#x1F441;</a></td>
    <td align="center" style="cursor:pointer;" onclick="ampel_wechseln(<?= $tn->id ?>)"><div id="ampel_<?= $tn->id ?>" style="display:inline-block;width:1em;height:1em;border-radius:1em;background-color:<?= $antrag->ampel ?>">&nbsp;</div></td>
    <td><?= empty($betreuer) ? '' : $betreuer->dozentenname ?></td>
    <td><?= empty($antrag->hinweise_geaendertam) ? '' : date('d.m.Y H:i',strtotime($antrag->hinweise_geaendertam)) ?> <?= $antrag->hinweise_geaendertvon ?></td>
  </tr>
<?php
  }
?>
</table>
<script>
tn_sortieren();
</script>
<?php
}
?>
<br />
<a href="kurse.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>