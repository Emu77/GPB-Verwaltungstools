<?php
require_once 'Ferien.php';

class Klasse {
  static function eineLaden($id,$className) {
    global $db;
    if($id>0){
      $result=$db->query("select k.* 
        from gpb_klasse_view k
        where k.id=".$id." limit 1");
      $klasse=$result->fetch_object($className);
      $result->free();
    } else {
      $klasse=false;
    }
    if(!empty($klasse)) {
      $klasse->init();
      $klasse->ferienids=array();
      $klasse->ferien=array();
      $result=$db->query("select * 
        from gpb_ferien 
        where (art='Klassenferien' and id in(select ferienid from gpb_klasse_ferien where klasseid=".$klasse->id."))
           or (art='Institutsferien' and ort='".addslashes($klasse->ort)."')
        order by beginn,ende");
      while($row=$result->fetch_object('Ferien')) {
        $row->init();
        $klasse->ferien[]=$row;
        $klasse->ferienids[]=$row->id;
      }
      $result->free();
    }
    return $klasse;
  }
  
  function init() {
    $this->von=empty($this->beginn) || $this->beginn=='0000-00-00' ? 0 : strtotime($this->beginn);
    $this->bis=empty($this->ende) || $this->ende=='0000-00-00' ? 0 : strtotime($this->ende);
  }
  
  function makeSehen() {
    global $moodleisttest;
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Bezeichnung</th>
    <td><?= $this->bezeichnung?></td>
  </tr>
  <tr>
    <th>Beruf</th>
    <td><?= $this->berufkuerzel ?></td>
  </tr>
  <tr>
    <th>Ort</th>
    <td><?= $this->ort ?></td>
  </tr>
  <tr>
    <th>Beginn</th>
    <td><?= empty($this->von) ? '-' : date('d.m.Y',$this->von) ?></td>
  </tr>
  <tr>
    <th>Ende</th>
    <td><?= empty($this->bis) ? '-' : date('d.m.Y',$this->bis) ?></td>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-Klassenkurs</th>
<?php
    $this->makeMoodleTd();
?>
  </tr>
<?php
    $this->makeAnzahlTNTr();
    $this->makeFerienTr();
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
  function makeMoodleTd() {
    global $moodleurl,$moodleisttest;
?>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">
<?php
    if($this->moodleid>0) {
?>
      <a href="<?= $moodleurl ?>course/view.php?id=<?= $this->moodleid ?>" target="moodle">Kurs-ID: <?= $this->moodleid ?></a>
<?php
    }
?>
    </td>
<?php
  }
  function makeAnzahlTNTr() {
?>
  <tr>
    <th>Anzahl TN</th>
    <td><?= $this->anzahlTN ?></td>
  </tr>
<?php
  }
  function makeFerienTr() {
    if(!empty($this->ferien)) {
?>
  <tr>
    <th>Institut- und Klassenferien</th>
    <td>
<?php
      foreach($this->ferien as $f) {
?>
      <a href="ferien_sehen.php?ferienid=<?= $f->id ?>"><?= date('d.m.Y',$f->von) ?><?= $f->bis==$f->von ? '' : ' - '.date('d.m.Y',$f->bis) ?> <?= $f->anlass ?></a><br />
<?php
      }
?>
    </td>
  </tr>
<?php
    }
  }
  function makeBearbeitenTd() {
?>
    <td></td>
<?php
  }
}
?>