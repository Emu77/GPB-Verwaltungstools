<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'DozentKurs.php';
require_once 'DozentPVTermin.php';

$suche=isset($_SESSION['kurse_suche']) ? $_SESSION['kurse_suche'] : new Suche('von','bis','titel','ort','raum','klassebez','dozentname','nurich','moodleid','todo');
if(empty($suche->where)) {
  $suche->nurich=true;
  $suche->addKriterium("id in(select kursid from gpb_kurs_dozent where dozentid=?)",$ich->id,'i');
  $suche->von=date('Y-m-d',strtotime('6 months ago'));
  $suche->addKriterium('ende>=?',$suche->von,'s');
  $suche->todo=true;
  $suche->addKriterium("(kurzbericht_ok=0 or tagesbericht_ok=0 or notenstatus='todo')",false,false);
}
//FIXME todo als OR anstatt AND

$kurse=new Liste('DozentKurs',$suche->prepare("select * from gpb_kurs_view where (moodleid>0 or sichtbar) and","order by ende desc,beginn desc limit 25"));
Kurs::anzahlTNLaden($kurse);
Kurs::refsLaden($kurse);

$ihkantragsformulare=new Liste('DozentKurs',$db->prepare("select * from gpb_kurs_view
  where hatProjektantrag and ende>=current_date() and id in(select kursid from gpb_kurs_dozent where dozentid=".$ich->id.")
  order by beginn desc,ende desc"));
Kurs::refsLaden($ihkantragsformulare);

$pvtermine=new Liste('DozentPVTermin',$db->prepare("select t.* 
  from gpb_pruefungsvorbereitung_termin t
  join gpb_kurs_view k on k.id=t.kursid and k.id in(select kursid from gpb_kurs_dozent where dozentid=".$ich->id.") and (k.moodleid>0 or k.sichtbar)
  where t.beginn is not null and t.beginn>=current_date()
  order by t.beginn,t.beginn_uhrzeit,t.ende,t.ende_uhrzeit"));
DozentPVTermin::refsLaden($pvtermine);

require_once 'DozentSeite.php';
$seite=Seite::$menueByUrl['kurse.php'];
$seite->anfangGenerieren('');
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;">
<?php
DozentKurs::makeHeaderTr(false);
?>
  <form action="kurse_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <tr>
<?php
$suche->makeVonBisInput(3);
$suche->makeStringInput('titel');
?>
    <td><select name="ort">
      <option value="">Alle</option>
      <option value="-" <?= $suche->ort=='-' ? 'selected' : '' ?>>-</option>
      <option value="Mitte" <?= $suche->ort=='Mitte' ? 'selected' : '' ?>>Mitte</option>
      <option value="Neukölln" <?= $suche->ort=='Neukölln' ? 'selected' : '' ?>>Neukölln</option>
    </select></td>
<?php
$suche->makeStringInput('raum');
$suche->makeStringInput('klassebez');
?>
    <td>
      <input type="checkbox" name="nurich" value="J" <?= $suche->nurich ? 'checked' : '' ?> /> Nur meine Kurse<br />
      <input type="text" name="dozentname" value="<?= htmlentities($suche->dozentname,ENT_COMPAT) ?>" style="width:150px;" />
    </td>
<?php
$suche->makeStringInput('moodleid',1,100,$moodleisttest ? 'testmoodle' : 'moodle');
?>
    <td>
      <div class="todo"><input type="checkbox" name="todo" value="J" <?= $suche->todo ? 'checked' : '' ?> /> unvollständig</div>
      <input type="submit" value="Suchen" />
    </td>
  </tr>
  </form>
<?php
foreach($kurse->alle as $kurs) {
  $kurs->makeTr(false);
}
?>
</table>
<?php
if(!empty($ihkantragsformulare->alle)) {
?>
<h2>IHK-Projektantrag</h2>
<table border="1" cellspacing="0" class="sehen" style="border-collapse:collapse;margin-bottom:1em;">
<?php
  foreach($ihkantragsformulare->alle as $kurs) {
?>
  <tr>
    <td><?= $kurs->titel ?></td>
<?php
    $kurs->makeKlassenTd();
    $kurs->makeAnzahlTNTd(true);
    $kurs->makeBearbeitenTD();
?>
  </tr>
<?php
  }
}
?>
</table>
<?php
if(!empty($pvtermine->alle)) {
?>
<h2>PV-Termine</h2>
<table border="1" cellspacing="0" class="sehen" style="border-collapse:collapse;margin-bottom:1em;">
<?php
DozentPVTermin::makeHeaderTr();
foreach($pvtermine->alle as $termin) {
  $termin->makeTr();
}
?>
</table>
<?php
}
$seite->endeGenerieren();
exit;
?>