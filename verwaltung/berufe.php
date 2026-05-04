<?php
require_once 'check_login.php';

$familien=array();
$familienById=array();
$result=$db->query("select * from gpb_berufsfamilie order by kuerzel");
while($row=$result->fetch_object()) {
  $row->berufe=array();
  $familien[]=$row;
  $familienById[$row->id]=$row;
}
$result->free();

$result=$db->query("select * from gpb_beruf order by kuerzel");
while($row=$result->fetch_object()) {
  $familienById[$row->familieid]->berufe[]=$row;
}
$result->free();

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=VerwaltungSeite::$menueByUrl['../verwaltung/berufe.php'];
$seite->anfangGenerieren();
?>
<a href="beruf_bearbeiten.php?id=0">Neuer Beruf</a><br />
<br />
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-top:1em;">
  <tr>
    <th>Kürzel</th>
    <th>BKZ</th>
    <th>Bezeichnung</th>
    <th>Klassen-Kürzel</th>
    <th></th>
  </tr>
<?php
foreach($familien as $f) {
?>
  <tr>
    <td colspan="2"><b><?= $f->kuerzel ?></b> (<?= $f->ort ?>)</td>
    <td colspan="3"><b><?= $f->bezeichnung ?></b></td>
    <td><b>
      <a href="massnahmen_suchen.php?familieid=<?= $f->id ?>">Maßnahmen</a>
      <a href="klassen_suchen.php?familieid=<?= $f->id ?>">Klassen</a>
    </b></td>
  </tr>
<?php
  foreach($f->berufe as $b) {
?>
  <tr>
    <td><?= $b->kuerzel ?></td>
    <td><?= $b->bkz ?></td>
    <td><?= $b->bezeichnung ?></td>
    <td><?= $b->mitiskuerzel ?></td>
    <td>
      <a href="beruf_bearbeiten.php?berufid=<?= $b->id ?>">Bearbeiten</a>
      <a href="beruf_module.php?berufid=<?= $b->id ?>">Module</a>
    </td>
    <td>
      <a href="massnahmen_suchen.php?berufid=<?= $b->id ?>">Maßnahmen</a>
      <a href="klassen_suchen.php?berufid=<?= $b->id ?>">Klassen</a>
    </td>
  </tr>
<?php
  }
}
?>
</table>
<?php
$seite->endeGenerieren();
?>