<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once 'VerwaltungRaum.php';

if(isset($_GET['raumid']) && $_GET['raumid']!='0') {
  $raum=Raum::einenLaden((int)$_GET['raumid'],'VerwaltungRaum');
  if(empty($raum)) {
    header('Location:raeume.php');
    exit;
  }
} else {
  $ort=isset($_GET['ort']) ? $_GET['ort'] : '';
  $tuer=isset($_GET['tuer']) ? $_GET['tuer'] : '';
  $zusatz=isset($_GET['zusatz']) ? $_GET['zusatz'] : '';
  $art=isset($_GET['art']) ? $_GET['art'] : '';
  $anzahlPlaetze=isset($_GET['anzahlPlaetze']) ? (int)$_GET['anzahlPlaetze'] : -1;
  $anzahlComputer=isset($_GET['anzahlComputer']) ? (int)$_GET['anzahlComputer'] : -1;
  $raum=(object)array(
    'id'=>0,
    'ort'=>$ort,
    'tuer'=>$tuer,
    'zusatz'=>$zusatz,
    'art'=>$art,
    'anzahlPlaetze'=>$anzahlPlaetze,
    'anzahlComputer'=>$anzahlComputer
  );
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Raum '.$raum->tuer.' bearbeiten');
$seite->anfangGenerieren();
?>
<form action="raum_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="id" value="<?= $raum->id ?>" />
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="bearbeiten">
  <tr>
    <th>Ort</th>
    <td><select name="ort">
      <option value="-" <?= $raum->ort=='-' ? 'selected' : '' ?>>-</option>
      <option value="Mitte" <?= $raum->ort=='Mitte' ? 'selected' : '' ?>>Mitte</option>
      <option value="Neukölln" <?= $raum->ort=='Neukölln' ? 'selected' : '' ?>>Neukölln</option>
    </select></td>
  </tr>
  <tr>
    <th>Raum</th>
    <td><input type="text" name="tuer" value="<?= htmlentities($raum->tuer,ENT_COMPAT) ?>" style="width:100px;" /></td>
  </tr>
  <tr>
    <th>Zusatz</th>
    <td><input type="text" name="zusatz" value="<?= htmlentities($raum->zusatz,ENT_COMPAT) ?>" style="width:150px;" /></td>
  </tr>
  <tr>
    <th>Art</th>
    <td><input type="text" name="art" value="<?= htmlentities($raum->art,ENT_COMPAT) ?>" style="width:100px;" /></td>
  </tr>
  <tr>
    <th>Anzahl Plätze</th>
    <td><input type="number" style="width:100px;" name="anzahlPlaetze" value="<?= $raum->anzahlPlaetze<0 ? '' : $raum->anzahlPlaetze ?>" /></td>
  </tr>
  <tr>
    <th>Anzahl Computer</th>
    <td><input type="number" style="width:100px;" name="anzahlComputer" value="<?= $raum->anzahlComputer<0 ? '' : $raum->anzahlComputer ?>" /></td>
  </tr>
  <tr>
    <th></th>
    <td><input type="submit" value="Speichern" /> <a href="raeume.php">Abbrechen</a></td>
  </tr>
</table>
</form>
<?php
$seite->endeGenerieren();
?>