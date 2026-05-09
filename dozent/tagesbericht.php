<?php
require_once 'check_login.php';
require_once 'DozentKurs.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'DozentKurs');
if(empty($kurs)) {
  header('Location:index.php');
  exit;
}
$kurs->ferienLaden();

$berichtByTag=array();
$result=$db->query("select * from gpb_kurs_tagesbericht where kursid=".$kurs->id);
while($row=$result->fetch_object()) {
  $berichtByTag[$row->tag]=$row;
}
$result->free();
$leererBericht=(object)array(
  'themen'=>'',
  'kguil'=>'',
  'bemerkungen'=>''
);

$tagnamen=array('Mon'=>'Mo','Tue'=>'Di','Wed'=>'Mi','Thu'=>'Do','Fri'=>'Fr','Sat'=>'Sa','Sun'=>'So');

$altekurse=array();
$altekursebyId=array();
$result=$db->query("select k.id,k.titel
  from gpb_kurs_view k
  where (k.moodleid>0 or k.sichtbar)
    and k.tagesbericht_ok 
    and k.id in(select kursid from gpb_kurs_dozent where dozentid=".$ich->id.") 
  order by (k.modulid=".$kurs->modulid.") desc,k.beginn desc
  limit 25");
while($row=$result->fetch_object()) {
  $row->tagesbericht=array();
  $altekurse[]=$row;
  $altekurseById[$row->id]=$row;
}
$result->free();
if(!empty($altekurseById)) {
  $result=$db->query("select * from gpb_kurs_tagesbericht where kursid in(".implode(',',array_keys($altekurseById)).") order by kursid,tag");
  while($row=$result->fetch_object()) {
    $altekurseById[$row->kursid]->tagesbericht[]=$row;
  }
  $result->free();
}

require_once 'DozentSeite.php';
$seite=new DozentSeite('Kurs-Tagesbericht');
$seite->anfangGenerieren('');
$kurs->makeSehen();
if(in_array($ich->id,$kurs->dozentenids)) {
?>
<script>
const altekurseById=<?= json_encode($altekurseById) ?>;
function tagesbericht_uebernehmen() {
  let id=document.getElementById('alterkurs').value;
  if(id) {
    let ak=altekurseById[id];
    let t=document.getElementById('tagesbericht');
    for(let i=0,ir=1;i<ak.tagesbericht.length && ir<t.rows.length-1;++i,++ir) {
      t.rows[ir].cells[1].childNodes[0].value=ak.tagesbericht[i].themen;
      t.rows[ir].cells[2].childNodes[0].value=ak.tagesbericht[i].kguil;
      t.rows[ir].cells[3].childNodes[0].value=ak.tagesbericht[i].bemerkungen;
    }
  }
}
</script>
Tagesbericht von <select id="alterkurs">
<?php
foreach($altekurse as $ak) {
?>
  <option value="<?= $ak->id ?>"><?= $ak->titel ?></option>
<?php
}
?>
</select> <button type="button" onclick="tagesbericht_uebernehmen()">übernehmen</button>
<form action="tagesbericht_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <input type="hidden" name="kursid" value="<?= $kurs->id ?>" />
<table id="tagesbericht" border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Tag</th>
    <th>Themen</th>
    <th>KGU und IL</th>
    <th>Bemerkungen</th>
  </tr>
<?php
for($wann=$kurs->von;$wann<=$kurs->bis;$wann=strtotime('+1 day',$wann)) {
  $tagname=$tagnamen[date('D',$wann)];
  if($tagname=='Sa' || $tagname=='So') continue;
  $tag=date('Y-m-d',$wann);
  $ferien=isset($kurs->ferienByTag[$tag]) ? $kurs->ferienByTag[$tag] : null;
  $bericht=isset($berichtByTag[$tag]) ? $berichtByTag[$tag] : $leererBericht;
?>
  <tr>
    <th>
      <?= $tagname ?><br />
      <?= date('d.m.Y',$wann) ?>
    </th>
<?php
  if($ferien) {
?>
    <td colspan="3" align="center" class="ferien"><?= $ferien->anlass ?></td>
<?php
  } else {
?>
    <td><textarea name="themen_<?= $tag ?>"><?= $bericht->themen ?></textarea></td>
    <td><textarea name="kguil_<?= $tag ?>"><?= $bericht->kguil ?></textarea></td>
    <td><textarea name="bemerkungen_<?= $tag ?>"><?= $bericht->bemerkungen ?></textarea></td>
<?php
  }
?>
  </tr>
<?php
}
?>
  <tr>
    <th></th>
    <td colspan="3">
      <input type="checkbox" name="ok" value="J" <?= $kurs->tagesbericht_ok ? 'checked' : '' ?> /> Bericht vollständig
      <input type="submit" value="Speichern" />
    </td>
  </tr>
</table>
</form>
<?php
} else { //Dozent nicht im Kurs angemeldet: nur anzeigen, kein bearbeiten
?>
<table id="tagesbericht" border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Tag</th>
    <th>Themen</th>
    <th>KGU und IL</th>
    <th>Bemerkungen</th>
  </tr>
<?php
for($wann=$kurs->von;$wann<=$kurs->bis;$wann=strtotime('+1 day',$wann)) {
  $tagname=$tagnamen[date('D',$wann)];
  if($tagname=='Sa' || $tagname=='So') continue;
  $tag=date('Y-m-d',$wann);
  $bericht=isset($berichtByTag[$tag]) ? $berichtByTag[$tag] : $leererBericht;
?>
  <tr>
    <th>
      <?= $tagname ?><br />
      <?= date('d.m.Y',$wann) ?>
    </th>
    <td><?= nl2br($bericht->themen) ?></td>
    <td><?= nl2br($bericht->kguil) ?></td>
    <td><?= nl2br($bericht->bemerkungen) ?></td>
  </tr>
<?php
}
?>
  <tr>
    <th></th>
    <td colspan="3" class="<?= $kurs->tagesbericht_ok ? 'ok' : 'todo' ?>">
      <?= $kurs->tagesbericht_ok ? 'Bericht vollständig' : 'Bericht noch nicht vollständig' ?>
    </td>
  </tr>
</table>
<?php
}
?>
<br />
<a href="kurse.php">Zurück zur Suche</a>
<?php
$seite->endeGenerieren();
exit;
?>