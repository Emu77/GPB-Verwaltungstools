<?php
class Raum {
  static function einenLaden($id,$className) {
    global $db;
    if($id>0){
      $result=$db->query("select * from gpb_raum r where id=".$id." limit 1");
      $raum=$result->fetch_object($className);
      $result->free();
    } else {
      $raum=false;
    }
    if(!empty($raum)) {
      $raum->init();
    }
    return $raum;
  }
  
  function init() {
    //nichts
  }
  
  function makeSehen() {
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Ort</th>
    <td><?= $this->ort ?></td>
  </tr>
  <tr>
    <th>Raum</th>
    <td><?= $this->tuer ?></td>
  </tr>
  <tr>
    <th>Zusatz</th>
    <td><?= $this->zusatz ?></td>
  </tr>
  <tr>
    <th>Art</th>
    <td><?= $this->art ?></td>
  </tr>
  <tr>
    <th>Anzahl Plätze</th>
    <td><?= $this->anzahlPlaetze<0 ? '' : $this->anzahlPlaetze ?></td>
  </tr>
  <tr>
    <th>Anzahl Computer</th>
    <td><?= $this->anzahlComputer<0 ? '' : $this->anzahlComputer ?></td>
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
  function makeBearbeitenTd() {
?>
    <td></td>
<?php
  }
}
?>