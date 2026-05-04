<?php
require_once '../Kurs.php';
class InternKurs extends Kurs {
  function makeZeitraumTds() {
?>
    <td class="kw">
<?php
    if(count($this->kw)<=1) {
?>
      <div>KW <?= substr($this->kw[0],5) ?></div>
<?php
    } else {
?>
      <div>KW <?= substr($this->kw[0],5) ?> - <?= substr($this->kw[count($this->kw)-1],5) ?></div>
<?php
    }
?>
    </td>
<?php
  }
  function makeTitelTd() {
?>
    <td style="font-weight:bold;"><?= empty($this->modultitel) ? $this->titel : $this->modultitel ?></td>
<?php
  }
  function makeKlassenText() {
    $bezs=array();
    foreach($this->klassen as $k) {
      $bezs[]=$k->bezeichnung;
    }
    $this->klassenText=implode(', ',$bezs);
  }
  function makeKlassenTd() {
?>
    <td class="klassen"><?= $this->klassenText ?></td>
<?php
  }
  function makeDozentenTd($extras=null) {
    $namen=array();
    foreach($this->dozenten as $d) {
      $namen[]=$d->vorname.' '.$d->nachname;
    }
?>
    <td align="center" class="dozenten"><?= implode(', ',$namen) ?></td>
<?php
  }
}
?>