<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'DozentKurs');
if(empty($kurs)) {
  header('Location:kurse.php');
  exit;
}
$result=$db->query("select * from gpb_kurs_kurzbericht where kursid=".$kurs->id." limit 1");
$kurzbericht=$result->fetch_object();
$result->free();

$altekurse=array();
$altekursebyId=array();
$result=$db->query("select k.id,k.titel,b.*
  from gpb_kurs_view k
  join gpb_kurs_kurzbericht b on b.kursid=k.id
  where (k.moodleid>0 or k.sichtbar)
    and k.kurzbericht_ok 
    and k.id in(select kursid from gpb_kurs_dozent where dozentid=".$ich->id.") 
  order by (k.modulid=".$kurs->modulid.") desc,k.beginn desc
  limit 25");
while($row=$result->fetch_object()) {
  $altekurse[]=$row;
  $altekurseById[$row->id]=$row;
}
$result->free();

require_once 'DozentSeite.php';
$seite=new DozentSeite('Kurs-Kurzbericht');
$seite->anfangGenerieren('');
$kurs->makeSehen();
if(in_array($ich->id,$kurs->dozentenids)) {
    if(empty($kurzbericht)) {
      $kurzbericht=(object)array(
        'kursid'=>$kurs->id,
        'zusammenfassung'=>'',
        'inhaltsgestaltung'=>'',
        'methoden'=>'',
        'eindruck'=>'',
        'todos'=>''
      );
    }
?>
<script>
const altekurseById=<?= json_encode($altekurseById) ?>;
function kurzbericht_uebernehmen() {
  let id=document.getElementById('alterkurs').value;
  if(id) {
    let ak=altekurseById[id];
    document.getElementById('zusammenfassung').value=ak.zusammenfassung;
    document.getElementById('inhaltsgestaltung').value=ak.inhaltsgestaltung;
    document.getElementById('methoden').value=ak.methoden;
    document.getElementById('eindruck').value=ak.eindruck;
    document.getElementById('todos').value=ak.todos;
    if(ak.zusammenfassung || ak.inhaltsgestaltung || ak.methoden || ak.eindruck || ak.todos) {
      document.getElementById('formular').submit();
    }
  }
}
</script>
Hier können Sie den Kurzbericht von einem vorigen Kurs übernehmen<br />
<select id="alterkurs">
<?php
foreach($altekurse as $ak) {
?>
  <option value="<?= $ak->id ?>"><?= $ak->titel ?></option>
<?php
}
?>
</select><br />
<button type="button" onclick="kurzbericht_uebernehmen()">Kurzbericht des ausgewählten Kurses übernehmen</button><br />
<br />
<form action="kurzbericht_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8" id="formular">
  <input type="hidden" name="kursid" value="<?= $kurs->id ?>" />
<table id="kurzbericht" border="0" cellspacing="0" style="border-collapse:collapse;">
  <tr><th>
    Zusammenfassung (Kurze, attraktive Beschreibung des Kursinhalts)
  </th></tr>
  <tr><td><textarea name="zusammenfassung" id="zusammenfassung"><?= $kurzbericht->zusammenfassung ?></textarea></td></tr>
  <tr><th>
    Inhaltliche Gestaltung des Moduls (Abweichungen vom Lehrplan)
  </th></tr>
  <tr><td><textarea name="inhaltsgestaltung" id="inhaltsgestaltung"><?= $kurzbericht->inhaltsgestaltung ?></textarea></td></tr>
  <tr><th>
    Methodisch-didaktische Hinweise (Lehr- und Lernformen)
  </th></tr>
  <tr><td><textarea name="methoden" id="methoden"><?= $kurzbericht->methoden ?></textarea></td></tr>
  <tr><th>
    Gesamteindruck von der Klasse (Besonderheiten, Schwierigkeiten)
  </th></tr>
  <tr><td><textarea name="eindruck" id="eindruck"><?= $kurzbericht->eindruck ?></textarea></td></tr>
  <tr><th>
    Was soll in nachfolgenden Modulen trainiert, vertieft bzw. in anderem Kontext eingesetzt werden?
  </th></tr>
  <tr><td><textarea name="todos" id="todos"><?= $kurzbericht->todos ?></textarea></td></tr>
  <tr><td><input type="submit" value="Speichern" /></td></tr>
</table>
</form>
<?php
} else { //Dozent nicht in diesem Kurs eingetragen: anzeigen, nicht bearbeiten
  if(empty($kurzbericht)) {
?>
<b>Noch kein Kurzbericht gespeichert.<br /></b>
<?php
  } else {
?>
<table id="kurzbericht" border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr><th>
    Zusammenfassung (Kurze, attraktive Beschreibung des Kursinhalts)
  </th></tr>
  <tr><td><?= nl2br($kurzbericht->zusammenfassung) ?></td></tr>
  <tr><th>
    Inhaltliche Gestaltung des Moduls (Abweichungen vom Lehrplan)
  </th></tr>
  <tr><td><?= nl2br($kurzbericht->inhaltsgestaltung) ?></td></tr>
  <tr><th>
    Methodisch-didaktische Hinweise (Lehr- und Lernformen)
  </th></tr>
  <tr><td><?= nl2br($kurzbericht->methoden) ?></td></tr>
  <tr><th>
    Gesamteindruck von der Klasse (Besonderheiten, Schwierigkeiten)
  </th></tr>
  <tr><td><?= nl2br($kurzbericht->eindruck) ?></td></tr>
  <tr><th>
    Was soll in nachfolgenden Modulen trainiert, vertieft bzw. in anderem Kontext eingesetzt werden?
  </th></tr>
  <tr><td><?= nl2br($kurzbericht->todos) ?></td></tr>
</table>
<?php
  }
}
?>
<br />
<a href="kurse.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
exit;
?>