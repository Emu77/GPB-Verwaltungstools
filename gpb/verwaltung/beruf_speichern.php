<?php
require_once 'check_login.php';
$fehler=array();
$id=isset($_POST['id']) ? (int)$_POST['id'] : 0;
$familieid=isset($_POST['familieid']) ? (int)$_POST['familieid'] : 0;
if($familieid<=0) {
  $fehler['familieid']='Bitte Berufsfamilie auswählen';
}
$bezeichnung=isset($_POST['bezeichnung']) ? $_POST['bezeichnung'] : '';
if(empty($bezeichnung)) {
  $fehler['bezeichnung']='Bitte Bezeichnung eingeben';
}
$bezeichnungfrau=isset($_POST['bezeichnungfrau']) ? $_POST['bezeichnungfrau'] : '';
$bezeichnungmann=isset($_POST['bezeichnungmann']) ? $_POST['bezeichnungmann'] : '';
$kuerzel=isset($_POST['kuerzel']) ? $_POST['kuerzel'] : '';
$bkz=isset($_POST['bkz']) ? $_POST['bkz'] : '';
$mitiskuerzel=isset($_POST['mitiskuerzel']) ? $_POST['mitiskuerzel'] : '';
if(!empty($fehler)) {
  $beruf=(object)array(
    'id'=>$id,
    'familieid'=>$familieid,
    'bezeichnung'=>$bezeichnung,
    'bezeichnungfrau'=>$bezeichnungfrau,
    'bezeichnungmann'=>$bezeichnungmann,
    'kuerzel'=>$kuerzel,
    'bkz'=>$bkz,
    'mitiskuerzel'=>$mitiskuerzel
  );
  require_once 'beruf_bearbeiten.php';
  exit;
}
if($id>0) {
  $stmt=$db->prepare("update gpb_beruf set familieid=?,bezeichnung=?,bezeichnungfrau=?,bezeichnungmann=?,kuerzel=?,bkz=?,mitiskuerzel=? where id=? limit 1");
  $stmt->bind_param('issssssi',$familieid,$bezeichnung,$bezeichnungfrau,$bezeichnungmann,$kuerzel,$bkz,$mitiskuerzel,$id);
  $stmt->execute();
  header('Location:berufe.php');
  exit;
}
$stmt=$db->prepare("insert into gpb_beruf(familieid,bezeichnung,bezeichnungfrau,bezeichnungmann,kuerzel,bkz,mitiskuerzel) values(?,?,?,?,?,?,?)");
$stmt->bind_param('issssss',$familieid,$bezeichnung,$bezeichnungfrau,$bezeichnungmann,$kuerzel,$bkz,$mitiskuerzel);
$stmt->execute();
$id=$db->insert_id;
header('Location:beruf_module.php?berufid='.$id);
exit;
?>