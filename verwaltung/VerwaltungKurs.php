<?php
require_once '../Kurs.php';
class VerwaltungKurs extends Kurs {
  
  function pvLaden() {
    global $db;
    if(!$this->istPV) return;
    $this->klassenById=array();
    foreach($this->klassen as $klasse) {
      $this->klassenById[$klasse->id]=$klasse;
    }
    $result=$db->query("select * from gpb_pruefungsvorbereitung_klasse where kursid=".$this->id);
    while($row=$result->fetch_object()) {
      $this->klassenById[$row->klasseid]->pv=$row;
    }
    $result->free();
  }
  
  function makeTitelTd() {
?>
    <td style="max-width:400px;"><?= $this->titel."<br />".($this->modulid>0 ? $this->modulkuerzel.' - '.$this->modultitel : '(Kein Modul)') ?></td>
<?php
  }
  function makeLocationTds() {
?>
    <td><?= $this->ort ?></td>
    <td><a href="raum_sehen.php?raumid=<?= $this->raumid ?>"><?= $this->raum ?> <?= $this->anzahlPlaetze<0 ? '' : '('.$this->anzahlPlaetze.' Plätze)' ?></a></td>
<?php
  }
  function makeKlassenTd() {
?>
    <td class="klassen">
<?php
    foreach($this->klassen as $k) {
?>
      <a href="klasse_sehen.php?klasseid=<?= $k->id ?>" style="display:block;"><?= $k->bezeichnung ?> (<?= $k->anzahlTN ?> TN)</a>
<?php
    }
    if(isset($_SESSION['fehler']['klassen'])) {
?>
    <div class="fehler"><?= $_SESSION['fehler']['klassen'] ?></div>
<?php
      unset($_SESSION['fehler']['klassen']);
    }
?>
    </td>
<?php
  }
  function makeAnzahlTNTd($zentriert) {
?>
    <td <?= $zentriert ? 'align="center"' : '' ?>><?= isset($this->anzahlTN) ? ($this->anzahlTN<=0 ? '('.$this->anzahlAnmeldungen.')' : $this->anzahlTN) : '?' ?></td>
<?php
  }
  function makeDozentenTd($extras=null) {
?>
    <td class="dozenten" <?= empty($extras) ? '' : $extras ?>>
<?php
    foreach($this->dozenten as $d) {
?>
      <a href="dozent_sehen.php?dozentid=<?= $d->id ?>" style="display:block;"><?= $d->vorname ?> <?= $d->nachname ?></a>
<?php
    }
    if(isset($_SESSION['fehler']['dozenten'])) {
?>
    <div class="fehler"><?= $_SESSION['fehler']['dozenten'] ?></div>
<?php
      unset($_SESSION['fehler']['dozenten']);
    }
?>
    </td>
<?php
  }
  function makeMoodleTd($extras=null) {
    global $moodleisttest;
?>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" <?= empty($extras) ? '' : $extras ?>>
<?php
    if($this->moodleid>0) {
      global $moodleurl;
?>
    <a href="<?= $moodleurl ?>course/view.php?id=<?= $this->moodleid ?>" target="moodle">Moodle-ID=<?= $this->moodleid ?></a>
<?php
    }
    if(isset($_SESSION['fehler']['moodleid'])) {
?>
    <div class="fehler"><?= $_SESSION['fehler']['moodleid'] ?></div>
<?php
      unset($_SESSION['fehler']['moodleid']);
    }
?>
    </td>
<?php
  }
  function makeBearbeitenTd() {
    global $ich;
?>
    <td>
      <a href="kurs_sehen.php?kursid=<?= $this->id ?>">Sehen</a>
<?php
    if($this->istPV) {
?>
      <a href="kurs_pv.php?kursid=<?= $this->id ?>">PV</a>
<?php
    }
    if($this->hatProjektantrag) {
?>
      <a href="ihkprojektantrag.php?kursid=<?= $this->id ?>">Projektantrag</a>
<?php
    }
?>
      <a href="kurs_bearbeiten.php?kursid=<?= $this->id ?>">Bearbeiten</a>
      <a href="kurs_tn.php?kursid=<?= $this->id ?>">TN</a>
      <a href="kurs_anwesenheit.php?kursid=<?= $this->id ?>">Anwesenheit</a><br />
      <a class="<?= $this->kurzbericht_ok ? 'ok' : 'todo' ?>" href="kurs_kurzbericht.php?kursid=<?= $this->id ?>">Kurzbericht</a>
      <a class="<?= $this->tagesbericht_ok ? 'ok' : 'todo' ?>" href="kurs_tagesbericht.php?kursid=<?= $this->id ?>">Tagesbericht</a>
      <a class="<?= $this->notenstatus=='todo' ? 'todo' : 'ok' ?>" href="kurs_il_noten.php?kursid=<?= $this->id ?>">IL+Noten</a>
<?php
    if($ich->istleiter) {
?>
      <a href="../leitung/kurs_bewertung.php?kursid=<?= $this->id ?>">Bewertung</a>
<?php
    }
?>
    </td>
<?php
  }
  function makeSehen($classname='') {
    global $moodleisttest;
    if(isset($_SESSION['fehler']['done'])) {
?>
<div class="done"><?= $_SESSION['fehler']['done'] ?></div>
<?php
      unset($_SESSION['fehler']['done']);
    }
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;margin-bottom:1em;" class="<?= $classname ?>">
  <tr>
    <th>Beginn</th>
    <td><?= date('d.m.Y',$this->von) ?></td>
  </tr>
  <tr>
    <th>Ende</th>
    <td><?= date('d.m.Y',$this->bis) ?></td>
  </tr>
  <tr>
    <th>Einzeltag einer Reihe</th>
    <td><?= $this->einzeltage ? 'Ja' : 'Nein' ?></td>
  </tr>  
  <tr>
    <th>Klassen</th>
<?php
    $this->makeKlassenTd();
?>
  </tr>
  <tr>
    <th>Anzahl TN</th>
    <td>
      Angemeldet:<?= $this->anzahlAnmeldungen ?><br />
      Eingestiegen: <?= $this->anzahlTN ?>
    </td>
  </tr>
  <tr>
    <th>Dozenten</th>
<?php
    $this->makeDozentenTd();
?>
  </tr>
  <tr>
    <th>Modul</th>
    <td><?= empty($this->modulid) ? '(keines)' : $this->modultitel.' ('.$this->moduldauer.' Wochen)' ?></td>
  </tr>
  <tr>
    <th>Zeugnis-relevant</th>
    <td><?= $this->zeugnisrelevant ? 'Ja' : 'Nein' ?></td>
  </tr>
  <tr>
    <th>Gewichtung im Zeugnis</th>
    <td><?= $this->zeugnisgewichtung<0 ? 'automatisch (Kursdauer in Wochen)' : $this->zeugnisgewichtung.' Wochen' ?></td>
  </tr>
  <tr>
    <th><a class="<?= $this->notenstatus=='todo' ? 'todo' : 'ok' ?>" href="kurs_noten.php?kursid=<?= $this->id ?>">Noten</a></th>
    <td>
<?php
    if($this->notenstatus=='keine') echo 'Keine Noten';
    else if($this->notenstatus=='todo') echo 'Unvollständig';
    else echo 'OK';
?>
    </td>
  </tr>
  <tr>
    <th>Ort</th>
    <td><?= $this->ort ?></td>
  </tr>
  <tr>
    <th>Raum</th>
    <td><?= $this->raum ?></td>
  </tr>
  <tr>
    <th>KW</th>
    <td>
<?php
    if(count($this->kw)<=3) {
      foreach($this->kw as $kw) {
?>
      <div>KW <?= substr($kw,5) ?></div>
<?php
      }
    } else {
?>
      <div>KW <?= substr($this->kw[0],5) ?> - <?= substr($this->kw[count($this->kw)-1],5) ?></div>
      <div>(<?= count($this->kw) ?> Wo)</div>
<?php
    }
?>
    </td>
  </tr>
  <tr>
    <th>Titel</th>
<?php
    $this->makeTitelTd();
?>
  </tr>
  <tr>
    <th>Prüfungsvorbereitung</th>
    <td>
<?php
    if($this->istPV) {
?>
      <a href="../verwaltung/kurs_pv.php?kursid=<?= $this->id ?>">PV-Einstellungen bearbeiten</a>
<?php
    } else {
      echo '-';
    }
?>
    </td>
  </tr>
  <tr>
    <th>IHK-Projektantrag</th>
    <td>
<?php
    if($this->hatProjektantrag) {
      echo $this->hatProjektantragDaten ? 'Formular + TN-Daten vorhanden' : 'Formular eröffnet';
    } else {
      echo '-';
    }
?>
    </td>
  </tr>
  <tr <?= empty($this->planungfarbe) ? '' : 'style="background-color:'.htmlentities($this->planungfarbe,ENT_COMPAT).';"' ?>>
    <th>Planungsnotiz</th>
    <td><?= $this->planungnotiz ?></td>
  </tr>
<?php
    if($this->moodleid<=0) {
?>
  <tr>
    <th>Planungsstatus</th>
    <td><?= $this->sichtbar ? 'Für Dozenten und TN sichtbar' : 'In Planung' ?></td>
  </tr>
<?php
    }
?>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
<?php
    $this->makeMoodleTd();
?>
  </tr>  
  <tr>
    <th></th>
<?php
    $this->makeBearbeitenTd();
?>
  </tr>
</table>
<br />
<?php
  }
}
?>