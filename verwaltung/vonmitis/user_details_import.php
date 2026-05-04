<?php
require_once '../../db.php';

function prefix_entfernen(&$s,$k) {
  $s=json_decode(substr($s,strpos($s,'"')));
}
echo "User Details laden<br />\n";
$userdetByMid=array();
$massnahmenByTitel=array();
$result=$db->query("select ud.* 
  ,b.id as berufid
  from mdl_gpb_user_details ud
  left outer join gpb_beruf b on b.bkz=ud.bkz");
while($row=$result->fetch_object()) {
  //Vor- und Nachname aus der Spalte data holen
  $data=$row->data;
  $data=substr($data,strpos($data,'{'));
  $data=explode(';',$data);
  array_walk($data,'prefix_entfernen');
  for($i=0;$i<count($data);++$i){
    if($data[$i]=='Vorname') {
      ++$i;
      $row->vorname=mb_convert_encoding($data[$i],'iso-8859-1','utf-8');
    } else if($data[$i]=='Nachname') {
      ++$i;
      $row->nachname=mb_convert_encoding($data[$i],'iso-8859-1','utf-8');
    }
  }
  //Datumsangaben normalisieren
  $d=empty($row->einstieg_ist) ? $row->einstieg_soll : $row->einstieg_ist;
  $row->einstieg=empty($d) ? '0000-00-00' : implode('-',array_reverse(explode('.',$d)));
  $d=empty($row->ausstieg_ist) ? $row->ausstieg_soll : $row->ausstieg_ist;
  $row->ausstieg=empty($d) ? '0000-00-00' : implode('-',array_reverse(explode('.',$d)));
  
  $userdetByMid[$row->mitisid]=$row;
  if(isset($massnahmenByTitel[$row->massnahmetitel])) {
    $massn=$massnahmenByTitel[$row->massnahmetitel];
    if(empty($massn->bkz) && !empty($row->bkz)) {
      $massn->bkz=$row->bkz;
    }
  } else {
    $massn=(object)array(
      'titel'=>$row->massnahmetitel,
      'bkz'=>$row->bkz,
      'schon'=>false,
      'userdets'=>array()
    );
    $massnahmenByTitel[$row->massnahmetitel]=$massn;
  }
  $massn->userdets[]=$row;
}
$result->free();

$berufeById=array();
$berufeByBkz=array();
$result=$db->query("select * from gpb_beruf ");
while($row=$result->fetch_object()) {
  $berufeById[$row->id]=$row;
  if(!empty($row->bkz)) {
    $berufeByBkz[$row->bkz]=$row;
  }
}
$result->free();

echo "Angleichung Maßnahmen<br />\n";
$massnahmenById=array();
if(!empty($massnahmenByTitel)) {
  $result=$db->query("select * from gpb_massnahme where titel in('".implode("','",array_keys($massnahmenByTitel))."')");
  while($row=$result->fetch_object()) {
    $massnahmenByTitel[$row->titel]->schon=$row;
    $massnahmenById[$row->id]=$row;
  }
  $result->free();
}
foreach($massnahmenByTitel as $titel=>$massn) {
  $berufid=0;
  if(!empty($massn->bkz)) {
    if(isset($berufeByBkz[$massn->bkz])) {
      $berufid=$berufeByBkz[$massn->bkz]->id;
    } else {
      echo "Maßnahme ".$titel.": Beruf nicht gefunden: BKZ=".$massn->bkz;
    }
  }
  if($massn->schon) {
    if(!empty($berufid) && $massn->schon->berufid!=$berufid) {
      $db->query("update gpb_massnahme set berufid=".$berufid." where id=".$massn->schon->id);
      echo "Maßnahme ".$titel." Beruf geändert: ".$berufeById[$berufid]->kuerzel."<br />\n";
    }
  } else {
    $stmt=$db->prepare("insert into gpb_massnahme(titel,berufid) values(?,?)");
    $stmt->bind_param('si',$titel,$berufid);
    $stmt->execute();
    $id=$db->insert_id;
    $massn->schon=(object)array(
      'id'=>$id,
      'titel'=>$titel,
      'berufid'=>$berufid
    );
    $massnahmenById[$massn->schon->id]=$massn->schon;
    echo "Maßnahme ".$titel." hinzugefügt<br />\n";
  }
}

$tnidByMid=array();
echo "Vergleich mit schon importierten TN<br />\n";
$result=$db->query("select * from gpb_tn");
while($tn=$result->fetch_object()){
  $tnidByMid[$tn->mitisid]=$tn->id;
  if(!isset($userdetByMid[$tn->mitisid])) {
    echo "TN ".$tn->vorname." ".$tn->nachname." mitisid=".$tn->mitisid." fehlt in mdl_gpb_user_details<br />\n";
    continue;
  }
  $userdet=$userdetByMid[$tn->mitisid];
  unset($userdetByMid[$tn->mitisid]);
  $anrede=$userdet->anrede;
  if($anrede!='' && $anrede!='Frau' && $anrede!='Herr') {
    echo "TN ".$userdet->vorname." ".$userdet->nachname." mitisid=".$tn->mitisid.": unbekannte Anrede ".$anrede."<br />\n";
    $anrede='';
  }
  if($anrede!=$tn->anrede) {
    $stmt=$db->prepare("update gpb_tn set anrede=? where id=? limit 1");
    $stmt->bind_param('si',$anrede,$tn->id);
    $stmt->execute();
    echo "TN ".$tn->vorname." ".$tn->nachname." mitisid=".$tn->mitisid." Anrede geändert: ".$anrede."<br />\n";
  }
  if($userdet->vorname!=$tn->vorname) {
    $stmt=$db->prepare("update gpb_tn set vorname=? where id=? limit 1");
    $stmt->bind_param('si',$userdet->vorname,$tn->id);
    $stmt->execute();
    echo "TN ".$tn->vorname." ".$tn->nachname." mitisid=".$tn->mitisid." Vorname geändert: ".$userdet->vorname."<br />\n";
  }
  if($userdet->nachname!=$tn->nachname) {
    $stmt=$db->prepare("update gpb_tn set nachname=? where id=? limit 1");
    $stmt->bind_param('si',$userdet->nachname,$tn->id);
    echo "TN ".$tn->vorname." ".$tn->nachname." mitisid=".$tn->mitisid." Nachname geändert: ".$userdet->nachname."<br />\n";
    $stmt->execute();
  }
  if($userdet->berufid!=$tn->berufid && !empty($userdet->berufid)) {
    $stmt=$db->prepare("update gpb_tn set berufid=? where id=? limit 1");
    $stmt->bind_param('ii',$userdet->berufid,$tn->id);
    echo "TN ".$tn->vorname." ".$tn->nachname." mitisid=".$tn->mitisid." Beruf-ID geändert: ".$userdet->berufid."<br />\n";
    $stmt->execute();
  }
}
$result->free();

echo "Neue TN hinzufügen<br />\n";
foreach($userdetByMid as $mitisid=>$userdet){
  $stmt=$db->prepare("insert into gpb_tn(mitisid,anrede,vorname,nachname,berufid) values(?,?,?,?,?)");
  if(!empty($userdet->anrede) && $userdet->anrede!='Herr' && $userdet->anrede!='Frau') {
    echo "TN ".$userdet->vorname." ".$userdet->nachname." mitisid=".$mitisid." unbekannte Anrede: ".$userdet->anrede."<br />\n";
    $userdet->anrede='';
  }
  $stmt->bind_param('ssssi',$mitisid,$userdet->anrede,$userdet->vorname,$userdet->nachname,$userdet->berufid);
  $stmt->execute();
  $tnidByMid[$mitisid]=$db->insert_id;
  echo "TN ".$userdet->vorname." ".$userdet->nachname." mitisid=".$mitisid." hinzugefügt<br />\n";
}

echo "Anmeldungen in Maßnahmen aktualisieren<br />\n";
$anzahl=0;
if(!empty($massnahmenById)) {
  $db->query("delete from gpb_massnahme_tn where massnahmeid in(".implode(',',array_keys($massnahmenById)).")");
}
$stmt=$db->prepare("insert into gpb_massnahme_tn(massnahmeid,tnid,einstieg,ausstieg) values(?,?,?,?)");
foreach($massnahmenByTitel as $titel=>$massn) {
  $massnahmeid=$massn->schon->id;
  foreach($massn->userdets as $row) {
    $tnid=$tnidByMid[$row->mitisid];
    $stmt->bind_param('iiss',$massnahmeid,$tnid,$row->einstieg,$row->ausstieg);
    $stmt->execute();
    ++$anzahl;
  }
}
echo $anzahl." Anmeldungen gespeichert.<br />\n";
echo '<a href="index.php">Zurück</a>';
exit;
?>