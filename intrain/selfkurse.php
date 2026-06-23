<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'IntrainSelfkurs.php';

$suche=isset($_SESSION['selfkurs_suche']) ? $_SESSION['selfkurs_suche'] : new Suche('titel');

$selfkurse=new Liste('IntrainSelfkurs',empty($suche->where) ? null : 
    $suche->prepare("select sk.* from gpb_selfkurs_view sk where","order by sk.titel limit 50"));

require_once 'IntrainSeite.php';
$seite=Seite::$menueByUrl['selfkurse.php'];
$seite->anfangGenerieren('');
?>
<a href="selfkurs_bearbeiten.php?selfkursid=0">Neuer inTrain-Kurs</a><br />
<br />
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
IntrainSelfkurs::makeHeaderTr();
?>
  <form action="selfkurse_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="redirect" value="selfkurse.php" />
  <tr>
<?php
$suche->makeStringInput('titel');
?>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"></td>
    <td></td>
<?php
$suche->makeSubmit();
?>
  </tr>
  </form>
<?php
foreach($selfkurse->alle as $selfkurs) {
  $selfkurs->makeTr();
}
?>
</table>
<?php
$seite->endeGenerieren();
exit;
?>