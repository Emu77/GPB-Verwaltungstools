<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once 'VerwaltungDozent.php';

$suche=isset($_SESSION['dozenten_suche']) ? $_SESSION['dozenten_suche'] : new Suche('dozentname','nutzername');

$dozenten=array();
if(!empty($suche->where)) {
  $stmt=$suche->prepare("select * from (select *,concat(vorname,concat(' ',nachname)) as dozentname
    from gpb_dozent) doz
    where "," order by nachname,vorname");
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object('VerwaltungDozent')) {
    $dozenten[]=$row;
  }
  $result->free();  
  if($ich->istleiter && !empty($dozenten)) {
    $dozentenById=array();
    foreach($dozenten as $dozent) {
      $dozent->anzahlkurse=0; //wird nachher korrigiert
      $dozent->inMitte=false; //wird nachher korrigiert
      $dozent->inNeukoelln=false; //wird nachher korrigiert
      $dozentenById[$dozent->id]=$dozent;
    }

    $result=$db->query("select kd.dozentid
  ,count(*) as anzahlkurse
  ,sum(case when r.ort='Mitte' then 1 else 0 end) as inMitte
  ,sum(case when r.ort='Neukölln' then 1 else 0 end) as inNeukoelln
      from gpb_kurs_dozent kd
      join gpb_kurs k on k.id=kd.kursid
      left outer join gpb_raum r on r.id=k.raumid
      where kd.dozentid in(".implode(',',array_keys($dozentenById)).")
        and k.ende>=date_sub(current_date,interval 1 year)
      group by kd.dozentid");
    while($row=$result->fetch_object()) {
      $dozentenById[$row->dozentid]->anzahlkurse=$row->anzahlkurse;
      $dozentenById[$row->dozentid]->inMitte=$row->inMitte;
      $dozentenById[$row->dozentid]->inNeukoelln=$row->inNeukoelln;
    }
    $result->free();
  }
}

require_once $ich->istleiter ? '../leitung/LeitungSeite.php' : 'VerwaltungSeite.php';
$seite=VerwaltungSeite::$menueByUrl['../verwaltung/dozenten.php'];
$seite->anfangGenerieren();
?>
<a href="dozent_bearbeiten.php?dozentid=0">Neuer Dozent</a><br />
<br />
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-top:1em;">
  <tr>
    <th>Anrede</th>
    <th>Vorname</th>
    <th>Nachname</th>
    <th>Nutzername</th>
    <th>Email</th>
<?php
if($ich->istleiter) {
?>
    <th>inTrain</th>
    <th>i.d.R. verfügbar</th>
    <th>Einsätze in 12 Mo</th>
    <th>Einsatzort</th>
<?php
}
?>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th></th>
  </tr>
  <form action="dozenten_suchen.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
  <tr>
<?php
$suche->makeStringInput('dozentname',3);
$suche->makeStringInput('nutzername');
?>
    <td></td>
<?php
if($ich->istleiter) {
?>
    <td><?php  $suche->makeBooleanInput('istintrain','inTrain'); ?></td>
    <td><select name="nurverfuegbare">
      <option value="">(alle)</option>
      <option value="Ja" <?= isset($suche->nurverfuegbare) && $suche->nurverfuegbare=='Ja' ? 'selected' : '' ?>>ja</option>
      <option value="Nein" <?= isset($suche->nurverfuegbare) && $suche->nurverfuegbare=='Nein' ? 'selected' : '' ?>>nein</option>
    </select></td>
    <td><?php $suche->makeBooleanInput('iststamm','>= 10'); ?></td>
    <td><select name="ort">
      <option value="">(alle)</option>
      <option value="Mitte" <?= isset($suche->ort) && $suche->ort=='Mitte' ? 'selected' : '' ?>>Mitte</option>
      <option value="Neukölln" <?= isset($suche->ort) && $suche->ort=='Neukölln' ? 'selected' : '' ?>>Neukölln</option>
    </select></td>
<?php
}
?>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>"></td>
<?php
$suche->makeSubmit();
?>
  </tr>
  </form>
<?php
foreach($dozenten as $row) {
?>
  <tr>
    <td><?= $row->anrede ?></td>
    <td><?= $row->vorname ?></td>
    <td><?= $row->nachname ?></td>
    <td><?= $row->nutzername ?></td>
    <td><a href="mailto:<?= htmlentities($row->email,ENT_COMPAT) ?>"><?= $row->email ?></a></td>
<?php
  if($ich->istleiter) {
?>
    <td align="center"><?= $row->istintrain ? 'ja' : '' ?></td>
    <td align="center"><?= $row->idrverfuegbar ? 'ja' : 'nein' ?></td>
    <td align="center"><?= $row->anzahlkurse ?></td>
    <td align="center"><?= $row->inMitte ? 'Mitte: '.$row->inMitte : '' ?> <?= $row->inNeukoelln ? 'Neukölln: '.$row->inNeukoelln : '' ?></td>
<?php
  }
  $row->makeMoodleTd();
  $row->makeBearbeitenTd();
?>
  </tr>
<?php
}
?>
</table>
<?php
$seite->endeGenerieren();
?>