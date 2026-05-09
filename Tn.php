<?php
class Tn {
  static function einenLaden($id,$className) {
    global $db;
    if($id>0){
      $result=$db->query("select tn.* 
        from gpb_tn_view tn
        where tn.id=".$id." limit 1");
      $tn=$result->fetch_object($className);
      $result->free();
    } else {
      $tn=false;
    }
    if(!empty($tn)) {
      $tn->init();
    }
    return $tn;
  }
  
  static function refsMassnahmenLaden($liste) {
    global $db;
    if(empty($liste->byId)) return;
    $result=$db->query("select mtn.* 
      ,m.kuerzel,m.titel
      from gpb_massnahme_tn mtn
      left outer join gpb_massnahme m on m.id=mtn.massnahmeid
      where mtn.tnid in(".implode(',',array_keys($liste->byId)).") order by mtn.einstieg,mtn.ausstieg");
    while($row=$result->fetch_object()) {
      $row->von=empty($row->einstieg) || $row->einstieg=='0000-00-00' ? 0 : strtotime($row->einstieg);
      $row->bis=empty($row->ausstieg) || $row->ausstieg=='0000-00-00' ? 0 : strtotime($row->ausstieg);
      $liste->byId[$row->tnid]->massnahmen[]=$row;
    }
    $result->free();
  }
  static function refsKlassenLaden($liste) {
    global $db;
    if(empty($liste->byId)) return;
    $result=$db->query("select *
      from gpb_klasse_tn ktn
      left outer join gpb_klasse k on k.id=ktn.klasseid
      where ktn.tnid in(".implode(',',array_keys($liste->byId)).") order by ktn.einstieg,ktn.ausstieg");
    while($row=$result->fetch_object()) {
      $row->von=empty($row->einstieg) || $row->einstieg=='0000-00-00' ? 0 : strtotime($row->einstieg);
      $row->bis=empty($row->ausstieg) || $row->ausstieg=='0000-00-00' ? strtotime($row->ende) : strtotime($row->ausstieg);
      $tn=$liste->byId[$row->tnid];
      $tn->klassen[]=$row;
      $tn->klassenids[]=$row->klasseid;
      if($row->von>0 && ($tn->von<=0 || $tn->von>$row->von)) {
        $tn->von=$row->von;
      }
      if($tn->bis<=0 || $tn->bis<$row->bis) {
        $tn->bis=$row->bis;
      }
    }
    $result->free();
  }
  static function refsSelfkurseLaden($liste) {
    global $db;
    if(empty($liste->byId)) return;
    $result=$db->query("select sktn.*
        ,sk.*
      from gpb_selfkurs_tn sktn
      left outer join gpb_selfkurs_view sk on sk.id=sktn.selfkursid
      where sktn.tnid in(".implode(',',array_keys($liste->byId)).") order by sktn.einstieg,sktn.ausstieg,sk.titel");
    while($row=$result->fetch_object()) {
      $row->von=empty($row->einstieg) || $row->einstieg=='0000-00-00' ? 0 : strtotime($row->einstieg);
      $row->bis=empty($row->ausstieg) || $row->ausstieg=='0000-00-00' ? 0 : strtotime($row->ausstieg);
      $liste->byId[$row->tnid]->selfkurse[]=$row;
      $liste->byId[$row->tnid]->selfkurseById[$row->selfkursid]=$row;
    }
    $result->free();
  }
  
  static function makeHeaderTr() {
    global $moodleisttest;
?>
  <tr>
    <th>Anrede</th>
    <th>Vorname</th>
    <th>Nachname</th>
    <th>Beruf</th>
    <th>Nutzername</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
    <th>Klassen</th>
    <th></th>
  </tr>
<?php
  }
  
  function init() {
    $this->von=0;
    $this->bis=0;
  }
  function massnahmenLaden() {
    global $db;
    $result=$db->query("select mtn.* 
      ,m.kuerzel,m.titel
      from gpb_massnahme_tn mtn
      left outer join gpb_massnahme m on m.id=mtn.massnahmeid
      where mtn.tnid=".$this->id." order by mtn.einstieg,mtn.ausstieg");
    while($row=$result->fetch_object()) {
      $row->von=empty($row->einstieg) || $row->einstieg=='0000-00-00' ? 0 : strtotime($row->einstieg);
      $row->bis=empty($row->ausstieg) || $row->ausstieg=='0000-00-00' ? 0 : strtotime($row->ausstieg);
      $this->massnahmen[]=$row;
      if($row->von>0 && ($this->von<=0 || $this->von>$row->von)) {
        $this->von=$row->von;
      }
      if($this->bis<=0 || $this->bis<$row->bis) {
        $this->bis=$row->bis;
      }
    }
    $result->free();
  }
  function klassenLaden() {
    global $db;
    $result=$db->query("select ktn.* 
      ,k.bezeichnung,k.beginn,k.ende
      from gpb_klasse_tn ktn
      left outer join gpb_klasse k on k.id=ktn.klasseid
      where ktn.tnid=".$this->id." order by ktn.einstieg,ktn.ausstieg");
    while($row=$result->fetch_object()) {
      $row->von=empty($row->einstieg) || $row->einstieg=='0000-00-00' ? 0 : strtotime($row->einstieg);
      $row->bis=empty($row->ausstieg) || $row->ausstieg=='0000-00-00' ? strtotime($row->ende) : strtotime($row->ausstieg);
      $this->klassen[]=$row;
      $this->klassenids[]=$row->klasseid;
      if($row->von>0 && ($this->von<=0 || $this->von>$row->von)) {
        $this->von=$row->von;
      }
      if($this->bis<=0 || $this->bis<$row->bis) {
        $this->bis=$row->bis;
      }
    }
    $result->free();
  }
  function selfkurseLaden() {
    global $db;
    $result=$db->query("select sktn.*
      ,sk.*
      from gpb_selfkurs_tn sktn
      left outer join gpb_selfkurs_view sk on sk.id=sktn.selfkursid
      where sktn.tnid=".$this->id." order by sktn.einstieg,sktn.ausstieg,sk.titel");
    while($row=$result->fetch_object()) {
      $row->von=empty($row->einstieg) || $row->einstieg=='0000-00-00' ? 0 : strtotime($row->einstieg);
      $row->bis=empty($row->ausstieg) || $row->ausstieg=='0000-00-00' ? 0 : strtotime($row->ausstieg);
      $this->selfkurse[]=$row;
      $this->selfkurseById[$row->selfkursid]=$row;
    }
    $result->free();
  }
  function ferienLaden($classname='Ferien') {
    global $db;
    if(empty($this->massnahmen)) {
      $this->massnahmenLaden(); //rechnet von...bis
    }
    if(empty($this->klassen)) {
      $this->klassenLaden();
    }
    $this->ferien=array();
    $this->ferienids=array();
    $result=$db->query("select * from gpb_ferien where (art='Feiertag' or art='GPB Ferien') and beginn<='".date('Y-m-d',$this->bis)."' and ende>='".date('Y-m-d',$this->von)."' order by beginn,ende,art");
    while($row=$result->fetch_object($classname)) {
      $row->init();
      $this->ferien[]=$row;
      $this->ferienids[]=$row;
    }
    $result->free();
    if(!empty($this->klassenids)) {
      $result=$db->query("select * 
        from (select f.*
          ,case when ktn.einstieg is null or ktn.einstieg='0000-00-00' then k.beginn else ktn.einstieg end as einstieg
          ,case when ktn.ausstieg is null or ktn.ausstieg='0000-00-00' then k.ende else ktn.ausstieg end as ausstieg
        from gpb_ferien f
        join gpb_klasse k on f.art='Institutsferien' and k.ort=f.ort
        join gpb_klasse_tn ktn on ktn.tnid=".$this->id." and ktn.klasseid=k.id and ktn.einstieg<>'0000-00-00') a
        where a.beginn<=a.ausstieg and a.ende>=a.einstieg
        order by a.beginn,a.ende,a.art");
      while($row=$result->fetch_object($classname)) {
        $row->init();
        $this->ferien[]=$row;
        $this->ferienids[]=$row;
      }
      $result->free();
      $klassenferienById=array();
      $result=$db->query("select * 
        from (select f.*
          ,case when ktn.einstieg is null or ktn.einstieg='0000-00-00' then k.beginn else ktn.einstieg end as einstieg
          ,case when ktn.ausstieg is null or ktn.ausstieg='0000-00-00' then k.ende else ktn.ausstieg end as ausstieg
          ,kf.klasseid
        from gpb_ferien f
        join gpb_klasse_ferien kf on f.art='Klassenferien' and kf.ferienid=f.id
        join gpb_klasse_tn ktn on ktn.tnid=".$this->id." and ktn.klasseid=kf.klasseid and ktn.einstieg<>'0000-00-00'
        join gpb_klasse k on k.id=ktn.klasseid) a
        where a.beginn<=a.ausstieg and a.ende>=a.einstieg
        order by a.beginn,a.ende,a.art");
      while($row=$result->fetch_object($classname)) {
        $row->init();
        $this->ferien[]=$row;
        $this->ferienids[]=$row;
        $klassenferienById[$row->id]=$row;
      }
      $result->free();
      if(!empty($klassenferienById)) {
        $result=$db->query("select * from gpb_klasse_ferien kf join gpb_klasse_view k on k.id=kf.klasseid where kf.ferienid in(".implode(',',array_keys($klassenferienById)).")");
        while($row=$result->fetch_object()) {
          $f=$klassenferienById[$row->ferienid];
          $f->klassenids[]=$row->klasseid;
          $f->klassen[]=$row;
        }
        $result->free();
      }
    }
    usort($this->ferien,function($f0,$f1) {
      if($f0->beginn<$f1->beginn) return -1;
      if($f0->beginn>$f1->beginn) return 1;
      if($f0->ende<$f1->ende) return -1;
      if($f0->ende>$f1->ende) return 1;
      return 0;
    });
  }
  
  function makeTr() {
?>
  <tr>
<?php
    $this->makeNameTds();
    $this->makeBerufTd();
    $this->makeNutzernameTd();
    $this->makeMoodleTd();
    $this->makeKlassenTd();
    $this->makeBearbeitenTd();
?>
  </tr>
<?php
  }
  function makeNameTds() {
?>
    <td><?= $this->anrede ?></td>
    <td><?= $this->vorname ?></td>
    <td><?= $this->nachname ?></td>
<?php
  }
  function makeBerufTd() {
?>
    <td><?= $this->berufkuerzel ?></td>
<?php
  }
  function makeNutzernameTd() {
?>
    <td><?= $this->nutzername ?></td>
<?php
  }
  function makeMoodleTd($center=true) {
    global $moodleurl,$moodleisttest;
?>
    <td class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>" <?= $center ? 'align="center"' : '' ?>>
<?php
    if($this->moodleid>0) {
?>
    <a href="<?= $moodleurl ?>user/profile.php?id=<?= $this->moodleid ?>" target="moodle"><?= $this->moodleid ?></a>
<?php
  }
?>
    </td>
<?php
  }
  function makeKlassenTd() {
?>
    <td>
<?php
    foreach($this->klassen as $klasse) {
?>
      <div><?= $klasse->bezeichnung ?></div>
<?php
    }
?>
    </td>
<?php
  }
  function makeBearbeitenTd() {
?>
    <td></td>
<?php
  }
  
  function makeSehen() {
    global $moodleisttest;
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
<?php
    $this->makeNameTrs();
?>
  <tr>
    <th>Beruf</th>
<?php
    $this->makeBerufTd();
?>
  </tr>
  <tr>
    <th>Nutzername</th>
<?php
    $this->makeNutzernameTd();
?>
  </tr>
  <tr>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
<?php
    $this->makeMoodleTd();
?>
  </tr>
  <tr>
    <th>Klassen</th>
<?php
    $this->makeKlassenTd();
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
  function makeNameTrs() {
?>
  <tr>
    <th>Anrede</th>
    <td><?= $this->anrede ?></td>
  </tr>
  <tr>
    <th>Vorname</th>
    <td><?= $this->vorname ?></td>
  </tr>
  <tr>
    <th>Nachname</th>
    <td><?= $this->nachname ?></td>
  </tr>
<?php
  }
}
?>