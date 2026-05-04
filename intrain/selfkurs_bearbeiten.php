<?php
require_once 'check_login.php';
require_once 'IntrainSelfkurs.php';

if(isset($_GET['selfkursid']) && $_GET['selfkursid']!='0') {
  $selfkurs=Selfkurs::einenLaden((int)$_GET['selfkursid'],'IntrainSelfkurs');
  if(empty($selfkurs)) {
    header('Location:selfkurse.php');
    exit;
  }
  $selfkurs->moodleerstellen=($selfkurs->moodleid<=0);
} else {
  $titel=isset($_GET['titel']) ? $_GET['titel'] : '';
  $moodleid=isset($_GET['moodleid']) ? (int)$_GET['moodleid'] : 0;
  $selfkurs=(object)array(
    'id'=>0,
    'titel'=>$titel,
    'moodleerstellen'=>($moodleid<=0),
    'moodleid'=>$moodleid
  );
}
if(isset($_SESSION['fehler']['selfkurs'])) {
  $fehler=$_SESSION['fehler'];
  unset($_SESSION['fehler']);
  foreach($fehler['selfkurs'] as $k=>$v) {
    $selfkurs->$k=$v;
  }
}

require_once 'IntrainSeite.php';
$seite=new IntrainSeite('inTrain-Kurs '.$selfkurs->titel.' bearbeiten');
$seite->anfangGenerieren();
?>
<script>
function moodleerstellen_geaendert() {
  let erstellen=document.getElementById('moodleerstellen').checked;
  document.getElementById('moodleid').disabled=erstellen;
}
</script>
<form action="selfkurs_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="id" value="<?= $selfkurs->id ?>" />
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="bearbeiten">
  <tr>
    <th>Titel</th>
    <td>
      <input type="text" name="titel" id="titel" value="<?= htmlentities($selfkurs->titel,ENT_COMPAT) ?>" style="width:400px;" />
    </td>
    <td class="fehler"><?= isset($fehler['titel']) ? $fehler['titel'] : '' ?></td>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">
<?php
if($selfkurs->id>0 && $selfkurs->moodleid>0) {
?>
      <a href="<?= $moodleurl ?>course/view.php?id=<?= $selfkurs->moodleid ?>" target="moodle">Moodle-ID=<?= $selfkurs->moodleid ?></a>
<?php
} else {
?>
      Moodle-Kurs wird automatisch erstellt.
<?php
}
?>
    </td>
    <td class="fehler"><?= isset($fehler['moodleid']) ? $fehler['moodleid'] : '' ?></td>
  </tr>
  <tr>
    <th></th>
    <td>
      <input type="submit" value="Speichern" />
      <a href="selfkurse.php">Abbrechen</a>
    </td>
    <td class="fehler"><?= isset($fehler['speichern']) ? $fehler['speichern'] : '' ?></td>
  </tr>
</table>
</form>
<?php
$seite->endeGenerieren();
?>