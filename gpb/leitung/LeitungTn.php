<?php
require_once '../Tn.php';
class LeitungTn extends Tn {
 
  static function refsLaden($liste) {
    if(!empty($liste->byId)) {
      Tn::refsMassnahmenLaden($liste);
      Tn::refsKlassenLaden($liste);
    }
  }

  static function makeHeaderTr() {
    global $moodleisttest,$ich;
?>
  <tr>
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
  
  function makeTr() {
    global $ich;
?>
  <tr>
<?php
    $this->makeNameTds();
    $this->makeBerufTd();
    $this->makeNutzernameTd();
    $this->makeMoodleTd();
    if($ich->massnahmenanzeigen) $this->makeMassnahmenTd();
    $this->makeKlassenTd();
    $this->makeBerichtsheftTd('center');
    $this->makeBearbeitenTd();
?>
  </tr>
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
      <a href="../verwaltung/tn_sehen.php?tnid=<?= $this->id ?>">Sehen</a>
      <a href="../verwaltung/tn_loginas.php?tnid=<?= $this->id ?>">LoginAs</a>
    </td>
<?php
  }
  
  function makeSehen($classname='') {
    global $moodleisttest,$ich;
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="<?= $classname ?>">
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
<?php if($ich->massnahmenanzeigen) { ?>
  <tr>
    <th>Maßnahmen</th>
<?php
    $this->makeMassnahmenTd();
?>
  </tr>
<?php } ?>
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