<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'IntrainTn.php';
require_once 'IntrainSelfkurs.php';

$tn=IntrainTn::einenLaden(isset($_GET['tnid']) ? (int)$_GET['tnid'] : 0,'IntrainTn');
if(empty($tn)) {
  header('Location:tn.php');
  exit;
}

$suche=isset($_SESSION['selfkurs_suche']) ? $_SESSION['selfkurs_suche'] : new Suche('titel');

$selfkurse=new Liste('IntrainSelfkurs',empty($suche->where) ? null : 
    $suche->prepare("select sk.* from gpb_selfkurs_view sk where","order by sk.titel limit 50"));
foreach($tn->selfkurse as $kurs) {
  if(isset($selfkurse->byId[$kurs->selfkursid])) {
    $selfkurse->byId[$kurs->selfkursid]->angemeldet=true;
  }
}

require_once 'IntrainSeite.php';
$seite=new IntrainSeite('inTrain-Kurse von TN '.$tn->tnname);
$seite->anfangGenerieren();
$tn->makeSehen('sehen');
?>
<script>
let edited=null;
let editor=null;
function stopEdit() {
  if(editor) {
    editor.remove();
    edited.style.display='';
    edited=null;
    editor=null;
  }
}
function datum_bearbeiten(was,selfkursid,kopf,wert) {
  stopEdit();
  edited=document.getElementById(was+'_'+selfkursid);
  edited.style.display='none';
  editor=document.createElement('div');
  const inp=document.createElement('input');
  inp.type='date';
  inp.value=wert;
  editor.appendChild(inp);
  let btn=document.createElement('button');
  btn.type='button';
  btn.innerText='Speichern';
  btn.onclick=function() {
    location.href='selfkurs_tn_speichern.php?tnid=<?= $tn->id ?>&selfkursid='+selfkursid+'&was='+was+'&neu='+inp.value+'&redirect='+encodeURIComponent('tn_selfkurse.php?tnid=<?= $tn->id ?>');
  }
  editor.appendChild(btn);
  btn=document.createElement('button');
  btn.type='button';
  btn.innerText='Abbrechen';
  btn.onclick=stopEdit;
  editor.appendChild(btn);
  edited.parentNode.insertBefore(editor,edited);
  inp.focus();
}
function abmelden(selfkursid,selfkursmoodleid,titel) {
  if(confirm('TN aus "'+titel+'" abmelden, sicher?')) {
    location.href='selfkurs_tn_abmelden.php?tnid=<?= $tn->id ?>&tnmoodleid=<?= $tn->moodleid ?>&selfkursid='+selfkursid+'&selfkursmoodleid='+selfkursmoodleid+'&redirect='+encodeURIComponent('tn_selfkurse.php?tnid=<?= $tn->id ?>');
  }
}
</script>
<?php
if(isset($_SESSION['fehler_moodleid'])) {
?>
<div class="fehler"><?= $_SESSION['fehler_moodleid'] ?></div>
<?php
  unset($_SESSION['fehler_moodleid']);
}
?>
<table class="anmeldungen" border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
  <tr>
    <th align="left" colspan="4">Angemeldet in</th>
  </tr>
  <tr>
    <th>Kurs</th>
    <th>Von</th>
    <th>Bis</th>
    <th></th>
  </tr>
<?php
if(empty($tn->selfkurse)) {
?>
  <tr>
    <td colspan="4">Noch keine Anmeldungen</td>
  </tr>
<?php
} else {
  foreach($tn->selfkurse as $selfkurs) {
?>
  <tr>
    <td><a href="selfkurs_sehen.php?selfkursid=<?= $selfkurs->selfkursid ?>"><?= $selfkurs->titel ?></a></td>
    <td align="center"><div class="edit" id="einstieg_<?= $selfkurs->selfkursid ?>" onclick="datum_bearbeiten('einstieg',<?= $selfkurs->selfkursid ?>,'Einstieg','<?= $selfkurs->von<=0 ? '' : date('Y-m-d',$selfkurs->von) ?>')"><?= $selfkurs->von<=0 ? '' : date('d.m.Y',$selfkurs->von) ?></div></td>
    <td align="center"><div class="edit" id="ausstieg_<?= $selfkurs->selfkursid ?>" onclick="datum_bearbeiten('ausstieg',<?= $selfkurs->selfkursid ?>,'Ausstieg','<?= $selfkurs->bis<=0 ? '' : date('Y-m-d',$selfkurs->bis) ?>')"><?= $selfkurs->bis<=0 ? '' : date('d.m.Y',$selfkurs->bis) ?></div></td>
    <td><button type="button" onclick="abmelden(<?= $selfkurs->selfkursid ?>,<?= $selfkurs->moodleid ?>,'<?= str_replace("'","\\'",$selfkurs->titel) ?>')">Abmelden</button></td>
  </tr>
<?php
  }
}
?>
  <tr>
    <th align="left" colspan="4">In weitere Kurse anmelden</th>
  </tr>
  <tr>
    <th>Kurs</th>
    <th>Von</th>
    <th>Bis</th>
    <th></th>
  </tr>
  <form action="selfkurse_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="redirect" value="tn_selfkurse.php?tnid=<?= $tn->id ?>" />
  <tr>
<?php
$suche->makeStringInput('titel');
?>
    <td></td>
    <td></td>
    <td><input type="submit" value="Suchen" /></td>
  </tr>
  </form>
<?php
foreach($selfkurse->alle as $selfkurs) {
  if(isset($selfkurs->angemeldet)) continue;
?>
  <form action="selfkurs_tn_anmelden.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="tnid" value="<?= $tn->id ?>" />
    <input type="hidden" name="tnmoodleid" value="<?= $tn->moodleid ?>" />
    <input type="hidden" name="selfkursid" value="<?= $selfkurs->id ?>" />
    <input type="hidden" name="selfkursmoodleid" value="<?= $selfkurs->moodleid ?>" />
    <input type="hidden" name="redirect" value="tn_selfkurse.php?tnid=<?= $tn->id ?>" />
  <tr>
    <td><a href="selfkurs_sehen.php?selfkursid=<?= $selfkurs->id ?>"><?= $selfkurs->titel ?></a></td>
    <td align="center"><input type="date" name="einstieg" value="" /></td>
    <td align="center"><input type="date" name="ausstieg" value="" /></td>
    <td><input type="submit" value="Anmelden" /></td>
  </tr>
  </form>
<?php
}
?>
</table>
<a href="tn.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
?>