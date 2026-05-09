<?php
require_once '../Kurs.php';
class LeitungKurs extends Kurs {
  
  static function massnahmenLaden($liste) {
    global $db;
    if(empty($liste->byId)) return;
    $massnahmeids=array();
    $result=$db->query("select distinct k.id as kursid,mtn.massnahmeid
      from gpb_kurs k
      join gpb_kurs_klasse kk on kk.kursid=k.id
      join gpb_klasse_tn ktn on ktn.klasseid=kk.klasseid 
        and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
        and (ktn.ausstieg='000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)
      join gpb_massnahme_tn mtn on mtn.tnid=ktn.tnid
        and (mtn.einstieg<>'0000-00-00' and mtn.einstieg is not null and mtn.einstieg<=k.ende)
        and (mtn.ausstieg='0000-00-00' or mtn.ausstieg is null or mtn.ausstieg>=k.beginn)
      where k.id in(".implode(',',array_keys($liste->byId)).")");
    while($row=$result->fetch_object()) {
      $liste->byId[$row->kursid]->massnahmeids[]=$row->massnahmeid;
      $massnahmeids[$row->massnahmeid]=true;
    }
    $result->free();
    if(empty($massnahmeids)) return;
    $massnahmenById=array();
    $result=$db->query("select m.* from gpb_massnahme_view m where id in(".implode(',',array_keys($massnahmeids)).")");
    while($row=$result->fetch_object()) {
      $massnahmenById[$row->id]=$row;
    }
    $result->free();
    foreach($liste->alle as $kurs) {
      foreach($kurs->massnahmeids as $massnid) {
        $kurs->massnahmen[]=$massnahmenById[$massnid];
      }
    }
  }
  
  function init() {
    parent::init();
    $this->massnahmeids=array();
    $this->massnahmen=array();
  }
  
  function makeModulTd() {
?>
    <td><?= $this->modulkuerzel ?> - <?= $this->modultitel ?></td>
<?php
  }
  function makeLocationTds() {
?>
    <td><?= $this->ort ?></td>
    <td><a href="../verwaltung/raum_sehen.php?raumid=<?= $this->raumid ?>"><?= $this->raum ?> <?= $this->anzahlPlaetze<0 ? '' : '('.$this->anzahlPlaetze.' Plätze)' ?></a></td>
<?php
  }
  function makeMassnahmenTd() {
?>
    <td class="massnahmen">
<?php
    foreach($this->massnahmen as $m) {
?>
      <a href="../verwaltung/massnahme_sehen.php?massnahmeid=<?= $m->id ?>" style="display:block;"><?= $m->kuerzel ?> (<?= $m->anzahlTN ?> TN)</a>
<?php
    }
?>
    </td>
<?php
  }
  function makeKlassenTd() {
?>
    <td class="klassen">
<?php
    foreach($this->klassen as $k) {
?>
      <a href="../verwaltung/klasse_sehen.php?klasseid=<?= $k->id ?>" style="display:block;"><?= $k->bezeichnung ?> (<?= $k->anzahlTN ?> TN)</a>
<?php
    }
?>
    </td>
<?php
  }
  function makeAnzahlTNTd($zentriert) {
?>
    <td <?= $zentriert ? 'align="center"' : '' ?>><?= isset($this->anzahlTN) ? 'Plan: '.$this->anzahlAnmeldungen.' Ist: '.$this->anzahlTN : '?' ?></td>
<?php
  }
  function makeDozentenTd($extras=null) {
?>
    <td class="dozenten" <?= empty($extras) ? '' : $extras ?>>
<?php
    foreach($this->dozenten as $d) {
?>
      <a href="../verwaltung/dozent_sehen.php?dozentid=<?= $d->id ?> style="display:block;"><?= $d->vorname ?> <?= $d->nachname ?></a>
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
?>
    <td>
      <a href="kurs_sehen.php?kursid=<?= $this->id ?>">Sehen</a>
      <a href="../verwaltung/kurs_bearbeiten.php?kursid=<?= $this->id ?>">Bearbeiten</a>
<?php
      if($this->istPV) {
?>
      <a href="../verwaltung/kurs_pv.php?kursid=<?= $this->id ?>">PV</a>
<?php
    }
?>
      <a href="../verwaltung/kurs_tn.php?kursid=<?= $this->id ?>">TN</a>
      <a href="../verwaltung/kurs_anwesenheit.php?kursid=<?= $this->id ?>">Anwesenheit</a><br />
      <a class="<?= $this->kurzbericht_ok ? 'ok' : 'todo' ?>" href="../verwaltung/kurs_kurzbericht.php?kursid=<?= $this->id ?>">Kurzbericht</a>
      <a class="<?= $this->tagesbericht_ok ? 'ok' : 'todo' ?>" href="../verwaltung/kurs_tagesbericht.php?kursid=<?= $this->id ?>">Tagesbericht</a>
      <a class="<?= $this->notenstatus=='todo' ? 'todo' : 'ok' ?>" href="../verwaltung/kurs_il_noten.php?kursid=<?= $this->id ?>">IL+Noten</a>
<?php
    if($this->bewertungStatus!='nochnicht') {
?>
      <a href="kurs_bewertung.php?kursid=<?= $this->id ?>">Bewertung</a>
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
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="<?= $classname ?>">
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
    <th>Noten</th>
    <td>
<?php
    if($this->notenstatus=='keine') echo 'Keine Noten';
    else if($this->notenstatus=='todo') echo 'Unvollständig';
    else echo 'OK';
?>
    </td>
  </tr>
  <tr>
    <th>Bewertung</th>
    <td><input type="checkbox" value="J" <?= $this->bewertungoffen ? 'checked' : '' ?> onchange="location.href='kurs_bewertungoffen_speichern.php?redirect='+encodeURIComponent(location.href)+'&kursid=<?= $this->id ?>&bewertungoffen='+(this.checked ? 'J' : 'N');" /> offen (ab Kursende)</td>
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
    foreach($this->kw as $kw) {
?>
      <div>KW <?= substr($kw,5) ?></div>
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