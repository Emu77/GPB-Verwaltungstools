<?php
class VerwaltungVerwalter {
  static function einenLaden($id,$className) {
    global $db;
    if($id>0){
      $result=$db->query("select * from gpb_verwalter where id=".$id." limit 1");
      $verwalter=$result->fetch_object($className);
      $result->free();
    } else {
      $verwalter=false;
    }
    if(!empty($verwalter)) {
      $verwalter->init();
    }
    return $verwalter;
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
    <th>Email</th>
    <td><?= $this->email ?></td>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
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
  </tr>
  <tr>
    <th>Maßnahmen anzeigen</th>
    <td><?= $this->massnahmenanzeigen ? 'Ja' : 'Nein' ?></td>
  </tr>
  <tr>
    <th>Ort</th>
    <td><?= $this->ort ?></td>
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
    <td>
      <a href="verwalter_bearbeiten.php?verwalterid=<?= $this->id ?>">Bearbeiten</a>
      <a href="verwalter_sehen.php?verwalterid=<?= $this->id ?>">Sehen</a>
    </td>
<?php
  }
}
?>