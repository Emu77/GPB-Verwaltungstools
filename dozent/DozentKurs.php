<?php
require_once '../Kurs.php';
class DozentKurs extends Kurs {
  
  function pvLaden() {
    global $db;
    if(!$this->istPV) return;
    $this->klassenById=array();
    foreach($this->klassen as $klasse) {
      $this->klassenById[$klasse->id]=$klasse;
    }
    $result=$db->query("select * from gpb_pruefungsvorbereitung_klasse where kursid=".$this->id);
    while($row=$result->fetch_object()) {
      $this->klassenById[$row->klasseid]->pv=$row;
    }
    $result->free();
  }
  
  function makeDozentenTd($extras=null) {
?>
    <td class="dozenten" <?= empty($extras) ? '' : $extras ?>>
<?php
    foreach($this->dozenten as $d) {
?>
      <div><?= $d->vorname ?> <?= $d->nachname ?></div>
<?php
    }
?>
    </td>
<?php
  }
  function makeKlassenTd() {
?>
    <td class="klassen">
<?php
    if($this->istPV) {
      foreach($this->klassen as $k) {
        if(isset($k->pv)) {
          $pv=$k->pv->hatAP1 ? 'AP1' : '';
          $pv.=$k->pv->hatAP2 ? (empty($pv) ? 'AP2/schriftliche' : ' + AP2/schriftliche') : '';
          $pv.=$k->pv->hatMuendliche ? (empty($pv) ? 'mündliche/Projekt/Fachgespräch' : ' + mündliche/Projekt/Fachgespräch') : '';
          if(!empty($pv)) $pv=' ('.$pv.')';
        }
?>
      <div><?= $k->bezeichnung ?><?= isset($pv) ? $pv : '' ?></div>
<?php
      }
    } else {
      foreach($this->klassen as $k) {
?>
      <div><?= $k->bezeichnung ?></div>
<?php
      }
    }
?>
    </td>
<?php
  }
  function makeMiniTd($extras=null) {
?>
    <td <?= empty($extras) ? '' : $extras ?>>
<?php
    if($this->mini) {
?>
      <a href="mini_kurs_sehen.php?kursid=<?= $this->id ?>">Mini</a>
<?php
    }
?>
    </td>
<?php
  }
    function makeBearbeitenTd() {
    global $ich;
?>
    <td class="aktionen">
      <a href="kurs_sehen.php?kursid=<?= $this->id ?>">Sehen</a>
      <a href="kurs_tn.php?kursid=<?= $this->id ?>">TN</a>
      <a class="<?= $this->kurzbericht_ok ? 'ok' : 'todo' ?>" href="kurzbericht.php?kursid=<?= $this->id ?>">Kurzbericht</a>
      <a class="<?= $this->tagesbericht_ok ? 'ok' : 'todo' ?>" href="tagesbericht.php?kursid=<?= $this->id ?>">Tagesbericht</a>
<?php
    if(in_array($ich->id,$this->dozentenids)) {
      if($this->hatProjektantrag) {
?>
      <a href="ihkprojektantrag.php?kursid=<?= $this->id ?>">IHK-Projektantrag</a>
<?php
      }
?>
      <a class="<?= $this->notenstatus=='todo' ? 'todo' : 'ok' ?>" href="kurs_il_noten.php?kursid=<?= $this->id ?>">IL+Noten</a>
<?php
      if($this->mini) {
?>
      <a href="mini_kurs_sehen.php?kursid=<?= $this->id ?>">Mini</a>
<?php
      }
?>
<?php
      if($this->bewertungStatus!='nochnicht') {
?>
      <a href="kurs_bewertung.php?kursid=<?= $this->id ?>">Bewertung</a>
<?php
      }
    }
?>
    </td>
<?php
  }
}
?>