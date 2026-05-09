<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungKurs.php';

$suche=isset($_SESSION['kurse_suche']) ? $_SESSION['kurse_suche'] : new Suche('am','modultitel','titel','ort','raum','ohneraum','klassebez','dozentname','moodleid','todo');
if(empty($suche->where)) {
  $heute=date('Y-m-d');
  $suche->am=$heute;
  $suche->addKriterium("beginn<=?",$heute,'s');
  $suche->addKriterium("ende>=?",$heute,'s');
  if(!empty($ich->ort)) {
    $suche->ort=$ich->ort;
    $suche->addKriterium('ort=?',$suche->ort,'s');
  }
}

$kurse=new Liste('VerwaltungKurs',$suche->prepare("select * from gpb_kurs_view where","order by beginn,ende,titel limit 50"));
Kurs::refsLaden($kurse);

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=VerwaltungSeite::$menueByUrl['../verwaltung/kurse.php'];
$seite->anfangGenerieren();
?>
<a href="kurs_bearbeiten.php?kursid=0">Neuer Kurs</a><br />
<br />
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-top:1em;">
<?php
VerwaltungKurs::makeHeaderTr(false);
?>
  <form action="kurse_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <tr>
    <td colspan="3">Am <input type="date" name="am" value="<?= $suche->am ?>" /></td>
<?php
$suche->makeStringInput('titel');
$suche->makeEnumInput('ort',array('-','Mitte','Neukölln'));
?>
    <td>
      <input type="text" name="raum" value="<?= isset($suche->raum) ? htmlentities($suche->raum,ENT_COMPAT) : '' ?>" style="width:100px;" /><br />
      <input type="checkbox" name="ohneraum" value="J" <?= $suche->ohneraum ? 'checked' : '' ?> /> ohne
    </td>
<?php
$suche->makeStringInput('klassebez');
$suche->makeStringInput('dozentname');
$suche->makeStringInput('moodleid',1,150,$moodleisttest ? 'testmoodle' : 'moodle');
?>
    <td>
<?php
$suche->makeBooleanInput('todo','unvollständig');
$suche->makeSubmit(false);
?>
    </td>
  </tr>
  </form>
<?php
foreach($kurse->alle as $kurs) {
  $kurs->makeTr(false);
}
?>
</table>
<?php
$seite->endeGenerieren();
?>