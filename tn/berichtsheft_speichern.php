<?php
require_once 'check_login.php';

if(!$ich->berichtsheft_offen) {
  header('Location:kurse.php');
  exit;
}

$tnid=$ich->id;
$datum=isset($_POST['datum']) ? $_POST['datum'] : '';
if(empty($datum)) {
  header('Location:berichtsheft.php');
  exit;
}
$stmt=$db->prepare("select * from gpb_berichtsheft where tnid=? and datum=? limit 1");
$stmt->bind_param('is',$tnid,$datum);
$stmt->execute();
$result=$stmt->get_result();
$schon=$result->fetch_object();
$result->free();

$beginn=isset($_POST['von']) ? $_POST['von'] : '';
$ende=isset($_POST['bis']) ? $_POST['bis'] : '';
$pausen=isset($_POST['pausen']) ? (float)$_POST['pausen'] : 0;
$was=isset($_POST['was']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['was'])) : '';
if(!empty($beginn) && !empty($ende) && !empty($was)) {
  if($schon) {
    $stmt=$db->prepare("update gpb_berichtsheft set beginn=?,ende=?,pausen=?,was=? where tnid=? and datum=? limit 1");
    $stmt->bind_param('ssdsis',$beginn,$ende,$pausen,$was,$tnid,$datum);
    $stmt->execute();
  } else {
    $stmt=$db->prepare("insert into gpb_berichtsheft(tnid,datum,beginn,ende,pausen,was) values(?,?,?,?,?,?)");
    $stmt->bind_param('isssds',$tnid,$datum,$beginn,$ende,$pausen,$was);
    $stmt->execute();
  }
  $_SESSION['berichtsheft'][$datum]='<div class="done">Gespeichert!</div>';
} else {
  $_SESSION['berichtsheft'][$datum]='<div class="nok">Arbeitsbeginn, Arbeitsende und Tätigkeit bitte eingeben!</div>';
}
header('Location:berichtsheft.php?datum='.$datum);
exit;
?>