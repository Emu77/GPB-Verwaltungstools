<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';

$id=isset($_POST['id']) ? (int)$_POST['id'] : 0;
$ort=isset($_POST['ort']) ? $_POST['ort'] : '';
$tuer=isset($_POST['tuer']) ? $_POST['tuer'] : '';
if(empty($tuer)) {
  header('Location:raum_bearbeiten.php?id='.$id);
  exit;
}
$zusatz=isset($_POST['zusatz']) ? $_POST['zusatz'] : '';
$art=isset($_POST['art']) ? $_POST['art'] : '';
$anzahlPlaetze=isset($_POST['anzahlPlaetze']) && !empty($_POST['anzahlPlaetze']) ? (int)$_POST['anzahlPlaetze'] : -1;
$anzahlComputer=isset($_POST['anzahlComputer']) && !empty($_POST['anzahlComputer']) ? (int)$_POST['anzahlComputer'] : -1;

if($id>0) {
  $stmt=$db->prepare("update gpb_raum set ort=?,tuer=?,zusatz=?,art=?,anzahlPlaetze=?,anzahlComputer=? where id=? limit 1");
  $stmt->bind_param('ssssiii',$ort,$tuer,$zusatz,$art,$anzahlPlaetze,$anzahlComputer,$id);
  $stmt->execute();
} else {
  $stmt=$db->prepare("insert into gpb_raum(ort,tuer,zusatz,art,anzahlPlaetze,anzahlComputer) values(?,?,?,?,?,?)");
  $stmt->bind_param('ssssii',$ort,$tuer,$zusatz,$art,$anzahlPlaetze,$anzahlComputer);
  $stmt->execute();
  $id=$db->insert_id;
}

header('Location:raum_sehen.php?raumid='.$id);
exit;
?>