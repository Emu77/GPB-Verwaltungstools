<?php
require_once 'check_login.php';

$id=isset($_POST['id']) ? (int)$_POST['id'] : 0;
$beginn=isset($_POST['beginn']) ? $_POST['beginn'] : '';
if(empty($beginn)) {
  header('Location:ferien_bearbeiten.php?ferienid='.$id);
  exit;
}
$ende=isset($_POST['ende']) ? $_POST['ende'] : '';
if(empty($ende)) {
  $ende=$beginn;
}
$anlass=isset($_POST['anlass']) ? $_POST['anlass'] : '';
if(empty($anlass)) {
  header('Location:ferien_bearbeiten.php?ferienid='.$id);
  exit;
}
$art=isset($_POST['art']) ? $_POST['art'] : '';
if(empty($art)) {
  header('Location:ferien_bearbeiten.php?ferienid='.$id);
  exit;
}
if($art=='Institutsferien'){
  $ort=isset($_POST['ort']) ? $_POST['ort'] : '';
  if(empty($ort)) {
    header('Location:ferien_bearbeiten.php?ferienid='.$id);
    exit;
  }
} else if($art=='Klassenferien'){
  $ort=isset($_POST['ort']) ? $_POST['ort'] : '';
} else {
  $ort='';
}

if($id>0) {
  $stmt=$db->prepare("update gpb_ferien set beginn=?,ende=?,anlass=?,art=?,ort=? where id=? limit 1");
  $stmt->bind_param('sssssi',$beginn,$ende,$anlass,$art,$ort,$id);
  $stmt->execute();
  $db->query("delete from gpb_klasse_ferien where ferienid=".$id);
} else {
  $stmt=$db->prepare("insert into gpb_ferien(beginn,ende,anlass,art,ort) values(?,?,?,?,?)");
  $stmt->bind_param('sssss',$beginn,$ende,$anlass,$art,$ort);
  $stmt->execute();
  $id=$db->insert_id;
}

if($art=='Klassenferien') {
  $klassenids=isset($_POST['klassenids']) ? explode(',',$_POST['klassenids']) : array();
  $values=array();
  foreach($klassenids as $klasseid) {
    $klasseid=(int)$klasseid;
    if($klasseid>0) {
      $values[]='('.$id.','.$klasseid.')';
    }
  }
  if(!empty($values)) {
    $db->query("insert into gpb_klasse_ferien(ferienid,klasseid) values ".implode(',',$values));
  }
}

header('Location:ferien_sehen.php?ferienid='.$id);
exit;
?>