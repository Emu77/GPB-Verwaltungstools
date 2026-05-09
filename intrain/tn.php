<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'IntrainTn.php';

$suche=isset($_SESSION['tn_suche']) ? $_SESSION['tn_suche'] : new Suche('tnname','massnbez','selfkurstitel');
//if(empty($suche->where)) {
//  $suche->massnbez='MODULAR';
//  $suche->addKriterium("id in(select tnid from gpb_massnahme_tn where massnahmeid in(select id from gpb_massnahme m where m.kuerzel like ?))",
//    '%'.$suche->massnbez.'%','s');
//  $suche->massnwann=date('Y-m-d');
//  $suche->where[]="id in(select tnid from gpb_massnahme_tn where (einstieg<>'0000-00-00' and einstieg is not null and einstieg<=?) and (ausstieg='0000-00-00' or ausstieg is null or ausstieg>=?))";
//  $suche->values[]=$suche->massnwann;
//  $suche->values[]=$suche->massnwann;
//  $suche->types.='ss';
//}

$tns=new Liste('IntrainTn',empty($suche->where) ? null : 
    $suche->prepare("select * from gpb_tn_view where","order by nachname,vorname limit 50"));
IntrainTn::refsLaden($tns);

require_once 'IntrainSeite.php';
$seite=IntrainSeite::$menueByUrl['tn.php'];
$seite->anfangGenerieren();
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
IntrainTn::makeHeaderTr();
?>
  <form action="tn_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="redirect" value="tn.php" />
  <tr>
    <td colspan="3">Name: <input type="text" name="tnname" value="<?= htmlentities($suche->tnname,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td></td>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"></td>
    <td>
      <input type="text" name="massnbez" value="<?= isset($suche->massnbez) ? htmlentities($suche->massnbez,ENT_COMPAT) : '' ?>" style="width:100px;" />
      Am&nbsp;<input type="date" name="massnwann" value="<?= isset($suche->massnwann) ? htmlentities($suche->massnwann,ENT_COMPAT) : '' ?>" />
    </td>
<?php
$suche->makeStringInput('selfkurstitel');
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