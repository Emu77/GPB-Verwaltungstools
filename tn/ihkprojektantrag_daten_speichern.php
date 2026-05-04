<?php
require_once 'check_login.php';
$kursid=isset($_POST['kursid']) ? (int)$_POST['kursid'] : 0;
$spalte=isset($_POST['spalte']) ? $_POST['spalte'] : false;
$daten=isset($_POST['daten']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['daten'])) : false;
if(empty($kursid) || empty($spalte)) {
  echo 'Bitte Kurs auswählen';
  exit;
}
$spalte=str_replace('`','',$spalte);
$result=$db->query("select * from gpb_ihkprojektantrag where kursid=".$kursid." and tnid=".$ich->id);
$alt=$result->fetch_object();
$result->close();
if(empty($alt)) {
  if(!empty($daten)) {
    $stmt=$db->prepare("insert into gpb_ihkprojektantrag(kursid,tnid,`".$spalte."`,`".$spalte."_geaendertam`) values(?,?,?,now())");
    $stmt->bind_param('iis',$kursid,$ich->id,$daten);
    $stmt->execute();
  }
} else {
  $sp=$spalte.'_geaendertam';
  $geaendertam=$alt->$sp;
  if($spalte=='bezeichnung') {
    if($alt->$spalte!=$daten) {
      $geaendertam=date('Y-m-d H:i:s');
    }
  } else {
    if(levenshtein($alt->$spalte,$daten)>=min(strlen($alt->$spalte)/2,30)) {
      $geaendertam=date('Y-m-d H:i:s');
    }
  }
  $stmt=$db->prepare("update gpb_ihkprojektantrag set `".$spalte."`=?,`".$spalte."_geaendertam`=? where kursid=? and tnid=?");
  $stmt->bind_param('ssii',$daten,$geaendertam,$kursid,$ich->id);
  $stmt->execute();
}
echo 'OK';
exit;
?>