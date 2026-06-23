<?php
class Kurs {
  static function einenLaden($id,$className) {
    global $db;
    if($id>0){
      $result=$db->query("select * from gpb_kurs_view where id=".$id." limit 1");
      $kurs=$result->fetch_object($className);
      $result->free();
    } else {
      $kurs=false;
    }
    if(!empty($kurs)) {
      $kurs->init();
      $result=$db->query("select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent where id in(select dozentid from gpb_kurs_dozent where kursid=".$kurs->id.") order by nachname,vorname");
      while($row=$result->fetch_object()) {
        $kurs->dozenten[]=$row;
        $kurs->dozentenids[]=$row->id;
      }
      $result->free();
      $kurs->klassenids=array();
      $result=$db->query("select * from gpb_klasse_view where id in(select klasseid from gpb_kurs_klasse where kursid=".$kurs->id.") order by bezeichnung");
      while($row=$result->fetch_object()) {
        $kurs->klassen[]=$row;
        $kurs->klassenids[]=$row->id;
      }
      $result->free();
      $result=$db->query("select 
        (select count(distinct ktn.tnid)
          from gpb_klasse_tn ktn
          where ktn.klasseid in(select kk.klasseid from gpb_kurs_klasse kk where kk.kursid=".$kurs->id.")
          and (ktn.einstieg<>'0000-00-00' and ktn.einstieg<=k.ende)
          and (ktn.ausstieg='0000-00-00' or ktn.ausstieg>=k.beginn)) as anzahlTN
        ,(select count(distinct ktn.tnid)
          from gpb_klasse_tn ktn
          where ktn.klasseid in(select kk.klasseid from gpb_kurs_klasse kk where kk.kursid=".$kurs->id.")
          and (ktn.einstieg='0000-00-00' or ktn.einstieg<=k.ende)
          and (ktn.ausstieg='0000-00-00' or ktn.ausstieg>=k.beginn)) as anzahlAnmeldungen
        from gpb_kurs k
        where k.id=".$kurs->id);
      $row=$result->fetch_object();
      $result->free();
      $kurs->anzahlTN=$row->anzahlTN;
      $kurs->anzahlAnmeldungen=$row->anzahlAnmeldungen;
    }
    return $kurs;
  }
  static function einenByMoodleIDLaden($moodleid,$className) {
    global $db;
    if($moodleid>0){
      $result=$db->query("select * from gpb_kurs_view where moodleid=".$moodleid." limit 1");
      $kurs=$result->fetch_object($className);
      $result->free();
    } else {
      $kurs=false;
    }
    if(!empty($kurs)) {
      $kurs->init();
      $result=$db->query("select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent where id in(select dozentid from gpb_kurs_dozent where kursid=".$kurs->id.") order by nachname,vorname");
      while($row=$result->fetch_object()) {
        $kurs->dozenten[]=$row;
        $kurs->dozentenids[]=$row->id;
      }
      $result->free();
      $kurs->klassenids=array();
      $result=$db->query("select * from gpb_klasse_view where id in(select klasseid from gpb_kurs_klasse where kursid=".$kurs->id.") order by bezeichnung");
      while($row=$result->fetch_object()) {
        $kurs->klassen[]=$row;
        $kurs->klassenids[]=$row->id;
      }
      $result->free();
      $result=$db->query("select 
        (select count(distinct ktn.tnid)
          from gpb_klasse_tn ktn
          where ktn.klasseid in(select kk.klasseid from gpb_kurs_klasse kk where kk.kursid=".$kurs->id.")
          and (ktn.einstieg<>'0000-00-00' and ktn.einstieg<=k.ende)
          and (ktn.ausstieg='0000-00-00' or ktn.ausstieg>=k.beginn)) as anzahlTN
        ,(select count(distinct ktn.tnid)
          from gpb_klasse_tn ktn
          where ktn.klasseid in(select kk.klasseid from gpb_kurs_klasse kk where kk.kursid=".$kurs->id.")
          and (ktn.einstieg='0000-00-00' or ktn.einstieg<=k.ende)
          and (ktn.ausstieg='0000-00-00' or ktn.ausstieg>=k.beginn)) as anzahlAnmeldungen
        from gpb_kurs k
        where k.id=".$kurs->id);
      $row=$result->fetch_object();
      $result->free();
      $kurs->anzahlTN=$row->anzahlTN;
      $kurs->anzahlAnmeldungen=$row->anzahlAnmeldungen;
    }
    return $kurs;
  }
  
  static function refsLaden($liste) {
    global $db;
    if(empty($liste->byId)) return;
    Kurs::dozentenLaden($liste);
    Kurs::klassenLaden($liste);
  }
  static function dozentenLaden($liste) {
    global $db;
    if(empty($liste->byId)) return;
    $dozentenById=array();
    $result=$db->query("select *,concat(vorname,concat(' ',nachname)) as dozentenname from gpb_dozent where id in(select dozentid from gpb_kurs_dozent where kursid in(".implode(',',array_keys($liste->byId))."))");
    while($row=$result->fetch_object()) {
      $dozentenById[$row->id]=$row;
    }
    $result->free();
    $result=$db->query("select * from gpb_kurs_dozent where kursid in(".implode(',',array_keys($liste->byId)).")");
    while($row=$result->fetch_object()) {
      $liste->byId[$row->kursid]->dozentenids[]=$row->dozentid;
      $liste->byId[$row->kursid]->dozenten[]=$dozentenById[$row->dozentid];
    }
    $result->free();
  }
  static function klassenLaden($liste) {
    global $db;
    if(empty($liste->byId)) return;
    $klassenById=array();
    $result=$db->query("select * from gpb_klasse_view where id in(select klasseid from gpb_kurs_klasse where kursid in(".implode(',',array_keys($liste->byId))."))");
    while($row=$result->fetch_object()) {
      $klassenById[$row->id]=$row;
    }
    $result->free();
    $result=$db->query("select * from gpb_kurs_klasse where kursid in(".implode(',',array_keys($liste->byId)).")");
    while($row=$result->fetch_object()) {
      $liste->byId[$row->kursid]->klassen[]=$klassenById[$row->klasseid];
    }
    $result->free();
  }
  
  static function anzahlTNLaden($liste) {
    global $db;
    if(empty($liste->byId)) return;
    $result=$db->query("select k.id
      ,(select count(distinct ktn.tnid)
        from gpb_klasse_tn ktn
        where ktn.klasseid in(select kk.klasseid from gpb_kurs_klasse kk where kk.kursid=k.id)
        and (ktn.einstieg<>'0000-00-00' and ktn.einstieg<=k.ende)
        and (ktn.ausstieg='0000-00-00' or ktn.ausstieg>=k.beginn)) as anzahlTN
      ,(select count(distinct ktn.tnid)
        from gpb_klasse_tn ktn
        where ktn.klasseid in(select kk.klasseid from gpb_kurs_klasse kk where kk.kursid=k.id)
        and (ktn.einstieg='0000-00-00' or ktn.einstieg<=k.ende)
        and (ktn.ausstieg='0000-00-00' or ktn.ausstieg>=k.beginn)) as anzahlAnmeldungen
      from gpb_kurs k
      where k.id in(".implode(',',array_keys($liste->byId)).")");
    while($row=$result->fetch_object()) {
      $kurs=$liste->byId[$row->id];
      $kurs->anzahlTN=$row->anzahlTN;
      $kurs->anzahlAnmeldungen=$row->anzahlAnmeldungen;
    }
    $result->free();
  }
  
  function ferienLaden() {
    global $db;
    require_once 'Liste.php';
    require_once 'Ferien.php';
    $stmt=$db->prepare("select * from gpb_ferien where beginn<='".$this->ende."' and ende>='".$this->beginn."' order by beginn,ende,art");
    $ferien=new Liste('Ferien',$stmt);
    Ferien::refsLaden($ferien);
    $this->ferienByTag=array();
    foreach($ferien->alle as $fer) {
      $ok=$fer->art=='Feiertag'
        || $fer->art=='GPB Ferien'
        || ($fer->art=='Institutsferien' && $fer->ort==$this->ort);
      if($fer->art=='Klassenferien') {
        $ok=true;
        foreach($this->klassenids as $kid) {
          if(!in_array($kid,$fer->klassenids)) {
            $ok=false;
            break;
          }
        }
      }
      if($ok) {
        for($d=max($this->von,$fer->von);$d<=min($this->bis,$fer->bis);$d=strtotime('+1 day',$d)) {
          $tag=date('Y-m-d',$d);
          if(!isset($this->ferienByTag[$tag]) || $fer->istAllgemeiner($this->ferienByTag[$tag])) {
            $this->ferienByTag[$tag]=$fer;
          }
        }
      }
    }
  }
  
  static function makeHeaderTr($mitAnzahlTN) {
    global $moodleisttest;
?>
  <tr>
    <th>KW</th>
    <th>Beginn</th>
    <th>Ende</th>
    <th>Titel</th>
    <th>Ort</th>
    <th>Raum</th>
    <th>Klassen</th>
<?php
    if($mitAnzahlTN) {
?>
    <th>Anz. TN</th>
<?php
    }
?>
    <th>Dozenten</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
    <th>Mini</th>
    <th></th>
  </tr>
<?php
  }
  
  function init() {
    $this->von=strtotime($this->beginn);
    $this->bis=strtotime($this->ende);
    $this->kw=array();
    for($wann=$this->von;$wann<=$this->bis;$wann=strtotime('+1 week',$wann)) {
      $this->kw[]=date('Y-W',$wann);
    }
    $this->dozentenids=array();
    $this->dozenten=array();
    $this->klassen=array();
    $today=strtotime('today');
    $this->bewertungStatus=$this->ende=='0000-00-00' || $today<strtotime('-1day',$this->bis) ? 'nochnicht' : ($this->bewertungoffen ? 'offen' : 'geschlossen');
  }
  
  function makeTr($mitAnzahlTN) {
?>
  <tr>
<?php
    $this->makeZeitraumTds();
    $this->makeTitelTd();
    $this->makeLocationTds();
    $this->makeKlassenTd();
    if($mitAnzahlTN) {
      $this->makeAnzahlTNTd(true);
    }
    $this->makeDozentenTd();
    $this->makeMoodleTd();
    $this->makeMiniTd();
    $this->makeBearbeitenTd();
?>
  </tr>
<?php
  }
  function makeZeitraumTds() {
?>
    <td class="kw">
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
    <td class="von"><?= date('d.m.Y',$this->von) ?></td>
    <td class="bis"><?= date('d.m.Y',$this->bis) ?></td>
<?php
  }
  function makeTitelTd() {
?>
    <td style="max-width:400px;"><?= $this->titel ?></td>
<?php
  }
  function makeLocationTds() {
?>
    <td class="ort"><?= $this->ort ?></td>
    <td class="raum"><?= $this->raum ?></td>
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
?>
    </td>
<?php
  }
  function makeAnzahlTNTd($zentriert) {
?>
    <td <?= $zentriert ? 'align="center"' : '' ?>><?= isset($this->anzahlTN) ? $this->anzahlTN.' TN' : '?' ?></td>
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
?>
    </td>
<?php
  }
  function makeMoodleTd($extras=null) {
    global $moodleisttest;
    if($this->moodleid>0) {
      global $moodleurl;
?>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" <?= empty($extras) ? '' : $extras ?>><a href="<?= $moodleurl ?>course/view.php?id=<?= $this->moodleid ?>" target="moodle">Moodle-ID=<?= $this->moodleid ?></a></td>
<?php
    } else {
?>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" <?= empty($extras) ? '' : $extras ?>>(in Planung)</td>
<?php
    }
  }
  function makeMiniTd($extras=null) {
?>
    <td <?= empty($extras) ? '' : $extras ?>></td>
<?php
  }
  function makeBearbeitenTd() {
?>
    <td class="aktionen"></td>
<?php
  }
  
  function makeSehen($classname='') {
    global $moodleisttest;
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;" class="<?= $classname ?>">
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
    <th>Beginn</th>
    <td><?= date('d.m.Y',$this->von) ?></td>
  </tr>
  <tr>
    <th>Ende</th>
    <td><?= date('d.m.Y',$this->bis) ?></td>
  </tr>
  <tr>
    <th>Titel</th>
<?php
    $this->makeTitelTd();
?>
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
    <th>Klassen</th>
<?php
    $this->makeKlassenTd();
?>
  </tr>
  <tr>
    <th>Anzahl TN</th>
<?php
    $this->makeAnzahlTNTd(false);
?>
  </tr>
  <tr>
    <th>Dozenten</th>
<?php
    $this->makeDozentenTd();
?>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
<?php
    $this->makeMoodleTd();
?>
  </tr>
  <tr>
    <th>Mini</th>
<?php
    $this->makeMiniTd();
?>
  </tr>
  <tr>
    <th>Zeugnis-Modul</th>
    <td><?= empty($this->modulid) ? '(keines)' : $this->modultitel.' ('.$this->moduldauer.' Wochen)' ?></td>
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