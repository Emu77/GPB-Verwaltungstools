<?php
class Kursvorlage {
  static function einenLaden($id,$className) {
    global $db,$ich;
    if($id>0){
      $result=$db->query("select * from gpb_kursvorlage where id=".$id." and id in(select kursvorlageid from gpb_kursvorlage_dozent where dozentid=".$ich->id.") limit 1");
      $vorlage=$result->fetch_object($className);
      $result->free();
    } else {
      $vorlage=false;
    }
    if(!empty($vorlage)) {
      $vorlage->init();
      $result=$db->query("select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent where id in(select dozentid from gpb_kursvorlage_dozent where kursvorlageid=".$vorlage->id.") order by nachname,vorname");
      while($row=$result->fetch_object()) {
        $vorlage->dozenten[]=$row;
        $vorlage->dozentenids[]=$row->id;
      }
      $result->free();
    }
    return $vorlage;
  }
  
  static function refsLaden($liste) {
    global $db;
    if(empty($liste->byId)) return;
    $dozentenById=array();
    $result=$db->query("select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent where id in(select dozentid from gpb_kursvorlage_dozent where kursvorlageid in(".implode(',',array_keys($liste->byId))."))");
    while($row=$result->fetch_object()) {
      $dozentenById[$row->id]=$row;
    }
    $result->free();
    $result=$db->query("select * from gpb_kursvorlage_dozent where kursvorlageid in(".implode(',',array_keys($liste->byId)).")");
    while($row=$result->fetch_object()) {
      $liste->byId[$row->kursvorlageid]->dozentenids[]=$row->dozentid;
      $liste->byId[$row->kursvorlageid]->dozenten[]=$dozentenById[$row->dozentid];
    }
    $result->free();
  }
   
  static function makeHeaderTr() {
    global $moodleisttest;
?>
  <tr>
    <th>Titel</th>
    <th>Dozenten</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
    <th></th>
  </tr>
<?php
  }
  
  function init() {
    $this->dozentenids=array();
    $this->dozenten=array();
  }
  
  function makeTr() {
?>
  <tr>
<?php
    $this->makeTitelTd();
    $this->makeDozentenTd();
    $this->makeMoodleTd();
    $this->makeBearbeitenTd();
?>
  </tr>
<?php
  }
  function makeTitelTd() {
?>
    <td><?= $this->titel ?></td>
<?php
  }
  function makeDozentenTd() {
?>
    <td class="dozenten">
<?php
    foreach($this->dozenten as $d) {
?>
      <a href="dozent_sehen.php?dozentid=<?= $d->id ?>" style="display:block;"><?= $d->vorname ?> <?= $d->nachname ?></a>
<?php
    }
?>
    </td>
<?php
  }
  function makeMoodleTd() {
    global $moodleisttest,$moodleurl;
?>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"><a href="<?= $moodleurl ?>course/view.php?id=<?= $this->moodleid ?>" target="moodle">Moodle-ID=<?= $this->moodleid ?></a></td>
<?php
  }
  function makeBearbeitenTd() {
?>
    <td class="aktionen"></td>
<?php
  }
  
  function makeSehen($classname='') {
    global $moodleisttest;
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="<?= $classname ?>">
  <tr>
    <th>Titel</th>
<?php
    $this->makeTitelTd();
?>
  </tr>
  <tr>
    <th>Dozenten</th>
<?php
    $this->makeDozentenTd();
?>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
<?php
    $this->makeMoodleTd();
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