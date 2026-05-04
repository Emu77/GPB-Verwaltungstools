<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once 'BeratungFerien.php';

if(isset($_GET['ferienid']) && $_GET['ferienid']!='0') {
  $ferien=Ferien::einenLaden((int)$_GET['ferienid'],'BeratungFerien');
  if(empty($ferien)) {
    header('Location:ferien.php');
    exit;
  }
} else {
  $beginn=isset($_GET['beginn']) ? $_GET['beginn'] : '';
  $ende=isset($_GET['ende']) ? $_GET['ende'] : '';
  $anlass=isset($_GET['anlass']) ? $_GET['anlass'] : '';
  $ort=isset($_GET['ort']) ? $_GET['ort'] : '';
  $klassenids=isset($_GET['klassenids']) ? $_GET['klassenids'] : array();
  $klassen=array();
  if(!empty($klassenids)) {
    $result=$db->query("select k.* from gpb_klasse_view k where k.id in(".addslashes($klassenids).") order by k.bezeichnung");
    $klassenids=array();
    while($row=$result->fetch_object()) {
      $klassen[]=$row;
      $klassenids[]=$row->id;
    }
    $result->free();
  }
  $art=isset($_GET['$klassenids']) ? 'Klassenferien' : (isset($_GET['ort']) ? 'Institutsferien' : 'Feiertag');
  $ferien=(object)array(
    'id'=>0,
    'beginn'=>$beginn,
    'ende'=>$ende,
    'anlass'=>$anlass,
    'art'=>$art,
    'ort'=>$ort,
    'klassen'=>$klassen,
    'klassenids'=>$klassenids
  );
}

$arten=array('Feiertag','GPB Ferien','Institutsferien','Klassenferien','Berliner Schulferien');
$orte=array('-','Mitte','Neukölln');

require_once 'BeratungSeite.php';
$seite=new BeratungSeite('Ferienzeit '.$ferien->anlass.' '.substr($ferien->beginn,0,4).' bearbeiten');
$seite->anfangGenerieren();
?>
<script>
function art_ausgewaehlt() {
  let art=document.getElementById('art').value;
  if(art=='Institutsferien' || art=='Klassenferien') {
    document.getElementById('ort_tr').style.display='';
  }
  else {
    document.getElementById('ort_tr').style.display='none';
  }
  if(art=='Klassenferien') {
    document.getElementById('klassen_tr').style.display='';
  }
  else {
    document.getElementById('klassen_tr').style.display='none';
  }
}
function klassen_suche_onkey(ev) {
  if(ev.key=='Enter') {
    ev.preventDefault();
    ev.stopPropagation();
    klassen_finden();
  }
}
function klassen_finden() {
  let nort=document.getElementById('ort').value;
  let bez=document.getElementById('klassen_suche').value.trim();
  if(!bez) {
    return;
  }
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText.startsWith('OK')) {
      let klass=JSON.parse(req.responseText.substring(2));
      let sel=document.getElementById('gefundene_klassen');
      for(let i=sel.childNodes.length-1;i>=0;--i) {
        sel.childNodes[i].remove();
      }
      for(let k of klass) {
        let o=document.createElement('option');
        o.value=k.id;
        o.innerText=k.bezeichnung;
        sel.appendChild(o);
      }
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','klassen_finden.php?ort='+encodeURIComponent(nort)+'&bezeichnung='+encodeURIComponent(bez));
  req.send();
}
var klassenids=<?= json_encode($ferien->klassenids) ?>;
function klasse_hinzufuegen() {
  let select=document.getElementById('gefundene_klassen');
  let idx=select.selectedIndex;
  if(idx<0 || idx>=select.options.length) {
    return;
  }
  let o=select.options[idx];
  o.remove();
  document.getElementById('klassen').appendChild(o);
  klassenids.push(o.value);
  document.getElementById('klassenids').value=klassenids.join(',');
}
function klasse_entfernen() {
  let select=document.getElementById('klassen');
  let idx=select.selectedIndex;
  if(idx<0 || idx>=select.options.length) {
    return;
  }
  let o=select.options[idx];
  o.remove();
  document.getElementById('gefundene_klassen').appendChild(o);
  klassenids.splice(klassenids.indexOf(o.value),1);
  document.getElementById('klassenids').value=klassenids.join(',');
}
</script>
<form action="ferien_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="id" value="<?= $ferien->id ?>" />
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="bearbeiten">
  <tr>
    <th>Beginn</th>
    <td><input type="date" name="beginn" value="<?= $ferien->beginn ?>" /></td>
  </tr>
  <tr>
    <th>Ende</th>
    <td><input type="date" name="ende" value="<?= $ferien->ende ?>" /></td>
  </tr>
  <tr>
    <th>Anlass</th>
    <td><input type="text" name="anlass" value="<?= htmlentities($ferien->anlass,ENT_COMPAT) ?>" style="width:200px;" /></td>
  </tr>
  <tr>
    <th>Art</th>
    <td><select name="art" id="art" onchange="art_ausgewaehlt()">
<?php
foreach($arten as $art) {
?>
      <option value="<?= htmlentities($art,ENT_COMPAT) ?>" <?= $art==$ferien->art ? 'selected' : '' ?>><?= $art ?></option>
<?php
}
?>
    </select></td>
  </tr>
  <tr id="ort_tr" <?= $ferien->art=='Institutsferien' || $ferien->art=='Klassenferien' ? '' : 'style="display:none;"' ?>>
    <th>Ort</th>
    <td><select name="ort" id="ort">
<?php
foreach($orte as $ort) {
?>
      <option value="<?= htmlentities($ort,ENT_COMPAT) ?>" <?= $ort==$ferien->ort ? 'selected' : '' ?>><?= $ort ?></option>
<?php
}
?>
    </select></td>
  </tr>
  <tr id="klassen_tr" <?= $ferien->art=='Klassenferien' ? '' : 'style="display:none;"' ?>>
    <th>Klassen</th>
    <td valign="bottom">
      <input type="hidden" name="klassenids" id="klassenids" value="<?= implode(',',$ferien->klassenids) ?>" />
      <select id="klassen" multiple ondblclick="klasse_entfernen()" style="width:200px;">
<?php
foreach($ferien->klassen as $k) {
?>
        <option value="<?= $k->id ?>"><?= $k->bezeichnung ?></option>
<?php
}
?>
      </select>
      <div style="display:inline-block">
        <input type="text" id="klassen_suche" value="" style="width:165px;" onchange="klassen_finden()" onkeydown="klassen_suche_onkey(event)" /><input type="button" value="&#x1F50D;" class="suchbutton" /><br />
        <select id="gefundene_klassen" multiple ondblclick="klasse_hinzufuegen()" style="width:200px;"></select>
      </div>
    </td>
  </tr>
  <tr>
    <th></th>
    <td><input type="submit" value="Speichern" /> <a href="ferien.php">Abbrechen</a></td>
  </tr>
</table>
</form>
<?php
$seite->endeGenerieren();
?>