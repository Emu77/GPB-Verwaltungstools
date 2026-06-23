<?php
require_once 'Kurs.php';
class OeffentlichKurs extends Kurs {
    function makeDozentenTd($extras=null) {
      global $moodleisttest,$moodleurl;
?>
    <td align="center" class="dozenten <?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">
<?php
    foreach($this->dozenten as $d) {
      if($d->moodleid>0) {
?>
      <div><a href="<?= $moodleurl ?>user/profile.php?id=<?= $d->moodleid ?>" target="moodle">Moodle-Dozent-ID=<?= $d->moodleid ?></a></div>
<?php
      }
    }
?>
    </td>
<?php
  }
}
?>