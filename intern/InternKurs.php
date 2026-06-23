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
  static $phasen=array(
    0=>'' //GruKo usw.
    ,1=>'BQ'
    ,2=>'SQ'
    ,3=>'PV'
  );
  function makeKlassenText() {
    $this->phase=2;
    $bezs=array();
    foreach($this->klassen as $k) {
      if(empty($k->berufkuerzel)) $this->phase=0;
      if(substr($k->berufkuerzel,strlen($k->berufkuerzel)-2)=='BQ') $this->phase=1;
      if(strpos($k->bezeichnung,'PV')!==false || strpos(mb_strtolower($k->bezeichnung),'prak')!==false) $this->phase=3;
      if(strpos(mb_strtolower($k->bezeichnung),'praktikumssucher')!==false) $this->phase=2;
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