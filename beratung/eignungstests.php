<?php
require_once 'check_login.php';

$interessentsuche=isset($_POST['interessentsuche']) ? $_POST['interessentsuche'] : false;
$gefunden=array();
if(!empty($interessentsuche)) {
  $suche='%'.$interessentsuche.'%';
  $stmt=$db->prepare("select id,mitisid,vorname,nachname from gpb_basket where concat(vorname,' ',nachname) like ? or mitisid=? order by vorname,nachname limit 10");
  $stmt->bind_param('ss',$suche,$interessentsuche);
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $row->nutzer=null;
    $gefunden[]=$row;
  }
  $result->free();
}

$interessent=isset($_SESSION['eignung_interessent']) ? $_SESSION['eignung_interessent'] : null;

$daten=(object)array(
  'category'=>'Beratung'
);
$url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=tests_finden&daten='.urlencode(json_encode($daten));
$ergebnis=json_decode(file_get_contents($url));
if(is_object($ergebnis) && isset($ergebnis->exception)) {
  $kursladenfehler='<div class="fehler">'.json_encode($ergebnis).'</div>';
  $ergebnis=null;
} else if(!is_object($ergebnis)) {
  $ergebnis=json_decode($ergebnis);
}
$coursesByMid=empty($ergebnis) ? array() : (array)$ergebnis;
$courses=array();

$quizzes=array();
$quizzesByCmid=array();
foreach($coursesByMid as $mid=>$course) {
  $course->anmeldungen=array();
  $courses[]=$course;
  foreach($course->quizzes as $quiz) {
    $quiz->anmeldungen=array();
    $quiz->abgaben=(array)$quiz->abgaben;
    $quizzes[]=$quiz;
    $quizzesByCmid[$quiz->cmid]=$quiz;
  }
}

function coursesVergleichen($c0,$c1) {
  return strcmp(mb_strtolower($c0->shortname),mb_strtolower($c1->shortname));
}
usort($courses,'coursesVergleichen');

$termine=array();
$termineById=array();
$result=$db->query("select * from gpb_eignungstest_termin ");
while($row=$result->fetch_object()) {
  $row->timestamp=strtotime($row->wann);
  $row->termin=date('d.m.Y H:i',$row->timestamp);
  $row->anmeldungen=array();
  $termine[]=$row;
  $termineById[$row->id]=$row;
}
$result->free();
$leererTermin=(object)array(
  'wann'=>'0000-00-00',
  'timestamp'=>0,
  'termin'=>'',
  'ort'=>'',
  'anmeldungen'=>array()
);

$nutzer=array();
$nutzerById=array();
$nutzerByMid=array();
$anzahlFreieNutzer=0;
$result=$db->query("select * from gpb_eignungstest_nutzer order by moodleid");
while($row=$result->fetch_object()) {
  $row->anmeldungen=array();
  $nutzer[]=$row;
  $nutzerById[$row->id]=$row;
  $nutzerByMid[$row->moodleid]=$row;
  ++$anzahlFreieNutzer;
}
$result->free();

$verfallen=array();
$result=$db->query("select a.id 
  from gpb_eignungstest_anmeldung a 
  left outer join gpb_eignungstest_termin t on a.terminid=t.id 
  where (case when t.wann is null then a.erstelltam else t.wann end)<date_sub(current_date,interval 3 month)
  limit 3");
while($row=$result->fetch_object()) {
  $verfallen[]=$row->id;
}
$result->free();
if(!empty($verfallen)) {
  require_once 'eignungstests_funktionen.php';
  foreach($verfallen as $anmeldungid) {
    eignungstestAnmeldungLoeschen($anmeldungid);
  }
}


$anmeldungen=array();
$verloreneAnmeldungen=array();
$result=$db->query("select a.* from gpb_eignungstest_anmeldung a order by a.terminid");
while($row=$result->fetch_object()) {
  $n=$nutzerById[$row->nutzerid];
  $row->nutzer=$n;
  $anmeldungen[]=$row;
  if(empty($n->anmeldungen)) {
    --$anzahlFreieNutzer;
  }
  $n->anmeldungen[]=$row;
  if($row->testcmid>0 && isset($quizzesByCmid[$row->testcmid])) {
    $quizzesByCmid[$row->testcmid]->anmeldungen[]=$row;
  } else if($row->coursemoodleid>0 && isset($coursesByMid[$row->coursemoodleid])) {
    $coursesByMid[$row->coursemoodleid]->anmeldungen[]=$row;
  } else {
    $verloreneAnmeldungen[]=$row;
  }
  if($row->terminid>0 && isset($termineById[$row->terminid])) {
    $termineById[$row->terminid]->anmeldungen[]=$row;
  }
}
$result->free();
function anmeldungenVergleichen($an0,$an1) {
  $res=strcmp(mb_strtolower($an0->nutzer->nachname),mb_strtolower($an1->nutzer->nachname));
  if($res!=0) return $res;
  $res=strcmp(mb_strtolower($an0->nutzer->vorname),mb_strtolower($an1->nutzer->vorname));
  if($res!=0) return $res;
  if($an0->terminid<$an1->terminid) return -1;
  if($an0->terminid>$an1->terminid) return 1;
  return 0;
}

require_once 'BeratungSeite.php';
$seite=BeratungSeite::$menueByUrl['eignungstests.php'];
$seite->anfangGenerieren();
?>
<link rel="stylesheet" href="eignungstests_styles.css" />
<?php
if(isset($_SESSION['nutzerabmeldennachricht'])) {
  echo $_SESSION['nutzerabmeldennachricht'];
  unset($_SESSION['nutzerabmeldennachricht']);
}
?>
<div id="rumpf">
<div class="linke spalte">
  <div id="interessent_div">
    <b>Interessent</b>
    <form action="eignungstests.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
      <input type="text" name="interessentsuche" value="<?= htmlspecialchars($interessentsuche,ENT_QUOTES) ?>" style="width:200px;" /><input type="submit" value="Suchen" /><br />
      (% für "beliebige Buchstaben")
    </form>
<?php
foreach($gefunden as $gef) {
?>
    <a href="eignungstests_interessent_merken.php?mitisid=<?= $gef->mitisid ?>"><?= $gef->vorname ?> <?= $gef->nachname ?></a> <button onclick="location.href='eignungstests_interessent_merken.php?mitisid=<?= $gef->mitisid ?>';">Auswählen</button><br />
<?php
}
?>
    <br />
<?php
if($interessent) {
?>
    <b><?= $interessent->anrede ?> <?= $interessent->vorname ?> <?= $interessent->nachname ?></b>
<?php
}
?>
  </div> <!-- interessent_div -->
  
  <div id="anmeldung_div">
<?php
if(isset($_SESSION['nutzeranmeldennachricht'])) {
  echo $_SESSION['nutzeranmeldennachricht'];
  unset($_SESSION['nutzeranmeldennachricht']);
}
?>
    <b>Anmeldung</b>
    <div style="<?= $anzahlFreieNutzer>=2 ? '' : 'background-color:'.($anzahlFreieNutzer<=0 ? 'red' : 'orange').';color:white;' ?>">Freie Dummy-Nutzer: <?= $anzahlFreieNutzer ?></div>
<?php
if($anzahlFreieNutzer>0) {
?>
    <form action="eignungstests_anmeldung_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <table border="1" cellspacing="0" style="border-collapse:collapse;">
      <tr>
        <th>Vorname</th>
        <td><input type="text" name="vorname" value="<?= empty($interessent) ? '' : htmlentities($interessent->vorname,ENT_COMPAT) ?>" style="width:150px;" /></td>
      </tr>
      <tr>
        <th>Nachname</th>
        <td><input type="text" name="nachname" value="<?= empty($interessent) ? '' : htmlentities($interessent->nachname,ENT_COMPAT) ?>" style="width:150px;" /></td>
      </tr>
      <tr>
        <th>Kurs</th>
        <td><select name="coursemoodleid" class="courseselect">
          <option value="0">-</option>
<?php
  foreach($courses as $course) {
?>
          <option value="<?= $course->id ?>"><?= $course->shortname ?></option>
<?php
  }
?>
        </select></td>
      </tr>
      <tr>
        <th>Test</th>
        <td><select name="testcmid" class="quizselect">
          <option value="0">-</option>
<?php
  foreach($quizzes as $quiz) {
?>
          <option value="<?= $quiz->cmid ?>"><?= $quiz->titel ?></option>
<?php
  }
?>
        </select></td>
      </tr>
      <tr>
        <th>Termin</th>
        <td><select name="terminid" class="terminselect">
          <option value="">-</option>
<?php
  $heute=date('Y-m-d');
  foreach($termine as $termin) {
    if($termin->wann<$heute) continue;
?>
          <option value="<?= $termin->id ?>"><?= $termin->termin ?></option>
<?php
  }
?>
        </select></td>
      </tr>
      <tr>
        <th></th>
        <td><input type="submit" value="Anmelden" /></td>
      </tr>
    </table>
    </form>
<?php
}
?>
  </div><!-- anmeldung_div -->
</div> <!-- linke spalte -->

<div class="mittlere spalte">
  <div id="termine_div">
    <b>Termine</b>
<?php
if(isset($_SESSION['terminloeschennachricht'])) {
  echo $_SESSION['terminloeschennachricht'];
  unset($_SESSION['terminloeschennachricht']);
}
?>
    <form action="eignungstests_termin_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
      Datum:&nbsp;<input type="date" name="datum" value="" />
      Uhrzeit:&nbsp;<input type="time" name="uhrzeit" value="14:45" />
      Ort:&nbsp;<select name="ort">
        <option value="Neukölln" <?= $ich->ort=='Neukölln' ? 'selected' : '' ?>>Neukölln</option>
        <option value="Mitte" <?= $ich->ort=='Mitte' ? 'selected' : '' ?>>Mitte</option>
        <option value="Online">Online</option>
      </select>
      <input type="submit" value="Termin hinzufügen" />
    </form>
    <table border="1" cellspacing="0" style="border-collapse:collapse;margin-top:1em;">
      <tr>
        <th>&nbsp; &nbsp; &nbsp;</th>
        <th>Vorname</th>
        <th>Nachname</th>
        <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
        <th>Nutzername</th>
        <th>Passwort</th>
        <th colspan="2">Test</th>
        <th>PDF</th>
        <th>Abgabe</th>
        <th></th>
      </tr>
<?php
foreach($termine as $termin) {
?>
      <tr>
        <td colspan="10">
          <?= $termin->termin ?> <?= $termin->ort ?>
          &nbsp;
          <button type="button" onclick="location.href='eignungstests_termin_loeschen.php?terminid=<?= $termin->id ?>';">Termin löschen</button>
<?php
  if(!empty($termin->anmeldungen)) {
?>
          &nbsp;
          <a href="eignungstests_pdf_generieren.php?terminid=<?= $termin->id ?>">PDF für alle</a>
<?php
  }
?>
        </td>
      </tr>
<?php
  usort($termin->anmeldungen,'anmeldungenVergleichen');
  foreach($termin->anmeldungen as $an) {
    $n=$an->nutzer;
    $quiz=$quizzesByCmid[$an->testcmid];
?>
      <tr>
        <td></td>
        <td><?= $n->vorname ?></td>
        <td><?= $n->nachname ?></td>
        <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" align="center"><a href="<?= $moodleurl ?>user/profile.php?id=<?= $n->moodleid ?>" target="moodle"><?= $n->moodleid ?></a></td>
        <td align="center"><?= $n->nutzername ?></td>
        <td align="center"><?= $n->passwort ?></td>
        <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" align="center"><a href="<?= $quiz->url ?>" target="moodle"><?= $quiz->cmid ?></a></td>
        <td><?= $quiz->titel ?></td>
        <td align="center"><a href="eignungstests_pdf_generieren.php?anmeldungid=<?= $an->id ?>">PDF</a></td>
        <td align="center">
<?php
    if(isset($quiz->abgaben[$n->moodleid])) {
?>
            <a href="<?= $moodleurl ?>mod/quiz/review.php?attempt=<?= $quiz->abgaben[$n->moodleid] ?>" target="moodle" class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Bewerten</a>
            <a href="eignungstest_ergebnis.php?attempt=<?= $quiz->abgaben[$n->moodleid] ?>">Ergebnis-PDF</a>
<?php
    }
?>        
        </td>
        <td><button type="button" onclick="location.href='eignungstests_anmeldung_loeschen.php?anmeldungid=<?= $an->id ?>';">Abmelden</button></td>
      </tr>
<?php
  }
}
?>
    </table>
  </div><!-- termine_div -->
  <div id="tests_div">
    <b>Tests</b>
    <table border="1" cellspacing="0" style="border-collapse:collapse;">
      <tr>
        <th>&nbsp; &nbsp; &nbsp;</th>
        <th>Vorname</th>
        <th>Nachname</th>
        <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
        <th>Nutzername</th>
        <th>Passwort</th>
        <th>Termin</th>
        <th>PDF</th>
        <th>Abgabe</th>
        <th></th>
      </tr>
<?php
foreach($quizzes as $quiz) {
?>
      <tr>
        <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" align="center"><a href="<?= $quiz->url ?>" target="moodle"><?= $quiz->cmid ?></a></td>
        <td colspan="9"><?= $quiz->titel ?></td>
      </tr>
<?php
    usort($quiz->anmeldungen,'anmeldungenVergleichen');
    foreach($quiz->anmeldungen as $an) {
      $n=$an->nutzer;
      $termin=$an->terminid>0 ? $termineById[$an->terminid] : $leererTermin;
?>
      <tr>
        <td></td>
        <td><?= $n->vorname ?></td>
        <td><?= $n->nachname ?></td>
        <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" align="center"><a href="<?= $moodleurl ?>user/profile.php?id=<?= $n->moodleid ?>" target="moodle"><?= $n->moodleid ?></a></td>
        <td align="center"><?= $n->nutzername ?></td>
        <td align="center"><?= $n->passwort ?></td>
        <td align="center"><?= $termin->termin ?> <?= $termin->ort ?></td>
        <td align="center"><a href="eignungstests_pdf_generieren.php?anmeldungid=<?= $an->id ?>">PDF</a></td>
        <td align="center">
<?php
      if(isset($quiz->abgaben[$n->moodleid])) {
?>
            <a href="<?= $moodleurl ?>mod/quiz/review.php?attempt=<?= $quiz->abgaben[$n->moodleid] ?>" target="moodle" class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Bewerten</a>
            <a href="eignungstest_ergebnis.php?attempt=<?= $quiz->abgaben[$n->moodleid] ?>">Ergebnis-PDF</a>
<?php
      }
?>        
        </td>
        <td><button type="button" onclick="location.href='eignungstests_anmeldung_loeschen.php?anmeldungid=<?= $an->id ?>';">Abmelden</button></td>
      </tr>
<?php
  }
}
?>
    </table>
  </div><!-- tests_div -->
</div><!-- mittlere spalte -->

<div class="rechte spalte">
  <div id="kurse_div">
    <b>Kurse</b>
<?php
if(isset($kursladenfehler)) {
  echo $kursladenfehler;
}
if(isset($_SESSION['kursspeichernnachricht'])) {
  echo $_SESSION['kursspeichernnachricht'];
  unset($_SESSION['kursspeichernnachricht']);
}
?>
    <table border="1" cellspacing="0" style="border-collapse:collapse;">
      <tr>
        <th>&nbsp; &nbsp; &nbsp;</th>
        <th>Vorname</th>
        <th>Nachname</th>
        <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
        <th>Nutzername</th>
        <th>Passwort</th>
        <th>PDF</th>
        <th></th>
      </tr>
<?php
foreach($courses as $course) {
?>
      <tr>
        <td colspan="8">
          <b><a href="<?= $moodleurl ?>course/view.php?id=<?= $course->id ?>" target="moodle"><?= $course->shortname ?></a></b>
          &nbsp;
          <button type="button" onclick="location.href='eignungstest_kurs_berater_anmelden.php?coursemoodleid=<?= $course->id ?>';">Alle Berater anmelden</button>
          &nbsp;
          <button type="button" onclick="location.href='eignungstest_kurs_interessenten_anmelden.php?coursemoodleid=<?= $course->id ?>';">Alle Dummy-Nutzer anmelden (Eignungstest-Kurs)</button>
        </td>
      </tr>
<?php
  usort($course->anmeldungen,'anmeldungenVergleichen');
  foreach($course->anmeldungen as $an) {
    $n=$an->nutzer;
?>
      <tr>
        <td></td>
        <td><?= $n->vorname ?></td>
        <td><?= $n->nachname ?></td>
        <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" align="center"><a href="<?= $moodleurl ?>user/profile.php?id=<?= $n->moodleid ?>" target="moodle"><?= $n->moodleid ?></a></td>
        <td align="center"><?= $n->nutzername ?></td>
        <td align="center"><?= $n->passwort ?></td>
        <td align="center"><a href="eignungstests_pdf_generieren.php?anmeldungid=<?= $an->id ?>">PDF</a></td>
        <td><button type="button" onclick="location.href='eignungstests_anmeldung_loeschen.php?anmeldungid=<?= $an->id ?>';">Abmelden</button></td>
      </tr>
<?php
  }
}
?>
    </table>
  </div><!-- kurse_div -->
</div><!-- rechte spalte -->
</div><!-- rumpf -->

<div id="fuss">
  <div id="nutzer_div">
    <b>Interessenten</b>
    <table border="1" cellspacing="0" style="border-collapse:collapse;">
      <tr>
        <th>Vorname</th>
        <th>Nachname</th>
        <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
        <th>Nutzername</th>
        <th>Passwort</th>
        <th colspan="2">Kurs/Test</th>
        <th>Termin</th>
        <th>PDF</th>
        <th>Abgabe</th>
        <th></th>
      </tr>
<?php
usort($anmeldungen,'anmeldungenVergleichen');
foreach($anmeldungen as $an) {
  $n=$an->nutzer;
  $course=$an->coursemoodleid>0 && isset($coursesByMid[$an->coursemoodleid]) ? $coursesByMid[$an->coursemoodleid] : null;
  $quiz=$an->testcmid>0 && isset($quizzesByCmid[$an->testcmid]) ? $quizzesByCmid[$an->testcmid] : null;
  $termin=$an->terminid>0 ? $termineById[$an->terminid] : $leererTermin;
  if(!empty($course)) {
?>
      <tr>
        <td><?= $n->vorname ?></td>
        <td><?= $n->nachname ?></td>
        <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" align="center"><a href="<?= $moodleurl ?>user/profile.php?id=<?= $n->moodleid ?>" target="moodle"><?= $n->moodleid ?></a></td>
        <td align="center"><?= $n->nutzername ?></td>
        <td align="center"><?= $n->passwort ?></td>
        <td colspan="2"><a href="<?= $moodleurl ?>course/view.php?id=<?= $course->id ?>" target="moodle"><?= $course->shortname ?></a></td>
        <td align="center"><?= $termin->termin ?> <?= $termin->ort ?></td>
        <td align="center"><a href="eignungstests_pdf_generieren.php?anmeldungid=<?= $an->id ?>">PDF</a></td>
        <td align="center"></td>
        <td><button type="button" onclick="location.href='eignungstests_anmeldung_loeschen.php?anmeldungid=<?= $an->id ?>';">Abmelden</button></td>
      </tr>
<?php    
  }
  if(!empty($quiz)) {
?>
      <tr>
        <td><?= $n->vorname ?></td>
        <td><?= $n->nachname ?></td>
        <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" align="center"><a href="<?= $moodleurl ?>user/profile.php?id=<?= $n->moodleid ?>" target="moodle"><?= $n->moodleid ?></a></td>
        <td align="center"><?= $n->nutzername ?></td>
        <td align="center"><?= $n->passwort ?></td>
        <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" align="center"><a href="<?= $quiz->url ?>" target="moodle"><?= $quiz->cmid ?></a></td>
        <td><?= $quiz->titel ?></td>
        <td align="center"><?= $termin->termin ?> <?= $termin->ort ?></td>
        <td align="center"><a href="eignungstests_pdf_generieren.php?anmeldungid=<?= $an->id ?>">PDF</a></td>
        <td align="center">
<?php
    if(isset($quiz->abgaben[$n->moodleid])) {
?>
            <a href="<?= $moodleurl ?>mod/quiz/review.php?attempt=<?= $quiz->abgaben[$n->moodleid] ?>" target="moodle" class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Bewerten</a>
            <a href="eignungstest_ergebnis.php?attempt=<?= $quiz->abgaben[$n->moodleid] ?>">Ergebnis-PDF</a>
<?php
    }
?>        
        </td>
        <td><button type="button" onclick="location.href='eignungstests_anmeldung_loeschen.php?anmeldungid=<?= $an->id ?>';">Abmelden</button></td>
      </tr>
<?php
  }
}
?>
    </table>
  </div><!-- nutzer_div -->
</div> <!-- fuss -->
<?php
$seite->endeGenerieren();
?>