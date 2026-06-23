<?php
require_once '../../db.php';

if(!isset($_POST['passwort']) || $_POST['passwort']!='vonmitis') {
  exit;
}

//TN aus /gpb/data/MitisExportTeilnehmer.csv importieren
if(!isset($_FILES['MitisExportTeilnehmer']) || !file_exists($_FILES['MitisExportTeilnehmer']['tmp_name'])) {
  header('Location:index.php');
  exit;
}

$ausgeschlosseneMitisids=array(
  //Aus ihrer Maßnahme vor dem 01.11.2024 ausgestiegen
  91667,99416,90428,86220,107699,83173,92703,92887,93977,93591,93696,94219,94864,94979,95792,96817,97056,97160,97322,97360,97416,97483,97661,97767,97768,97817,97821,97863,98021,98485,98528,98636,98653,98877,98908,98929,99001,99049,99181,99221,99313,99323,99350,99449,99519,99586,99610,99623,99646,99683,99810,99900,99982,100031,100369,100462,100526,100942,100963,101112,101163,101208,101371,101492,101606,102440,103281,103319,103463,103543
);

$MTNByMid=array();
$f=fopen($_FILES['MitisExportTeilnehmer']['tmp_name'],'rt');

//StammdatenID;Anrede;Vorname;Nachname;p_email;GebDat;GebOrt;
fgetcsv($f,null,';',' ');
while($row=fgetcsv($f,null,';','"')) {
  if(in_array((int)$row[0],$ausgeschlosseneMitisids)) continue;
  if(empty($row[4])) continue; // keine Emailadresse => kein Moodle
  
  if(isset($MTNByMid[$row[0]])) { // Für diesen TN wurden 2 Verträge erstellt; Daten mergen
    $alt=$MTNByMid[$row[0]];
    for($i=1;$i<6;++$i) {
      if(!empty($row[$i]) && empty($alt[$i])) {
        $alt[$i]=$row[$i];
      }
    }
  } else {
    $row['dest']=false;
    if($row[1]=='null' || $row[1]=='-'){
      $row[1]='';
    } else if(!empty($row[1]) && $row[1]!='Frau' && $row[1]!='Herr') {
      echo "TN ".$row[2]." ".$row[3]." mitisid=".$row[0]." unbekannte Anrede ".$row[1].".<br />\n";
      $row[1]='';
    }
    $MTNByMid[$row[0]]=$row;
  }
}
fclose($f);
echo "Datei geladen, ".count($MTNByMid)." Datensätze<br />\n";

echo "Abgleich mit schon importierten TN...<br />\n";
$result=$db->query("select * from gpb_tn");
while($row=$result->fetch_object()) {
  if(isset($MTNByMid[$row->mitisid])) {
    $MTNByMid[$row->mitisid]['dest']=$row;
  } else {
    echo "TN ".$row->vorname." ".$row->nachname." mitisid=".$row->mitisid." aus MitisExport verschwunden<br />\n";
  }
}
$result->free();

echo "Daten korrigieren...<br />\n";
$updatestmt=$db->prepare("update gpb_tn set mitisanrede=?,mitisvorname=?,anrede=?,vorname=?,nachname=?,email=?,geburtsdatum=?,geburtsort=? where id=? limit 1");
$updatestmtgender=$db->prepare("update gpb_tn set mitisanrede=?,mitisvorname=?,nachname=?,email=?,geburtsdatum=?,geburtsort=? where id=? limit 1");
$insertstmt=$db->prepare("insert into gpb_tn(mitisid,mitisanrede,mitisvorname,anrede,vorname,nachname,email,geburtsdatum,geburtsort) values(?,?,?,?,?,?,?,?,?)"); //Berufid haben wir hier nicht, kommt mit der Zuweisung zu Maßnahmen
$nutzernamenstmt=$db->prepare("select id from gpb_tn where nutzername=?"
  ." union all select id from gpb_dozent where nutzername=?"
  ." union all select id from gpb_verwalter where nutzername=?");
$updatemoodlestmt=$db->prepare("update gpb_tn set nutzername=?,moodleid=? where id=? limit 1");
foreach($MTNByMid as $mitisid=>$orig) {
  $id=0;
  $moodleid=0;
  $moodletodo=false;
  $vorname=$orig[2];
  $nutzername='';
  if($orig['dest']) {
    $dest=$orig['dest'];
    $id=$dest->id;
    $moodleid=$dest->moodleid;
    $nutzername=$dest->nutzername;
    if(!isset($orig[5]) || $orig[5]=='null' || $orig[5]=='0000-00-00' || empty($orig[5])) $orig[5]=$dest->geburtsdatum;
    if(!isset($orig[6]) || $orig[6]=='null' || empty($orig[6])) $orig[6]=$dest->geburtsort;
    //Passwort wird hier nicht geändert
    if($dest->anrede!=$orig[1]
      || $dest->vorname!=$orig[2]
      || $dest->nachname!=$orig[3]
      || $dest->email!=$orig[4]
      || $dest->geburtsdatum!=$orig[5]
      || $dest->geburtsort!=$orig[6]) {
        if($dest->anrede==$dest->mitisanrede && $dest->vorname==$dest->mitisvorname) {
          $updatestmt->bind_param('ssssssssi',$orig[1],$orig[2],$orig[1],$orig[2],$orig[3],$orig[4],$orig[5],$orig[6],$dest->id);
          $updatestmt->execute();
        } else { //bei Gender nur die MITIS-Daten korrigieren
          $updatestmtgender->bind_param('ssssssi',$orig[1],$orig[2],$orig[3],$orig[4],$orig[5],$orig[6],$dest->id);
          $updatestmtgender->execute();
          $vorname=$dest->vorname;
        }
      echo "TN ".$orig[2]." ".$orig[3]." mitisid=".$orig[0]." korrigiert.<br />\n";
      $moodletodo=true;
    }
  } else {
    if(!isset($orig[5]) || $orig[5]=='null' || $orig[5]=='0000-00-00' || empty($orig[5])) $orig[5]='2050-01-01';
    if(!isset($orig[6]) || $orig[6]=='null' || empty($orig[6])) $orig[6]='';
    $insertstmt->bind_param('sssssssss',$mitisid,$orig[1],$orig[2],$orig[1],$orig[2],$orig[3],$orig[4],$orig[5],$orig[6]);
    $insertstmt->execute();
    $id=$db->insert_id;
    echo "TN ".$orig[2]." ".$orig[3]." mitisid=".$orig[0]." hinzugefügt.<br />\n";
    $moodletodo=true;
  }
  if($moodleid<=0 || $moodletodo) {
    if(empty($nutzername)) {
      //Neuen Nutzernamen aus Vornamen und Nachnamen gewinnen, dann prüfen, dass er noch nicht belegt ist
      $nn=mb_substr($vorname,0,1);
      $i=mb_strpos($vorname,'-');
      if($i!==false) {
        $nn.=mb_substr($vorname,$i+1,1);
      }
      $nn.='.';
      $nn.=str_replace(" ","",$orig[3]);
      $search=explode(',',' ,,À,Á,Â,Ã,Ä,Å,Æ,Ç,Ć,Č,È,É,Ê,Ë,Ì,Í,Î,Ï,Ñ,Ò,Ó,Ô,Õ,Ö,Ś,Š,Ù,Ú,Û,Ü,Ý,ß,à,á,â,ã,ä,å,æ,ç,ć,č,è,é,ê,ë,ì,í,î,ï,ñ,ò,ó,ô,õ,ö,ś,š,ş,ù,ú,û,ü,ý,ÿ,\',#,+,%,/,:,;,\\,-');
      $replace=explode(',','_,_,A,A,A,A,AE,A,AE,C,C,C,E,E,E,E,I,I,I,I,N,O,O,O,O,OE,S,S,U,U,U,UE,Y,ss,a,a,a,a,ae,a,ae,c,c,c,e,e,e,e,i,i,i,i,n,o,o,o,o,oe,s,s,s,u,u,u,ue,y,y,_,_,_,_,_,_,_,_,_');
      $nn=str_replace($search,$replace,$nn);
      $nn=strtolower($nn);
      for($i=strlen($nn)-1;$i>=0;--$i) {
        $c=substr($nn,$i,1);
        if($c!='_' && $c!='.' && !($c>='a' && $c<='z') && !($c>='0' && $c<='9')) {
          $nn=substr($nn,$i).'_'.substr($nn,$i+1);
        }
      }
      $nr=0;
      $nutzername=$nn;
      while(true) {
        $nutzernamenstmt->bind_param('sss',$nutzername,$nutzername,$nutzername);
        $nutzernamenstmt->execute();
        $result=$nutzernamenstmt->get_result();
        $schon=$result->fetch_object();
        $result->free();
        if(!$schon) {
          break;
        }
        ++$nr;
        $nutzername=$nn.$nr;
      }
    }
    $daten=(object)array(
      'userid'=>$moodleid,
      'nutzername'=>$nutzername,
      'vorname'=>$vorname,
      'nachname'=>$orig[3],
      'email'=>$moodleisttest ? 'testtn'.$mitisid.'@gpb.de' : $orig[4],
      'createpassword'=>($moodleid<=0)
    );
    if($moodleid<=0) {
      $daten->maildisplay=0;
    }
    $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_speichern&daten='.urlencode(json_encode($daten));
    $ergebnis=json_decode(file_get_contents($url));
    if(is_object($ergebnis) && isset($ergebnis->exception)) {
      echo "TN ".$orig[2]." ".$orig[3]." mitisid=".$orig[0]." Moodle Nutzer nicht OK: ".json_encode($ergebnis)."<br />\n";
    } else {
      if(!is_object($ergebnis)) {
        $ergebnis=json_decode($ergebnis);
      }
      $updatemoodlestmt->bind_param('sii',$nutzername,$ergebnis->id,$id);
      $updatemoodlestmt->execute();
      echo "TN ".$orig[2]." ".$orig[3]." mitisid=".$orig[0]." Moodle Nutzer ID=".$ergebnis->id." gespeichert.<br />\n";
    }
  }
  ob_flush();
}
?>
<a href="index.php">Zurück</a>