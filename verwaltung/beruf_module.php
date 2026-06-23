<?php
require_once 'check_login.php';

$berufid=isset($_GET['berufid']) ? (int)$_GET['berufid'] : 0;
if($berufid<=0) {
  header('Location:berufe.php');
  exit;
}

$module=array();
$moduleById=array();
$result=$db->query("select bm.*
    ,m.*
    ,(select count(*) from gpb_kurs where modulid=m.id) as anzahlKurse
  from gpb_beruf_modul bm
  join gpb_modul m on m.id=bm.modulid
  where bm.berufid=".$berufid."
  order by bm.nummer,m.titel");
while($row=$result->fetch_object()) {
  $row->von=empty($row->beginn) || $row->beginn=='0000-00-00' ? '-' : date('d.m.Y',strtotime($row->beginn));
  $row->bis=empty($row->ende) || $row->ende=='0000-00-00' ? '-' : date('d.m.Y',strtotime($row->ende));
  $row->berufids=[];
  $module[]=$row;
  $moduleById[$row->id]=$row;
}
$result->free();

$berufeById=array($berufid=>true);
if(!empty($moduleById)) {
  $result=$db->query("select * from gpb_beruf_modul where modulid in(".implode(',',array_keys($moduleById)).") and berufid<>".$berufid);
  while($row=$result->fetch_object()) {
    $moduleById[$row->modulid]->berufids[]=$row->berufid;
    $berufeById[$row->berufid]=true;
  }
  $result->free();
}

$result=$db->query("select b.*
    ,f.kuerzel as familiekuerzel
    from gpb_beruf b
    left outer join gpb_berufsfamilie f on f.id=b.familieid
    where b.id in(".implode(',',array_keys($berufeById)).")");
while($row=$result->fetch_object()) {
  $berufeById[$row->id]=$row;
}
$result->free();
$beruf=$berufeById[$berufid];

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Module vom Beruf "'.$beruf->familiekuerzel.' / '.$beruf->bezeichnung.'"');
$seite->anfangGenerieren();
?>
<div style="margin-bottom:1em;">
  <script>
  function module_finden(ja) {
    document.getElementById('div_module_finden').style.display=ja ? '' : 'none';
    document.getElementById('a_module_finden').style.display=ja ? 'none' : '';
  }
  function neues_modul(ja) {
    document.getElementById('form_neues_modul').style.display=ja ? '' : 'none';
    document.getElementById('a_neues_modul').style.display=ja ? 'none' : '';
  }
  function suche_titel_keyup(ev) {
    if(ev.key=='Enter') {
      module_suchen();
    }
  }
  function module_suchen() {
    let titel=document.getElementById('suche_titel').value.trim();
    if(titel.length<=0) {
      document.getElementById('suche_titel').focus();
      return;
    }
    const req=new XMLHttpRequest();
    req.onreadystatechange = function() {
      if (req.readyState != 4) return;
      if (req.responseText.startsWith('OK')) {
        let module=JSON.parse(req.responseText.substring(2));
        let sel=document.getElementById('gefundene_module');
        for(let i=sel.childNodes.length-1;i>=0;--i) {
          sel.childNodes[i].remove();
        }
        for(let m of module) {
          let o=document.createElement('option');
          o.value=m.id;
          o.innerText=m.titel+' ('+m.dauer+' Wo, in '+m.anzahlBerufe+' Berufen)';
          sel.appendChild(o);
        }
      } else {
        alert(req.responseText);
      }
    };
    req.open('GET','beruf_module_finden.php?titel='+encodeURIComponent(titel));
    req.send();
  }
  function modul_hinzufuegen() {
    let sel=document.getElementById('gefundene_module');
    if(sel.value) {
      location.href='beruf_modul_hinzufuegen.php?berufid=<?= $berufid ?>&modulid='+sel.value;
    }
  }
  </script>
  <a id="a_module_finden" href="javascript:module_finden(true)">Module finden</a>
  <div id="div_module_finden" style="display:none;">
    Kürzel oder Titel: <input type="text" id="suche_titel" value="" style="width:150px;" onchange="module_suchen()" onkeyup="suche_titel_keyup(event)" /> <button onclick="module_suchen()">Suchen</button><br />
    <select id="gefundene_module" multiple="true" style="min-width:250px;min-height:200px;" ondblclick="modul_hinzufuegen()"></select><br />
    <button onclick="modul_hinzufuegen()">Markiertes hinzufügen</button> <a href="javascript:module_finden(false)">Abbrechen</a>
  </div>
  <a id="a_neues_modul" href="javascript:neues_modul(true)">Neues Modul</a>
  <form id="form_neues_modul" action="modul_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8" style="display:none;">
    <input type="hidden" name="berufid" value="<?= $beruf->id ?>" />
    <input type="hidden" name="id" value="0" />
  <table border="1" cellspacing="0" style="border-collapse:collapse;">
    <tr>
      <th>Kürzel</th>
      <td><input type="text" name="kuerzel" value="" style="width:300px;" /></td>
    </tr>
    <tr>
      <th>Titel</th>
      <td><input type="text" name="titel" value="" style="width:300px;" /></td>
    </tr>
    <tr>
      <th>Standard-Dauer</th>
      <td><input type="number" name="dauer" value="2" style="width:50px;" /> Wochen</td>
    </tr>
    <tr>
      <th></th>
      <td>
        <input type="submit" value="Erstellen" />
        <a href="javascript:neues_modul(false)">Abbrechen</a>
      </td>
    </tr>
  </table>
  </form>
</div>

<style>
a.aendern {
  text-decoration:none;
  color:black;
}
a.aendern:before {
  content:'✎ ';
}
</style>
<script>
let module=<?= json_encode($moduleById) ?>;
function titel_aendern(modulid) {
  let t=prompt('Neuer Titel:',module[modulid].titel);
  if(t && t!=module[modulid].titel) {
    location.href='modul_info_speichern.php?berufid=<?= $beruf->id ?>&id='+modulid+'&spalte=titel&wert='+encodeURIComponent(t);
  }
}
function kuerzel_aendern(modulid) {
  let k=prompt('Neues Kürzel:',module[modulid].kuerzel);
  if(k!==null && k!=module[modulid].kuerzel) {
    location.href='modul_info_speichern.php?berufid=<?= $beruf->id ?>&id='+modulid+'&spalte=kuerzel&wert='+encodeURIComponent(k);
  }
}
function notiz_aendern(modulid) {
  let n=prompt('Neue Notiz:',module[modulid].notiz);
  if(n!==null && n!=module[modulid].notiz) {
    location.href='modul_info_speichern.php?berufid=<?= $beruf->id ?>&id='+modulid+'&spalte=notiz&wert='+encodeURIComponent(n);
  }
}
function dauer_aendern(modulid) {
  let d=prompt('Neue Dauer:',module[modulid].dauer);
  if(d && d!=module[modulid].dauer) {
    location.href='modul_info_speichern.php?berufid=<?= $beruf->id ?>&id='+modulid+'&spalte=dauer&wert='+encodeURIComponent(d);
  }
}
function pflichtig_aendern(modulid) {
  location.href='modul_info_speichern.php?berufid=<?= $beruf->id ?>&id='+modulid+'&spalte=pflichtig';
}
function modul_entfernen(modulid) {
  if(confirm('Modul "'+module[modulid].titel+'" entfernen, sicher?')) {
    location.href='beruf_modul_entfernen.php?berufid=<?= $beruf->id ?>&modulid='+modulid;
  }
}
</script>
<form action="beruf_modulnummern_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="berufid" value="<?= $beruf->id ?>" />
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Notiz</th>
    <th>Reihenfolge</th>
    <th>Kürzel</th>
    <th>Titel</th>
    <th>Standard-Dauer</th>
    <th>Pflichtig</th>
    <th>auch Teil von</th>
    <th>Kurse</th>
    <th></th>
  </tr>
<?php
foreach($module as $m) {
?>
  <tr>
    <td><a class="aendern" href="javascript:notiz_aendern(<?= $m->modulid ?>)"><?= $m->notiz ?></a></td>
    <td><input type="number" name="nummer_<?= $m->modulid ?>" value="<?= $m->nummer ?>" style="width:60px;" /></td>
    <td><a class="aendern" href="javascript:kuerzel_aendern(<?= $m->modulid ?>)"><?= $m->kuerzel ?></a></td>
    <td><a class="aendern" href="javascript:titel_aendern(<?= $m->modulid ?>)"><?= $m->titel ?></a></td>
    <td><a class="aendern" href="javascript:dauer_aendern(<?= $m->modulid ?>)"><?= $m->dauer ?> Wo</a></td>
    <td><a class="aendern" href="javascript:pflichtig_aendern(<?= $m->modulid ?>)"><?= $m->pflichtig ? 'Pflichtig' : 'Optional' ?></a></td>
    <td>
<?php
  foreach($m->berufids as $bid) {
    $b=$berufeById[$bid];
?>
      <a href="beruf_module.php?berufid=<?= $bid ?>"><?= $b->familiekuerzel ?> / <?= $b->bezeichnung ?></a><br />
<?php
  }
?>
    </td>
    <td align="center"><a href="modul_kurse.php?modulid=<?= $m->id ?>"><?= $m->anzahlKurse ?> Kurse</a></td>
    <td><a href="javascript:modul_entfernen(<?= $m->modulid ?>)">Entfernen</a></td>
  </tr>
<?php
}
?>
<tr>
  <td></td>
  <td><button type="submit">Nummern<br />speichern</button></td>
  <td colspan="7">
<?php
if(isset($_SESSION['module_nachricht'])) {
  echo $_SESSION['module_nachricht'];
  unset($_SESSION['module_nachricht']);
}
?>
  </td>
</tr>
</table>
</form>
<br />
<br />
<a href="berufe.php">Zurück</a>
<?php
$seite->endeGenerieren();
?>