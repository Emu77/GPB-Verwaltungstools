<?php
require_once 'check_login.php';

if(!isset($beruf)) {
  $berufid=isset($_GET['berufid']) ? (int)$_GET['berufid'] : 0;
  if($berufid>0){
    $result=$db->query("select b.*
      from gpb_beruf b
      where b.id=".$berufid." limit 1");
    $beruf=$result->fetch_object();
    $result->free();
    if(!$beruf) {
      header('Location:berufe.php');
      exit;
    }
  } else {
    $beruf=(object)array(
      'id'=>0,
      'familieid'=>0,
      'bezeichnung'=>'',
      'bezeichnungfrau'=>'',
      'bezeichnungmann'=>'',
      'kuerzel'=>'',
      'bkz'=>'',
      'mitiskuerzel'=>''
    );
  }
}

$berufsfamilien=array();
$result=$db->query("select * from gpb_berufsfamilie order by kuerzel");
while($row=$result->fetch_object()) {
  $berufsfamilien[]=$row;
}
$result->free();

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite($beruf->id<=0 ? 'Neuer Beruf' : 'Beruf "'.$beruf->bezeichnung.'" bearbeiten');
$seite->anfangGenerieren();
?>
<form action="beruf_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="id" value="<?= $beruf->id ?>" />
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="bearbeiten">
  <tr>
    <th>Offizielle Bezeichnung</th>
    <td><input type="text" name="bezeichnung" value="<?= htmlentities($beruf->bezeichnung,ENT_COMPAT) ?>" style="width:300px;" /></td>
  </tr>
  <tr>
    <th>Ggf. Variante mit -frau</th>
    <td><input type="text" name="bezeichnungfrau" value="<?= htmlentities($beruf->bezeichnungfrau,ENT_COMPAT) ?>" style="width:300px;" /></td>
  </tr>
  <tr>
    <th>Ggf. Variante mit -mann</th>
    <td><input type="text" name="bezeichnungmann" value="<?= htmlentities($beruf->bezeichnungmann,ENT_COMPAT) ?>" style="width:300px;" /></td>
  </tr>
  <tr>
    <th>Berufsfamilie</th>
    <td><select name="familieid">
<?php
foreach($berufsfamilien as $f) {
?>
      <option value="<?= $f->id ?>" <?= $f->id==$beruf->familieid ? 'selected' : '' ?>><?= $f->kuerzel ?> (<?= $f->bezeichnung ?>)</option>
<?php
}
?>
    </select></td>
  </tr>
  <tr>
    <th>Kürzel</th>
    <td><input type="text" name="kuerzel" value="<?= htmlentities($beruf->kuerzel,ENT_COMPAT) ?>" style="width:70px;" /></td>
  </tr>
  <tr>
    <th>BKZ</th>
    <td><input type="text" name="bkz" value="<?= htmlentities($beruf->bkz,ENT_COMPAT) ?>" style="width:70px;" /></td>
  </tr>
  <tr>
    <th>Klassen-Kürzel</th>
    <td><input type="text" name="mitiskuerzel" value="<?= htmlentities($beruf->mitiskuerzel,ENT_COMPAT) ?>" style="width:150px;" /> durch ; getrennt!</td>
  </tr>
  <tr>
    <th></th>
    <td>
      <input type="submit" value="Speichern" />
      <a href="berufe.php">Abbrechen</a>
    </td>
  </tr>
</table>
</form>
<?php
$seite->endeGenerieren();
?>