<?php
require_once 'check_login.php';

$kursid=isset($_POST['kursid']) ? (int)$_POST['kursid'] : 0;
if($kursid>0) {
  $result=$db->query("select * from gpb_kurs where id=".$kursid." limit 1");
  $kurs=$result->fetch_object();
  $result->free();
} else {
  $kurs=false;
}
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}
if($kurs->notenstatus=='keine') {
  header('Location:kurs_il_noten.php?kursid='.$kurs->id);
  exit;
}

$result=$db->query("select * from gpb_kurs_dozent where kursid=".$kurs->id." and dozentid=".$ich->id." limit 1");
$darf=$result->fetch_object();
$result->free();
if(!$darf) {
  header('Location:kurs_il_noten.php?kursid='.$kurs->id);
  exit;
}

$db->query("delete from gpb_note where kursid=".$kurs->id);

$tnids=array();
$result=$db->query("select distinct tn.id
  from gpb_klasse_tn ktn
  join gpb_tn tn on tn.id=ktn.tnid
  left outer join gpb_note n on n.kursid=".$kurs->id." and n.tnid=tn.id
  where ktn.klasseid in(select klasseid from gpb_kurs_klasse where kursid=".$kurs->id.")
  and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<='".$kurs->ende."')
  and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>='".$kurs->beginn."')");
while($row=$result->fetch_object()){
  $tnids[]=$row->id;
}
$result->free();
$values=array();
foreach($tnids as $tnid) {
  if(isset($_POST['fehlt_'.$tnid]) && $_POST['fehlt_'.$tnid]!='N'){
    $values[]='('.$kursid.','.$tnid.',true,null)';
  } else if(isset($_POST['note_'.$tnid]) && !empty($_POST['note_'.$tnid])){
    $note=(int)$_POST['note_'.$tnid];
    $values[]='('.$kurs->id.','.$tnid.',false,'.$note.')';
  }
}
if(!empty($values)){
  $db->query("insert into gpb_note(kursid,tnid,fehlt,note) values ".implode(',',$values));
}
$notenstatus=isset($_POST['noten_ok']) && $_POST['noten_ok']!='N' ? 'ok' : 'todo';
$mittelwert=isset($_POST['mittelwert']) && !empty($_POST['mittelwert']) ? (int)$_POST['mittelwert'] : null;
$notenerklaerung=isset($_POST['notenerklaerung']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['notenerklaerung'])) : '';
if($notenstatus=='ok' && ($mittelwert==null || $mittelwert<65 || $mittelwert>85) && empty($notenerklaerung)) {
  $notenstatus='todo';
  $_SESSION['notenerklaerung_nachricht']='<div class="nok">Bitte eine Erläuterung eintragen</div>';
}
$stmt=$db->prepare("update gpb_kurs set notenstatus=?,notenerklaerung=? where id=".$kursid." limit 1");
$stmt->bind_param('ss',$notenstatus,$notenerklaerung);
$stmt->execute();
if(isset($_SESSION['todos'])) {
  unset($_SESSION['todos']);
}
$_SESSION['noten_nachricht']='<div class="done">Noten gespeichert!</div>';
header('Location:kurs_il_noten.php?kursid='.$kursid.'#noten');
exit;
?>