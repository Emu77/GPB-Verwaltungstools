<?php
require_once '../Suche.php';
@session_start();
require_once 'check_login.php';
require_once 'VerwaltungKurs.php';

$kurs=Kurs::einenLaden(isset($_GET['kursid']) ? (int)$_GET['kursid'] : 0,'VerwaltungKurs');
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

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=new VerwaltungSeite('Kurs-Tagesbericht');
$seite->anfangGenerieren('');
$kurs->makeSehen();
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
    <td colspan="3" align="center" class="ferien"><a href="ferien_sehen.php?ferienid=<?= $ferien->id ?>"><?= $ferien->anlass ?></a></td>
<?php
  } else {
?>
    <td><?= nl2br($bericht->themen) ?></td>
    <td><?= nl2br($bericht->kguil) ?></td>
    <td><?= nl2br($bericht->bemerkungen) ?></td>
<?php
  }
?>
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
$seite->endeGenerieren();
exit;
?>