<?php
require_once 'check_login.php';
$titel=isset($_POST['titel']) ? $_POST['titel'] : '';
if(empty($titel)) {
  header('Location:spez_ausbildungen.php');
  exit;
}
$vollzeit_wochenue=isset($_POST['vollzeit_wochenue']) ? (int)$_POST['vollzeit_wochenue'] : 0;
$vollzeit_massnnr=isset($_POST['vollzeit_massnnr']) ? $_POST['vollzeit_massnnr'] : '';
$vollzeit_beginn=isset($_POST['vollzeit_beginn']) && !empty($_POST['vollzeit_beginn']) ? $_POST['vollzeit_beginn'] : null;
$vollzeit_ende=isset($_POST['vollzeit_ende']) && !empty($_POST['vollzeit_ende']) ? $_POST['vollzeit_ende'] : null;
$teilzeit_massnnr=isset($_POST['teilzeit_massnnr']) ? $_POST['teilzeit_massnnr'] : '';
$teilzeit_beginn=isset($_POST['teilzeit_beginn']) && !empty($_POST['teilzeit_beginn']) ? $_POST['teilzeit_beginn'] : null;
$teilzeit_ende=isset($_POST['teilzeit_ende']) && !empty($_POST['teilzeit_ende']) ? $_POST['teilzeit_ende'] : null;
$zertif_beginn=isset($_POST['zertif_beginn']) && !empty($_POST['zertif_beginn']) ? $_POST['zertif_beginn'] : null;
$zertif_ende=isset($_POST['zertif_ende']) && !empty($_POST['zertif_ende']) ? $_POST['zertif_ende'] : null;
$stmt=$db->prepare("insert into gpb_spezausbildung(titel,vollzeit_wochenue,vollzeit_massnnr,vollzeit_beginn,vollzeit_ende,teilzeit_massnnr,teilzeit_beginn,teilzeit_ende,zertif_beginn,zertif_ende) values(?,?,?,?,?,?,?,?,?,?)");
$stmt->bind_param('sissssssss',$titel,$vollzeit_wochenue,$vollzeit_massnnr,$vollzeit_beginn,$vollzeit_ende,$teilzeit_massnnr,$teilzeit_beginn,$teilzeit_ende,$zertif_beginn,$zertif_ende);
$stmt->execute();
$id=$db->insert_id;
header('Location:spez_ausbildungen.php#ausbildung_'.$id);
exit;
?>