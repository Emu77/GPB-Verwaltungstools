<?php
class Ferien {
  static function einenLaden($id,$className) {
    global $db;
    if($id>0){
      $result=$db->query("select * from gpb_ferien where id=".$id." limit 1");
      $ferien=$result->fetch_object($className);
      $result->free();
    } else {
      $ferien=false;
    }
    if(!empty($ferien)) {
      $ferien->init();
      if($ferien->art=='Klassenferien') {
        $result=$db->query("select * from gpb_klasse_view where id in(select klasseid from gpb_klasse_ferien where ferienid=".$ferien->id.") order by bezeichnung");
        while($row=$result->fetch_object()) {
          $ferien->klassen[]=$row;
          $ferien->klassenids[]=$row->id;
        }
        $result->free();
      }
    }
    return $ferien;
  }
  
  static function refsLaden($liste) {
    global $db;
    if(empty($liste->byId)) return;
    $klassenById=array();
    $result=$db->query("select * from gpb_klasse_view where id in(select klasseid from gpb_klasse_ferien where ferienid in(".implode(',',array_keys($liste->byId))."))");
    while($row=$result->fetch_object()) {
      $klassenById[$row->id]=$row;
    }
    $result->free();
    $result=$db->query("select * from gpb_klasse_ferien where ferienid in(".implode(',',array_keys($liste->byId)).")");
    while($row=$result->fetch_object()) {
      $liste->byId[$row->ferienid]->klassen[]=$klassenById[$row->klasseid];
      $liste->byId[$row->ferienid]->klassenids[]=$row->klasseid;
    }
    $result->free();
  }
  
  static function makeHeaderTr() {
?>
  <tr>
    <th>KW</th>
    <th>Beginn</th>
    <th>Ende</th>
    <th>Anlass</th>
    <th>Art</th>
    <th>Ort</th>
    <th>Klassen</th>
    <th></th>
  </tr>
<?php
  }
  
  function init() {
    $this->von=strtotime($this->beginn);
    $this->bis=strtotime($this->ende);
    $this->kw=array();
    for($wann=$this->von;$wann<=$this->bis;$wann=strtotime('+1 week',$wann)) {
      $this->kw[]=date('Y-W',$wann);
    }
    $this->klassenids=array();
    $this->klassen=array();
  }
  
  function istAllgemeiner($andere) {
    if($this->art=='Feiertag') return true;
    if($andere->art=='Feiertag') return false;
    if($this->art=='GPB Ferien') return true;
    if($andere->art=='GPB Ferien') return false;
    if($this->art=='Institutsferien') return true;
    if($andere->art=='Institutsferien') return false;
    return false;
  }
  
  function makeTr() {
?>
  <tr>
<?php
    $this->makeZeitraumTds();
    $this->makeAnlassTd();
    $this->makeArtTd();
    $this->makeOrtTd();
    $this->makeKlassenTd();
    $this->makeBearbeitenTd();
?>
  </tr>
<?php
  }
  function makeZeitraumTds() {
?>
    <td>
<?php
    foreach($this->kw as $kw) {
?>
      <div>KW <?= substr($kw,5) ?></div>
<?php
    }
?>
    </td>
<?php
    if($this->bis==$this->von) {
?>
    <td colspan="2"><?= date('d.m.Y',$this->von) ?></td>
<?php
    } else {
?>
    <td><?= date('d.m.Y',$this->von) ?></td>
    <td><?= date('d.m.Y',$this->bis) ?></td>
<?php
    }
  }
  function makeAnlassTd() {
?>
    <td><?= $this->anlass ?></td>
<?php
  }
  function makeArtTd() {
?>
    <td><?= $this->art ?></td>
<?php
  }
  function makeOrtTd() {
?>
    <td><?= $this->ort ?></td>
<?php
  }
  function makeKlassenTd() {
?>
    <td class="klassen">
<?php
    foreach($this->klassen as $k) {
?>
      <a href="klasse_sehen.php?klasseid=<?= $k->id ?>" style="display:block;"><?= $k->bezeichnung ?></a>
<?php
    }
?>
    </td>
<?php
  }
  function makeBearbeitenTd() {
?>
    <td></td>
<?php
  }
  
  function makeSehen() {
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>KW</th>
    <td>
<?php
    foreach($this->kw as $kw) {
?>
      <div>KW <?= substr($kw,5) ?></div>
<?php
    }
?>
    </td>
  </tr>
<?php
    if($this->bis==$this->von) {
?>
  <tr>
    <th>Datum</th>
    <td><?= date('d.m.Y',$this->von) ?></td>
  </tr>
<?php
    } else {
?>
  <tr>
    <th>Beginn</th>
    <td><?= date('d.m.Y',$this->von) ?></td>
  </tr>
  <tr>
    <th>Ende</th>
    <td><?= $this->bis==$this->von ? '' : date('d.m.Y',$this->bis) ?></td>
  </tr>
<?php
    }
?>
  <tr>
    <th>Anlass</th>
<?php
    $this->makeAnlassTd();
?>
  </tr>
  <tr>
    <th>Art</th>
    <td><?= $this->art ?></td>
  </tr>
<?php
  if($this->art=='Institutsferien' || $this->art=='Klassenferien') {
?>
  <tr>
    <th>Ort</th>
    <td><?= $this->ort ?></td>
  </tr>
<?php
  }
  if($this->art=='Klassenferien') {
?>
  <tr>
    <th>Klassen</th>
<?php
    $this->makeKlassenTd();
?>
  </tr>
<?php
  }
?>
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