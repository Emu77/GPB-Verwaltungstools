<?php
class BeratungBerater {
  static function einenLaden($id,$className) {
    global $db;
    if($id>0){
      $result=$db->query("select * from gpb_berater where id=".$id." limit 1");
      $berater=$result->fetch_object($className);
      $result->free();
    } else {
      $berater=false;
    }
    if(!empty($berater)) {
      $berater->init();
    }
    return $berater;
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
  <tr>
    <th>Tel</th>
    <td><?= $this->tel ?></td>
  </tr>
  <tr>
    <th>Email</th>
    <td><?= $this->email ?></td>
  </tr>
  <tr>
    <th>Ort</th>
    <td><?= $this->ort ?></td>
  </tr>
  <tr>
    <th>Signatur</th>
    <td><?= $this->signaturbild ?></td>
  </tr>
  <tr class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">
    <th>Moodle-ID</th>
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
  function makeMoodleTd() {
?>
    <td>
<?php
    if($this->moodleid>0) {
      global $moodleurl;
?>
      <a href="<?= $moodleurl ?>user/profile.php?id=<?= $this->moodleid ?>" target="moodle"><?= $this->moodleid ?></a>
<?php
    }
?>
    </td>
<?php
  }
  function makeBearbeitenTd() {
?>
    <td>
      <a href="berater_bearbeiten.php?beraterid=<?= $this->id ?>">Bearbeiten</a>
      <a href="berater_sehen.php?beraterid=<?= $this->id ?>">Sehen</a>
    </td>
<?php
  }
}
?>