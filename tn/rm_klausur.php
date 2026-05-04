<?php
require_once 'check_login.php';

$fragen=array();
$result=$db->query("select f.*,k.antwort
  from rm_klausur_frage f
  left outer join rm_klausur k on k.tnid=".$ich->id." and k.fragenid=f.id
  order by f.id");
while($row=$result->fetch_object()) {
  $fragen[]=$row;
}
$result->free();

require_once 'TnSeite.php';
$seite=new TnSeite('Klausur IT-Sicherheit 02.04.2026');
$seite->anfangGenerieren();
?>
<script>
function antwort_speichern(fragenid) {
  let antwort=document.getElementById('antwort_'+fragenid).value;
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText.startsWith('OK')) {
      document.getElementById('gespeichert_'+fragenid).innerText='gespeichert!';
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','rm_klausur_antwort_speichern.php?fragenid='+fragenid+'&antwort='+encodeURIComponent(antwort));
  req.send();
}
</script>
<?php
foreach($fragen as $f) {
  echo $f->id.'. '.nl2br($f->frage);
?>
<br />
<textarea id="antwort_<?= $f->id ?>" style="width:200px;height:4em;"><?= empty($f->antwort) ? '' : $f->antwort ?></textarea><br />
<button type="button" onclick="antwort_speichern(<?= $f->id ?>)">Speichern</button> <span id="gespeichert_<?= $f->id ?>" style="color:green;"></span><br />
<br />
<?php
}
$seite->endeGenerieren();
?>