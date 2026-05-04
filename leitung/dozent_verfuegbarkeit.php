<?php
require_once 'check_login.php';
require_once '../verwaltung/VerwaltungDozent.php';
require_once 'DozentVerfuegbarkeit.php';

$dozent=Dozent::einenLaden(isset($_GET['dozentid']) ? (int)$_GET['dozentid'] : 0,'VerwaltungDozent');
if(empty($dozent)) {
  header('Location:../verwaltung/dozenten.php');
  exit;
}

$verfuegbarkeiten=array();
$result=$db->query("select * from gpb_dozent_verfuegbarkeit where dozentid=".$dozent->id." and ende>=current_date() order by beginn,ende");
while($row=$result->fetch_object()) {
//  $row->von=strtotime($row->beginn);
//  $row->bis=strtotime($row->ende);
  $verfuegbarkeiten[]=$row;
}
$result->free();

require_once 'LeitungSeite.php';
$seite=new LeitungSeite('Dozent '.$dozent->vorname.' '.$dozent->nachname.' Verfügbarkeit');
$seite->anfangGenerieren();
$dozent->makeSehen();
?>
Dozent für die Planung verfügbar: <input type="checkbox" <?= $dozent->idrverfuegbar ? 'checked' : '' ?> onchange="location.href='dozent_idrverfuegbar_speichern.php?dozentid=<?= $dozent->id ?>&amp;idrverfuegbar='+(this.checked ? 'J' : 'N')+'&amp;redirect=dozent_verfuegbarkeit.php'" /> idR verfügbar<br />
<br />
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th colspan="5" align="left">Ausnahmen / Bestätigungen</th>
  </tr>
  <tr>
    <th>Verfügbarkeit</th>
    <th>Von</th>
    <th>Bis</th>
    <th>Notiz</th>
    <th></th>
  </tr>
<?php
foreach($verfuegbarkeiten as $verf) {
?>
  <form action="dozent_verfuegbarkeit_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="id" value="<?= $verf->id ?>" />
    <input type="hidden" name="dozentid" value="<?= $dozent->id ?>" />
    <input type="hidden" name="redirect" value="dozent_verfuegbarkeit.php" />
  <tr>
    <td><?php DozentVerfuegbarkeit::makeSelect($verf->verfuegbar) ?></td>
    <td><input type="date" name="beginn" value="<?= $verf->beginn ?>" /></td>
    <td><input type="date" name="ende" value="<?= $verf->ende ?>" /></td>
    <td><input type="text" name="notiz" value="<?= htmlentities($verf->notiz,ENT_COMPAT) ?>" style="width:200px;" /></td>
    <td><input type="submit" value="Änderungen speichern" /><input type="button" value="Löschen" onclick="location.href='dozent_verfuegbarkeit_loeschen.php?id=<?= $verf->id ?>&amp;dozentid=<?= $dozent->id ?>&amp;redirect=dozent_verfuegbarkeit.php'" /></td>
  </tr>
  </form>
<?php
}
?>
  <form action="dozent_verfuegbarkeit_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="id" value="0" />
    <input type="hidden" name="dozentid" value="<?= $dozent->id ?>" />
    <input type="hidden" name="redirect" value="dozent_verfuegbarkeit.php" />
  <tr>
    <td><?php DozentVerfuegbarkeit::makeSelect($dozent->idrverfuegbar ? 'Nicht verfügbar' : 'Verfügbar') ?></td>
    <td><input type="date" name="beginn" value="" /></td>
    <td><input type="date" name="ende" value="" /></td>
    <td><input type="text" name="notiz" value="" style="width:200px;" /></td>
    <td><input type="submit" value="Hinzufügen" /></td>
  </tr>
  </form>
</table>
<?php
$seite->endeGenerieren();
?>