<?php
require_once '../Tn.php';
class VerwaltungTn extends Tn {
  static function einenLaden($id,$className) {
    $tn=Tn::einenLaden($id,$className);
    if(!empty($tn)) {
      $tn->massnahmenLaden();
      $tn->klassenLaden();
      $tn->fehlzeitenNotizLaden();
    }
    return $tn;
  }
  
  static function refsLaden($liste) {
    if(!empty($liste->byId)) {
      Tn::refsMassnahmenLaden($liste);
      Tn::refsKlassenLaden($liste);
    }
  }
  static function fehlzeitenNotizenLaden($liste) {
    global $db;
    if(!empty($liste->byId)) {
      $result=$db->query("select * from gpb_fehlzeiten_notiz where tnid in(".implode(',',array_keys($liste->byId)).")");
      while($row=$result->fetch_object()) {
        $liste->byId[$row->tnid]->notiz=$row->notiz;
      }
      $result->free();
    }
  }
  static function makeHeaderTr($pv=false) {
    global $moodleisttest,$ich;
?>
  <tr>
    <th>MITIS-ID</th>
    <th>Anrede</th>
    <th>Vorname</th>
    <th>Nachname</th>
    <th>Beruf</th>
    <th>Nutzername</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
<?php if($ich->massnahmenanzeigen) { ?>
    <th>Maßnahmen</th>
<?php } ?>
    <th>Klassen</th>
<?php if($pv) { ?>
    <th>PV</th>
<?php } ?>
    <th>Berichtsheft</th>
    <th></th>
  </tr>
<?php
  }
  
  function init() {
    parent::init();
    $this->massnahmen=array();
    $this->klassen=array();
    $this->klassenids=array();
  }
  function fehlzeitenNotizLaden() {
    global $db;
    $result=$db->query("select * from gpb_fehlzeiten_notiz where tnid=".$this->id);
    $obj=$result->fetch_object();
    $this->fehlzeiten_notiz=$obj ? $obj->notiz : null;
    $result->free();
  }
  
  function makeTr($pv=false) {
    global $ich;
?>
  <tr>
<?php
    $this->makeMitisTd();
    $this->makeNameTds();
    $this->makeBerufTd();
    $this->makeNutzernameTd();
    $this->makeMoodleTd();
    if($ich->massnahmenanzeigen) $this->makeMassnahmenTd();
    $this->makeKlassenTd();
    if($pv) {
      $this->makePVTd();
    }
    $this->makeBerichtsheftTd('center');
    $this->makeBearbeitenTd();
?>
  </tr>
<?php
  }
  function makeMitisTd() {
?>
    <td align="center"><?= $this->mitisid ?></td>
<?php
  }
  function makeEmailTd() {
?>
    <td align="center"><a href="mailto:<?= htmlentities($this->email,ENT_COMPAT) ?>" title="<?= htmlentities($this->email,ENT_COMPAT) ?>">@</a></td>
<?php
  }
  function makeMassnahmenTd() {
?>
    <td><table border="0" cellspacing="0" style="border-collapse:collapse;width:100%;">
<?php
    foreach($this->massnahmen as $massn) {
?>
      <tr>
        <td><a href="massnahme_sehen.php?massnahmeid=<?= $massn->massnahmeid ?>"><?= $massn->kuerzel ?></a></td>
        <td><?= empty($massn->von) ? '' : date('d.m.Y',$massn->von) ?> - <?= empty($massn->bis) ? '' : date('d.m.Y',$massn->bis) ?></td>
      </tr>
<?php
    }
?>
    </table></td>
<?php
  }
  function makeKlassenTd() {
?>
    <td><table border="0" cellspacing="0" style="border-collapse:collapse;width:100%;">
<?php
    foreach($this->klassen as $klasse) {
?>
      <tr>
        <td><a href="klasse_sehen.php?klasseid=<?= $klasse->klasseid ?>"><?= $klasse->bezeichnung ?></a></td>
        <td><?= empty($klasse->von) ? '' : date('d.m.Y',$klasse->von) ?> - <?= empty($klasse->bis) ? '' : date('d.m.Y',$klasse->bis) ?></td>
      </tr>
<?php
    }
?>
    </table></td>
<?php
  }
  function makePVTd() {
    $t=$this->pv->hatAP1 ? 'AP1' : '';
    $t.=$this->pv->hatAP2 ? (empty($t) ? 'AP2/schriftliche' : ' + AP2/schriftliche') : '';
    $t.=$this->pv->hatMuendliche ? (empty($t) ? 'mündliche/Projekt/Fachgespräch' : ' + mündliche/Projekt/Fachgespräch') : '';
?>
    <td><?= $t ?></td>
<?php
  }
  function makeBerichtsheftTd($align='center') {
?>
    <td align="<?= $align ?>">
      <input type="checkbox" name="berichtsheft_offen" value="J" <?= $this->berichtsheft_offen ? 'checked' : '' ?> 
        onchange="location.href='tn_berichtsheft_oeffnen.php?tnid=<?= $this->id ?>&offen='+(this.checked ? 'J' : 'N')+'&zurueck='+encodeURIComponent(location.href);" />
      <a href="tn_berichtsheft.php?tnid=<?= $this->id ?>">Berichtsheft</a>
    </td>
<?php
  }
  function makeBearbeitenTd() {
?>
    <td>
      <a href="tn_sehen.php?tnid=<?= $this->id ?>">Sehen</a>
      <a href="tn_loginas.php?tnid=<?= $this->id ?>">LoginAs</a>
      <a href="tn_kurse.php?tnid=<?= $this->id ?>">Kurse</a>
      <a href="tn_anwesenheit.php?tnid=<?= $this->id ?>">Anwesenheit</a>
      <a href="tn_noten.php?tnid=<?= $this->id ?>">Noten</a>
      <a href="tn_tagesbericht.php?tnid=<?= $this->id ?>">Tagesbericht</a>
    </td>
<?php
  }
  
  function makeSehen($classname='') {
    global $moodleisttest,$ich;
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="<?= $classname ?>">
  <tr>
    <th>MITIS-ID</th>
<?php
    $this->makeMitisTd();
?>
  </tr>
<?php
    $this->makeNameTrs();
?>
  <tr>
    <th>Beruf</th>
<?php
    $this->makeBerufTd();
?>
  </tr>
  <tr>
    <th>Nutzername</th>
<?php
    $this->makeNutzernameTd();
?>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
<?php
    $this->makeMoodleTd();
?>
  </tr>
<?php 
    if($ich->massnahmenanzeigen) { 
?>
  <tr>
    <th>Maßnahmen</th>
<?php
      $this->makeMassnahmenTd();
?>
  </tr>
<?php
    }
?>
  <tr>
    <th>Klassen</th>
<?php
    $this->makeKlassenTd();
?>
  </tr>
  <tr>
    <th>Berichtsheft</th>
<?php
    $this->makeBerichtsheftTd('left');
?>
  </tr>
  <tr>
    <th></th>
<?php
    $this->makeBearbeitenTd();
?>
  </tr>
</table>
<br />
<?php
  }
}
?>