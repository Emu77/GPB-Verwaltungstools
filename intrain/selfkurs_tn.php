<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'IntrainSelfkurs.php';
require_once 'IntrainTn.php';

$selfkurs=Selfkurs::einenLaden(isset($_GET['selfkursid']) ? (int)$_GET['selfkursid'] : (isset($_POST['selfkursid']) ? (int)$_POST['selfkursid'] : 0),'IntrainSelfkurs');
if(empty($selfkurs)) {
  header('Location:selfkurse.php');
  exit;
}

$suche_angemeldet=isset($_SESSION['selfkurs_tn_suche']) ? $_SESSION['selfkurs_tn_suche'] : new Suche('tnname','massnbez');
if(empty($suche_angemeldet->where)) {
  $suche_angemeldet->von=date('Y-m-d');
  $suche_angemeldet->addKriterium("(ausstieg='0000-00-00' or ausstieg is null or ausstieg>=?)",$suche_angemeldet->von,'s');
}
$selfkurs->tns=new Liste('IntrainTn',
  $suche_angemeldet->prepare("select * 
    from gpb_selfkurs_tn sktn
    join gpb_tn_view tn on tn.id=sktn.tnid
    where sktn.selfkursid=".$selfkurs->id." and ","order by nachname,vorname limit 50"));
Tn::refsMassnahmenLaden($selfkurs->tns);

$suche=isset($_SESSION['selfkurs_addtn_suche']) ? $_SESSION['selfkurs_addtn_suche'] : new Suche('tnname','massnbez');
$tns=new Liste('IntrainTn',empty($suche->where) ? null : 
    $suche->prepare("select * from gpb_tn_view where","order by nachname,vorname limit 50"));
Tn::refsMassnahmenLaden($tns);
foreach($selfkurs->tns->alle as $tn) {
  if(isset($tns->byId[$tn->id])) {
    $tns->byId[$tn->id]->angemeldet=true;
  }
}

require_once 'IntrainSeite.php';
$seite=new IntrainSeite('Teilnehmer vom inTrain-Kurs '.$selfkurs->titel);
$seite->anfangGenerieren();
$selfkurs->makeSehen('sehen');
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
function datum_bearbeiten(was,tnid,kopf,wert) {
  stopEdit();
  edited=document.getElementById(was+'_'+tnid);
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
    location.href='selfkurs_tn_speichern.php?tnid='+tnid+'&selfkursid=<?= $selfkurs->id ?>&was='+was+'&neu='+inp.value+'&redirect='+encodeURIComponent('selfkurs_tn.php?selfkursid=<?= $selfkurs->id ?>');
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
function abmelden(tnid,tnmoodleid,name) {
  if(confirm(name+' abmelden, sicher?')) {
    location.href='selfkurs_tn_abmelden.php?tnid='+tnid+'&tnmoodleid='+tnmoodleid+'&selfkursid=<?= $selfkurs->id ?>&selfkursmoodleid=<?= $selfkurs->moodleid ?>&redirect='+encodeURIComponent('selfkurs_tn.php?selfkursid=<?= $selfkurs->id ?>');
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
<table class="anmeldungen" border="1" cellspacing="0" style="border-collapse:collapse;margin-top:1em;margin-bottom:1em;">
  <tr>
    <th colspan="7" align="left">Angemeldete TN</th>
  </tr>
  <tr>
    <th>TN</th>
    <th>Nutzername</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th>Maßnahmen</th>
    <th>Einstieg</th>
    <th>Ausstieg</th>
    <th></th>
  </tr>
  <form action="selfkurs_tn_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="redirect" value="selfkurs_tn.php?selfkursid=<?= $selfkurs->id ?>" />
  <tr>
    <td><input type="text" name="tnname" value="<?= htmlentities($suche_angemeldet->tnname,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td></td>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"></td>
    <td>
      <input type="text" name="massnbez" value="<?= isset($suche->massnbez) ? htmlentities($suche->massnbez,ENT_COMPAT) : '' ?>" style="width:100px;" />
    </td>
<?php
$suche_angemeldet->makeVonBisInput();
$suche_angemeldet->makeSubmit();
?>
  </tr>
  </form>
<?php
foreach($selfkurs->tns->alle as $tn) {
?>
  <tr>
    <td><a href="tn_sehen.php?tnid=<?= $tn->id ?>"><?= $tn->anrede ?> <?= $tn->vorname ?> <?= $tn->nachname ?></a></td>
<?php
  $tn->makeNutzernameTd();
  $tn->makeMoodleTd();
  $tn->makeMassnahmenTd();
?>
    <td align="center"><div class="edit" id="einstieg_<?= $tn->id ?>" onclick="datum_bearbeiten('einstieg',<?= $tn->id ?>,'Einstieg','<?= $tn->von<=0 ? '' : date('Y-m-d',$tn->von) ?>')"><?= $tn->von<=0 ? '' : date('d.m.Y',$tn->von) ?></div></td>
    <td align="center"><div class="edit" id="ausstieg_<?= $tn->id ?>" onclick="datum_bearbeiten('ausstieg',<?= $tn->id ?>,'Ausstieg','<?= $tn->bis<=0 ? '' : date('Y-m-d',$tn->bis) ?>')"><?= $tn->bis<=0 ? '' : date('d.m.Y',$tn->bis) ?></div></td>
    <td><button type="button" onclick="abmelden(<?= $tn->id ?>,<?= $tn->moodleid ?>,'<?= str_replace("'","\\'",$tn->tnname) ?>')">Abmelden</button></td>
  </tr>
<?php
}
?>
  <tr>
    <th colspan="7" align="left">Weitere TN anmelden</th>
  </tr>
  <tr>
    <th>TN</th>
    <th>Nutzername</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th>Maßnahmen</th>
    <th>Einstieg</th>
    <th>Ausstieg</th>
    <th></th>
  </tr>
  <form action="selfkurs_addtn_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="redirect" value="selfkurs_tn.php?selfkursid=<?= $selfkurs->id ?>" />
  <tr>
    <td><input type="text" name="tnname" value="<?= htmlentities($suche->tnname,ENT_COMPAT) ?>" style="width:150px;" /></td>
    <td></td>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"></td>
    <td>
      <input type="text" name="massnbez" value="<?= isset($suche->massnbez) ? htmlentities($suche->massnbez,ENT_COMPAT) : '' ?>" style="width:100px;" />
      Am&nbsp;<input type="date" name="massnwann" value="<?= isset($suche->massnwann) ? htmlentities($suche->massnwann,ENT_COMPAT) : '' ?>" />
    </td>
    <td></td>
    <td></td>
<?php
$suche->makeSubmit();
?>
  </tr>
  </form>
<?php
foreach($tns->alle as $tn) {
  if(isset($tn->angemeldet)) continue;
  $anmeldung=isset($tn->selfkurseById[$selfkurs->id]) ? $tn->selfkurseById[$selfkurs->id] : null;
  if(!$anmeldung) {
?>
  <form action="selfkurs_tn_anmelden.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="hidden" name="selfkursid" value="<?= $selfkurs->id ?>" />
    <input type="hidden" name="selfkursmoodleid" value="<?= $selfkurs->moodleid ?>" />
    <input type="hidden" name="tnid" value="<?= $tn->id ?>" />
    <input type="hidden" name="tnmoodleid" value="<?= $tn->moodleid ?>" />
    <input type="hidden" name="redirect" value="selfkurs_tn.php?selfkursid=<?= $selfkurs->id ?>" />
<?php
  }
?>
  <tr>
    <td><a href="tn_sehen.php?tnid=<?= $tn->id ?>"><?= $tn->anrede ?> <?= $tn->vorname ?> <?= $tn->nachname ?></a></td>
<?php
    $tn->makeNutzernameTd();
    $tn->makeMoodleTd();
    $tn->makeMassnahmenTd();
    if($anmeldung) {
?>
    <td align="center"><div class="edit" id="einstieg_<?= $tn->id ?>" onclick="datum_bearbeiten('einstieg',<?= $tn->id ?>,'Einstieg','<?= $anmeldung->von<=0 ? '' : date('Y-m-d',$anmeldung->von) ?>')"><?= $anmeldung->von<=0 ? '' : date('d.m.Y',$anmeldung->von) ?></div></td>
    <td align="center"><div class="edit" id="ausstieg_<?= $tn->id ?>" onclick="datum_bearbeiten('ausstieg',<?= $tn->id ?>,'Ausstieg','<?= $anmeldung->bis<=0 ? '' : date('Y-m-d',$anmeldung->bis) ?>')"><?= $anmeldung->bis<=0 ? '' : date('d.m.Y',$anmeldung->bis) ?></div></td>
    <td><button type="button" onclick="abmelden(<?= $tn->id ?>,<?= $tn->moodleid ?>,'<?= str_replace("'","\\'",$tn->tnname) ?>')">Abmelden</button></td>
<?php
    } else {
?>
    <td><input type="date" name="einstieg" value="" /></td>
    <td><input type="date" name="ausstieg" value="" /></td>
    <td><input type="submit" value="Anmelden" /></td>
<?php
    }
?>
  </tr>
<?php
  if(!$anmeldung) {
?>
  </form>
<?php
  }
}
?>
</table>
<a href="selfkurse.php">Zurück zur Liste</a>
<?php
$seite->endeGenerieren();
?>