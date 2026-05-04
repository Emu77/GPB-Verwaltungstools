<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';

$tnid=isset($_POST['tnid']) ? (int)$_POST['tnid'] : 0;
$kursid=isset($_POST['kursid']) ? (int)$_POST['kursid'] : 0;
$fehlt=isset($_POST['fehlt']) && $_POST['fehlt']!='N';
$note=isset($_POST['note']) && !empty($_POST['note']) ? (int)$_POST['note'] : 'null';
$nachnote=isset($_POST['nachnote']) && !empty($_POST['nachnote'])  ? (int)$_POST['nachnote'] : 'null';
$inauszugsichtbar=isset($_POST['inauszugsichtbar']) && $_POST['inauszugsichtbar']!='N';
if($tnid>0 && $kursid>0) {
  $db->query("delete from gpb_note where tnid=".$tnid." and kursid=".$kursid);
  if($fehlt) {
    $db->query("insert into gpb_note(kursid,tnid,fehlt,note,nachnote,inauszugsichtbar) values(".$kursid.",".$tnid.",true,null,".$nachnote.",".($inauszugsichtbar ? 'true' : 'false').")");
  } else if($note!='null' || $nachnote!='null') {
    $db->query("insert into gpb_note(kursid,tnid,fehlt,note,nachnote,inauszugsichtbar) values(".$kursid.",".$tnid.",false,".$note.",".$nachnote.",".($inauszugsichtbar ? 'true' : 'false').")");
  }
  $_SESSION['nachricht']='<div class="done">Note gespeichert!</div>';
}
header('Location:tn_noten.php?tnid='.$tnid);
exit;
?>