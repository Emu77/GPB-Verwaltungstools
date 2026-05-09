<?php
class Dozent {
  static function einenLaden($id,$className) {
    global $db;
    if($id>0){
      $result=$db->query("select * from gpb_dozent where id=".$id." limit 1");
      $dozent=$result->fetch_object($className);
      $result->free();
    } else {
      $dozent=false;
    }
    if(!empty($dozent)) {
      $dozent->init();
    }
    return $dozent;
  }
  
  function init() {
    //nichts
  }
  
  function makeSehen($classname='') {
    global $moodleisttest;
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="<?= $classname ?>">
  <tr>
    <th>Anrede</th>
    <td><?= $this->anrede ?></td>
  </tr>
  <tr>
    <th>Vorname</th>
    <td><?= $this->vorname ?></td>
  </tr>
  <tr>
    <th>Nachname</th>
    <td><?= $this->nachname ?></td>
  </tr>
  <tr>
    <th>Nutzername</th>
    <td><?= $this->nutzername ?></td>
  </tr>
<?php
    $this->makeKontaktTrs();
?>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
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
  function makeKontaktTrs() {
    //Default: nichts
  }
  function makeMoodleTd() {
    global $moodleisttest;
?>
<td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">
<?php
    if($this->moodleid>0) {
      global $moodleurl;
?>
      <a href="<?= $moodleurl ?>user/profile.php?id=<?= $this->moodleid ?>" target="moodle"><?= $this->moodleid ?></a>
<?php
    }
    if(isset($_SESSION['fehler']['moodleid'])) {
?>
      <div class="fehler"><?= $_SESSION['fehler']['moodleid'] ?></div>
<?php
      unset($_SESSION['fehler']['moodleid']);
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
}
?>