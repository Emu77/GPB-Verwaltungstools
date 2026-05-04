<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungTn.php';

$suche=isset($_SESSION['tn_suche']) ? $_SESSION['tn_suche'] : new Suche('mitisid','tnname','berufkuerzel','massnkuerzel','klassebez');

$tns=new Liste('VerwaltungTn',empty($suche->where) ? null : 
    $suche->prepare("select * from gpb_tn_view where","order by nachname,vorname limit 50"));
VerwaltungTn::refsLaden($tns);

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=VerwaltungSeite::$menueByUrl['../verwaltung/tn.php'];
$seite->anfangGenerieren();
?>
<a href="tn_hohefehlzeiten_mitis.php" style="margin-bottom:1em;">Hohe Fehlzeiten (MITIS-Daten)</a>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
VerwaltungTn::makeHeaderTr();
?>
  <form action="tn_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <tr>
    <td><input type="text" name="mitisid" value="<?= $suche->mitisid>0 ? $suche->mitisid : '' ?>" style="width:75px;" /></td>
    <td colspan="3">Name: <input type="text" name="tnname" value="<?= htmlentities($suche->tnname,ENT_COMPAT) ?>" style="width:150px;" /></td>
<?php
$suche->makeStringInput('berufkuerzel');
?>
    <td></td>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"></td>
<?php
$suche->makeStringInput('massnkuerzel');
$suche->makeStringInput('klassebez');
?>
    <td></td>
<?php
$suche->makeSubmit();
?>
  </tr>
  </form>
<?php
foreach($tns->alle as $row) {
  $row->makeTr();
}
?>
</table>
<?php
$seite->endeGenerieren();
?>