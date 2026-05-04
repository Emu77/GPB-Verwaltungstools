<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungMassnahme.php';

$suche=isset($_SESSION['massnahmen_suche']) ? $_SESSION['massnahmen_suche'] : new Suche('familieid','berufid','titel');

$familien=array();
$familienById=array();
$result=$db->query("select * from gpb_berufsfamilie order by kuerzel");
while($row=$result->fetch_object()) {
  $familien[]=$row;
  $familienById[$row->id]=$row;
}
$result->free();
function makeFamilieOptionText($familie) {
  return $familie->kuerzel.' '.$familie->bezeichnung;
}

$berufe=array();
$berufeById=array();
$result=$db->query("select b.* from gpb_beruf b order by b.kuerzel");
while($row=$result->fetch_object()) {
  if(!empty($row->familieid)) {
    $row->familie=$familienById[$row->familieid];
  }
  $berufe[]=$row;
  $berufeById[$row->id]=$row;
}
$result->free();
function makeBerufOptionText($beruf) {
  return $beruf->kuerzel.' ('.(empty($beruf->familieid) ? '' : $beruf->familie->kuerzel.' ').$beruf->bezeichnung.')';
}

$massnahmen=new Liste('VerwaltungMassnahme',empty($suche->where) ? null : 
  $suche->prepare("select m.*
    from gpb_massnahme_view m 
    where","order by m.beginn desc,m.ende desc,m.kuerzel limit 50"));

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=VerwaltungSeite::$menueByUrl['../verwaltung/massnahmen.php'];
$seite->anfangGenerieren();
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Berufsfamilie</th>
    <th>Beruf</th>
    <th>Kürzel</th>
    <th>Beginn</th>
    <th>Ende</th>
    <th>Anzahl TN</th>
    <th></th>
  </tr>
  <form action="massnahmen_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <tr>
<?php
$suche->makeSelectId('familieid',$familien,'makeFamilieOptionText',true);
$suche->makeSelectId('berufid',$berufe,'makeBerufOptionText',true);
$suche->makeStringInput('kuerzel');
?>
    <td colspan="2">Am <input type="date" name="am" value="<?= $suche->am ?>" /></td>
    <td></td>
<?php
$suche->makeSubmit();
?>
  </tr>
  </form>
<?php
foreach($massnahmen->alle as $row) {
?>
  <tr>
    <td><?= empty($row->familieid) ? '' : $familienById[$row->familieid]->kuerzel ?></td>
    <td><?= empty($row->berufid) ? '' : $berufeById[$row->berufid]->kuerzel ?></td>
    <td><?= $row->kuerzel ?></td>
    <td><?= empty($row->von) ? '-' : date('d.m.Y',$row->von) ?></td>
    <td><?= empty($row->bis) ? '-' : date('d.m.Y',$row->bis) ?></td>
    <td align="center"><?= $row->anzahlTN ?></td>
<?php
  $row->makeBearbeitenTd();
?>
  </tr>
<?php
}
?>
</table>
<?php
$seite->endeGenerieren();
?>