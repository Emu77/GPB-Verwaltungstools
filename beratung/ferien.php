<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'BeratungFerien.php';

$suche=isset($_SESSION['ferien_suche']) ? $_SESSION['ferien_suche'] : new Suche('von','bis','anlass','art','ort','klassebez');
if(empty($suche->where)) {
  $beginn=strtotime('last month');
  $suche->von=date('Y-m-01',$beginn);
  $suche->bis=date('Y-m-d',strtotime('-1 day',strtotime('+1 year',strtotime($suche->von))));
  $suche->addKriterium("beginn<=?",$suche->bis,'s');
  $suche->addKriterium("ende>=?",$suche->von,'s');
  if(!empty($ich->ort)) {
    $suche->ort=$ich->ort;
    $suche->addKriterium('(length(ort)=0 or ort=?)',$suche->ort,'s');
  }
}

$ferien=new Liste('BeratungFerien',$suche->prepare("select * from gpb_ferien where","order by beginn,ende,art"));
Ferien::refsLaden($ferien);

require_once 'BeratungSeite.php';
$seite=BeratungSeite::$menueByUrl['ferien.php'];
$seite->anfangGenerieren();
?>
<a href="ferien_bearbeiten.php?ferienid=0">Neue Ferien</a><br />
<br />
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
Ferien::makeHeaderTr();
?>
  <form action="ferien_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <tr>
<?php
$suche->makeVonBisInput(3);
$suche->makeStringInput('anlass');
$suche->makeEnumInput('art',array('Feiertag','GPB Ferien','Institutsferien','Klassenferien','Berliner Schulferien'));
$suche->makeEnumInput('ort',array('-','Mitte','Neukölln'));
$suche->makeStringInput('klassebez');
?>
    <td>
<?php
$suche->makeSubmit(false);
?>
    </td>
  </tr>
  </form>
<?php
foreach($ferien->alle as $fer) {
  $fer->makeTr();
}
?>
</table>
<?php
$seite->endeGenerieren();
?>