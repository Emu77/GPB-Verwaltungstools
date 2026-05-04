<?php
require_once '../../db.php';
require_once '../../Seite.php';
$seite=new Seite('Berufe aus Moodle importieren','../');
$seite->anfangGenerieren();
if(isset($_FILES['BerufeAusMoodle']) && file_exists($_FILES['BerufeAusMoodle']['tmp_name'])) {  
  $f=fopen($_FILES['BerufeAusMoodle']['tmp_name'],'rt');
  $berufe=array();
  //36;id;fullname;shortname;family;qualifications;idents;cats;bkz;version;kq;
  fgetcsv($f,null,';',' ');
  while($row=fgetcsv($f,null,';','"')) {
    $berufe[]=$row;
  }
  fclose($f);
  echo "Datei geladen, ".count($berufe)." Datensätze<br />\n";
  
  $stmt_familie=$db->prepare("select * from gpb_berufsfamilie where bezeichnung=? limit 1");
  $stmt_beruf=$db->prepare("select * from gpb_beruf where familieid=? and bezeichnung=? limit 1");
  $stmt_mitiskuerzel=$db->prepare("update gpb_beruf set mitiskuerzel=? where id=? limit 1");
  $stmt_modul=$db->prepare("select * from gpb_modul where titel=? limit 1");
  $stmt_insert_modul=$db->prepare("insert into gpb_modul(titel) values(?)");
  $stmt_beruf_modul=$db->prepare("select * from gpb_beruf_modul where berufid=? and modulid=? limit 1");
  $stmt_insert_beruf_modul=$db->prepare("insert into gpb_beruf_modul(berufid,modulid) values(?,?)");
  foreach($berufe as $row) {
    echo 'Suche Familie '.$row[4];
    $stmt_familie->bind_param('s',$row[4]);
    $stmt_familie->execute();
    $result=$stmt_familie->get_result();
    $familie=$result->fetch_object();
    $result->free();
    if(!$familie) {
      echo " nicht gefunden :-(<br />\n";
      echo "Zu bearbeiten: ";
      printr($row);
      echo "<br />\n";
      continue;
    }
    echo " OK<br />\n";
    echo "Suche Beruf ".$row[2];
    $stmt_beruf->bind_param('is',$familie->id,$row[2]);
    $stmt_beruf->execute();
    $result=$stmt_beruf->get_result();
    $beruf=$result->fetch_object();
    $result->free();
    if(!$beruf) {
      echo " nicht gefunden :-(<br />\n";
      echo "Zu bearbeiten: ";
      var_dump($row);
      echo "<br />\n";
      continue;
    }
    echo " OK<br />\n";
    if($beruf->mitiskuerzel!=$row[6]) {
      $stmt_mitiskuerzel->bind_param('si',$row[6],$beruf->id);
      $stmt_mitiskuerzel->execute();
      echo "MITIS-Kürzel aktualisiert<br />\n";
    }
    foreach(explode("\\n",$row[7]) as $titel) {
      if(empty($titel)) {
        continue;
      }
      if($titel[0]!=' ' && $titel[0]!="\t") {
         continue; //Abschnitt, wird hier nicht berücksichtigt
      }
      $titel=trim($titel);
      if(empty($titel)) {
        continue;
      }
      echo "Suche Modul ".$titel;
      $stmt_modul->bind_param('s',$titel);
      $stmt_modul->execute();
      $result=$stmt_modul->get_result();
      $modul=$result->fetch_object();
      $result->free();
      $schon=false;
      if($modul) {
        echo " gefunden<br />\n";
        $stmt_beruf_modul->bind_param('ii',$beruf->id,$modul->id);
        $stmt_beruf_modul->execute();
        $result=$stmt_beruf_modul->get_result();
        $schon=$result->fetch_object();
        $result->free();
      } else {        
        $stmt_insert_modul->bind_param('s',$titel);
        $stmt_insert_modul->execute();
        $modul=(object)array(
          'id'=>$db->insert_id,
          'titel'=>$titel
        );
        echo " neu<br />\n";
      }
      if(!$schon) {
        $stmt_insert_beruf_modul->bind_param('ii',$beruf->id,$modul->id);
        $stmt_insert_beruf_modul->execute();
      }
    }
  }
  echo "Import fertig!<br />\n";
}
?>
<form action="module_import.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  BerufeAusMoodle.csv <input type="file" name="BerufeAusMoodle" /> <input type="submit" value="Hochladen" />
</form>
<?php
$seite->endeGenerieren();
?>