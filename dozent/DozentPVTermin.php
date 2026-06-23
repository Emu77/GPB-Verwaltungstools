<?php
require_once '../PVTermin.php';

class DozentPVTermin extends PVTermin {
  static function makeHeaderTr() {
    global $moodleisttest
?>
  <tr>
    <th>Wann</th>
    <th>Was</th>
    <th>Betrifft AP1</th>
    <th>Betrifft AP2 /<br />schriftliche Prüfung</th>
    <th>Betrifft mündliche<br />Prüfung / Projekt / Fachgespräch</th>
    <th>Klassen</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th></th>
  </tr>
<?php
  }
  
  static function refsLaden($liste) {
    global $db;
    if(empty($liste->byId)) return;
    $kursids=array();
    foreach($liste->alle as $termin) {
      $kursids[$termin->kursid]=true;
    }
    $result=$db->query("select * 
      from gpb_pruefungsvorbereitung_klasse pvk 
      join gpb_klasse_view k on k.id=pvk.klasseid
      where pvk.kursid in(".implode(',',array_keys($kursids)).")
      order by k.bezeichnung");
    while($row=$result->fetch_object()) {
      foreach($liste->alle as $termin) {
        if($termin->kursid!=$row->kursid) continue;
        if(($termin->betrifftAP1 && $row->hatAP1) || ($termin->betrifftAP2 && $row->hatAP2) || ($termin->betrifftMuendliche && $row->hatMuendliche)) {
          $termin->klassen[]=$row;
        }
      }
    }
    $result->free();
  }
  
  function init() {
    parent::init();
    $this->klassen=array();
  }
  function makeTr() {
    global $moodleisttest,$moodleurl;
?>
  <tr>
    <td>
      <?= empty($this->beginn) || $this->beginn=='0000-00-00' ? 'Wird rechtzeitig bekannt gegeben' : date('d.m.Y',$this->von) ?>
      <?= empty($this->beginn_uhrzeit) || $this->beginn_uhrzeit=='00:00:00' ? '' : date('H:i',$this->von) ?>
      <?= $this->keinEnde ? '' : '-' ?>
      <?= empty($this->ende) || $this->ende=='0000-00-00' ? '' : date('d.m.Y',$this->bis) ?>
      <?= empty($this->ende_uhrzeit) || $this->ende_uhrzeit=='00:00:00' ? '' : date('H:i',$this->bis) ?>
    </td>
    <td><?= $this->was ?></td>
    <td align="center"><?= $this->betrifftAP1 ? '✓' : '' ?></td>
    <td align="center"><?= $this->betrifftAP2 ? '✓' : '' ?></td>
    <td align="center"><?= $this->betrifftMuendliche ? '✓' : '' ?></td>
    <td>
<?php
    foreach($this->klassen as $klasse) {
?>
      <?= $klasse->bezeichnung ?><br />
<?php
    }
?>
    </td>
<?php
    if($this->moodleid>0) {
?>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" <?= empty($extras) ? '' : $extras ?>><a href="<?= $moodleurl ?>course/view.php?id=<?= $this->moodleid ?>" target="moodle">Moodle-ID=<?= $this->moodleid ?></a></td>
<?php
    } else {
?>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" <?= empty($extras) ? '' : $extras ?>>(in Planung)</td>
<?php
    }
?>
    <td>
      <a href="kurs_sehen.php?kursid=<?= $this->kursid ?>">Kurs sehen</a>
<?php
    if($this->hatProjektantrag) {
?>
      <a href="ihkprojektantrag.php?kursid=<?= $this->kursid ?>">IHK-Projektantrag</a>
<?php
    }
?>
    </td>
  </tr>
<?php
  }
}
?>