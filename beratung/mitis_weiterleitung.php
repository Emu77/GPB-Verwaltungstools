<?php
require_once 'check_login.php';

$mitisid=isset($_GET['mitisid']) ? (int)$_GET['mitisid'] : 0;
$interessenbereich=isset($_GET['interessenbereich']) ? (int)$_GET['interessenbereich'] : '';

if($mitisid>0){
  $result=$db->query("select vorname,nachname,interessenbereich from gpb_basket where mitisid=".$mitisid." limit 1");
  $basket=$result->fetch_object();
  $result->free();
} else {
  $basket=false;
}

require_once 'BeratungSeite.php';
$seite=new BeratungSeite('Interessent aus MITIS übernommen');
$seite->anfangGenerieren();

if(!empty($basket)) {
  if(empty($interessenbereich)) {
    $interessenbereich=$basket->interessenbereich;
  }
?>
<b>Interessent</b><br />
<?= $basket->vorname ?> <?= $basket->nachname ?><br />
<br />
<?php
}
?>
<b>Weiter zu</b><br />
<br />
<a href="eignungstests_interessent_merken.php?mitisid=<?= $mitisid ?>" style="<?= $interessenbereich=='eignung' ? 'font-weight:bold;' : '' ?>">Eignungstests</a><br />
<br />
<a href="intrain_interessent_merken.php?redirect=J&mitisid=<?= $mitisid ?>" style="<?= $interessenbereich=='intrain' ? 'font-weight:bold;' : '' ?>">inTrain</a><br />
<br />
<a href="spez_interessent_merken.php?mitisid=<?= $mitisid ?>" style="<?= $interessenbereich=='spez' ? 'font-weight:bold;' : '' ?>">Spezialisten</a><br />
<?php
$seite->endeGenerieren();
?>