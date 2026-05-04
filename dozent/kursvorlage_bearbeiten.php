<?php
require_once 'check_login.php';
require_once 'DozentKursvorlage.php';

if(isset($_GET['kursvorlageid']) && $_GET['kursvorlageid']!='0') {
  $vorlage=Kursvorlage::einenLaden((int)$_GET['kursvorlageid'],'DozentKursvorlage');
  if(empty($vorlage)) {
    header('Location:kursvorlagen.php');
    exit;
  }
  $vorlage->moodleerstellen=($vorlage->moodleid<=0);
} else {
  $titel=isset($_GET['titel']) ? $_GET['titel'] : '';
  $dozentenids=isset($_GET['dozentenids']) ? $_GET['dozentenids'] : ''.$ich->id;
  $dozenten=array();
  if(!empty($dozentenids)) {
    $result=$db->query("select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent where id in(".addslashes($dozentenids).")");
    $dozentenids=array();
    while($row=$result->fetch_object()) {
      $dozenten[]=$row;
      $dozentenids[]=$row->id;
    }
    $result->free();
  }
  $moodleid=isset($_GET['moodleid']) ? (int)$_GET['moodleid'] : 0;
  $vorlage=(object)array(
    'id'=>0,
    'titel'=>$titel,
    'dozenten'=>$dozenten,
    'dozentenids'=>$dozentenids,
    'moodleerstellen'=>($moodleid<=0),
    'moodleid'=>$moodleid
  );
}
if(isset($_SESSION['fehler']['kursvorlage'])) {
  $fehler=$_SESSION['fehler'];
  unset($_SESSION['fehler']);
  foreach($fehler['kursvorlage'] as $k=>$v) {
    $vorlage->$k=$v;
  }
  $vorlage->dozenten=array();
  if(!empty($vorlage->dozentenids)) {
    $result=$db->query("select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent where id in(".addslashes(implode(',',$vorlage->dozentenids)).")");
    $vorlage->dozentenids=array();
    while($row=$result->fetch_object()) {
      $vorlage->dozenten[]=$row;
      $vorlage->dozentenids[]=$row->id;
    }
    $result->free();
  }
}

require_once 'DozentSeite.php';
$seite=new DozentSeite('Kursvorlage '.$vorlage->titel.' bearbeiten');
$seite->anfangGenerieren();
?>
<script>
function dozenten_suche_onkey(ev) {
  if(ev.key=='Enter') {
    ev.preventDefault();
    ev.stopPropagation();
    dozenten_finden();
  }
}
function dozenten_finden() {
  let name=document.getElementById('dozenten_suche').value.trim();
  if(!name) {
    return;
  }
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if (req.responseText.startsWith('OK')) {
      let dozs=JSON.parse(req.responseText.substring(2));
      let sel=document.getElementById('gefundene_dozenten');
      for(let i=sel.childNodes.length-1;i>=0;--i) {
        sel.childNodes[i].remove();
      }
      for(let d of dozs) {
        let o=document.createElement('option');
        o.value=d.id;
        o.innerText=d.dozentenname;
        sel.appendChild(o);
      }
    } else {
      alert(req.responseText);
    }
  };
  req.open('GET','dozenten_finden.php?name='+encodeURIComponent(name));
  req.send();
}
var dozentenids=<?= json_encode($vorlage->dozentenids) ?>;
function dozent_hinzufuegen() {
  let select=document.getElementById('gefundene_dozenten');
  let idx=select.selectedIndex;
  if(idx<0 || idx>=select.options.length) {
    return;
  }
  let o=select.options[idx];
  if(o.value=='<?= $ich->id ?>') return;
  o.remove();
  document.getElementById('dozenten').appendChild(o);
  dozentenids.push(o.value);
  document.getElementById('dozentenids').value=dozentenids.join(',');
}
function dozenten_hinzufuegen() {
  let select=document.getElementById('gefundene_dozenten');
  for(let idx=0;idx<select.options.length;++idx) {
    let o=select.options[idx];
    if(!o.selected) continue;
    if(o.value=='<?= $ich->id ?>') continue;
    o.remove();
    --idx;
    document.getElementById('dozenten').appendChild(o);
    dozentenids.push(o.value);
    document.getElementById('dozentenids').value=dozentenids.join(',');
  }
}
function dozent_entfernen() {
  let select=document.getElementById('dozenten');
  let idx=select.selectedIndex;
  if(idx<0 || idx>=select.options.length) {
    return;
  }
  let o=select.options[idx];
  if(o.value=='<?= $ich->id ?>') return;
  o.remove();
  document.getElementById('gefundene_dozenten').appendChild(o);
  dozentenids.splice(dozentenids.indexOf(o.value),1);
  document.getElementById('dozentenids').value=dozentenids.join(',');
}
function dozenten_entfernen() {
  let select=document.getElementById('dozenten');
  for(let idx=0;idx<select.options.length;++idx) {
    let o=select.options[idx];
    if(!o.selected) continue;
    if(o.value=='<?= $ich->id ?>') continue;
    o.remove();
    --idx;
    document.getElementById('gefundene_dozenten').appendChild(o);
    dozentenids.splice(dozentenids.indexOf(o.value),1);
    document.getElementById('dozentenids').value=dozentenids.join(',');
  }
}
function moodleerstellen_geaendert() {
  let erstellen=document.getElementById('moodleerstellen').checked;
  document.getElementById('moodleid').disabled=erstellen;
}
</script>
<form action="kursvorlage_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="id" value="<?= $vorlage->id ?>" />
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="bearbeiten">
  <tr>
    <th>Titel</th>
    <td>
      <input type="text" name="titel" id="titel" value="<?= htmlentities($vorlage->titel,ENT_COMPAT) ?>" style="width:400px;" />
    </td>
    <td class="fehler"><?= isset($fehler['titel']) ? $fehler['titel'] : '' ?></td>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">
<?php
if($vorlage->id>0 && $vorlage->moodleid>0) {
?>
      <a href="<?= $moodleurl ?>course/view.php?id=<?= $vorlage->moodleid ?>" target="moodle">Moodle-ID=<?= $vorlage->moodleid ?></a>
<?php
} else {
?>
      Moodle-Kurs wird automatisch erstellt.
<?php
}
?>
    </td>
    <td class="fehler"><?= isset($fehler['moodleid']) ? $fehler['moodleid'] : '' ?></td>
  </tr>
  <tr>
    <th>Dozenten</th>
    <td valign="bottom">
      <input type="hidden" name="dozentenids" id="dozentenids" value="<?= implode(',',$vorlage->dozentenids) ?>" />
      <div style="display:inline-block;">
        <select id="dozenten" multiple ondblclick="dozent_entfernen()" style="width:200px;">
<?php
foreach($vorlage->dozenten as $d) {
?>
          <option value="<?= $d->id ?>"><?= $d->dozentenname ?></option>
<?php
}
?>
        </select><br />
        <button type="button" onclick="dozenten_entfernen()">Markierte entfernen</button>
      </div>
      <div style="display:inline-block;" class="suche">
        <input type="text" id="dozenten_suche" value="" style="width:200px;" onchange="dozenten_finden()" onkeydown="dozenten_suche_onkey(event)" /><input type="button" value="&#x1F50D;" class="suchbutton" /><br />
        <select id="gefundene_dozenten" multiple ondblclick="dozent_hinzufuegen()" style="width:220px;"></select><br />
        <button type="button" onclick="dozenten_hinzufuegen()">Markierte hinzufügen</button>
      </div>
    </td>
    <td class="fehler"><?= isset($fehler['dozenten']) ? $fehler['dozenten'] : '' ?></td>
  </tr>
  <tr>
    <th></th>
    <td>
      <input type="submit" value="Speichern" />
      <a href="kursvorlagen.php">Abbrechen</a>
    </td>
    <td class="fehler"><?= isset($fehler['speichern']) ? $fehler['speichern'] : '' ?></td>
  </tr>
</table>
</form>
<?php
$seite->endeGenerieren();
?>