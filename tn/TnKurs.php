<?php
require_once '../Kurs.php';
class TnKurs extends Kurs {
  
  static function vonMirLaden($liste) {
    global $db,$ich;
    if(empty($liste->byId)) return;
    $notenKursids=array();
    $pvantragkursids=array();
    foreach($liste->alle as $kurs) {
      $kurs->vonMir=false;
    }
    $result=$db->query("select distinct k.id
        from gpb_klasse_tn ktn 
        join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
        join gpb_kurs_view k on k.id=kk.kursid and (k.moodleid>0 or k.sichtbar)
        where k.id in(".implode(',',array_keys($liste->byId)).") 
        and ktn.tnid=".$ich->id."
        and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
        and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)");
    while($row=$result->fetch_object()) {
      $kurs=$liste->byId[$row->id];
      $kurs->vonMir=true;
      if($kurs->notenstatus=='ok') {
        $notenKursids[]=$kurs->id;
      }
      if($kurs->istPV && $kurs->hatProjektantrag) {
        $pvantragkursids[]=$kurs->id;
        $kurs->hatProjektantrag=false; //wird wieder auf true gesetzt, falls der TN mündliche/Projekt PV hat
      }
    }
    $result->free();
    if(!empty($notenKursids)) {
      $result=$db->query("select * from gpb_note where tnid=".$ich->id." and kursid in(".implode(',',$notenKursids).")");
      while($row=$result->fetch_object()) {
        $liste->byId[$row->kursid]->note=$row;
      }
      $result->free();
    }
    if(!empty($pvantragkursids)) {
      $result=$db->query("select pvk.* 
        from gpb_pruefungsvorbereitung_klasse pvk
        join gpb_kurs k on k.id=pvk.kursid
        join gpb_klasse_tn ktn on ktn.klasseid=pvk.klasseid and ktn.tnid=".$ich->id." 
          and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende) 
          and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)
        where pvk.kursid in(".implode(',',$pvantragkursids).")
          and pvk.hatMuendliche");
      while($row=$result->fetch_object()) {
        $liste->byId[$row->kursid]->hatProjektantrag=true;
      }
      $result->free();
    }
  }
  
  function ladeVonMir() {
    global $db,$ich;
    $result=$db->query("select distinct k.id,ktn.klasseid
        from gpb_klasse_tn ktn 
        join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
        join gpb_kurs_view k on k.id=kk.kursid and (k.moodleid>0 or k.sichtbar)
        where k.id=".$this->id."
        and ktn.tnid=".$ich->id."
        and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
        and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)");
    $row=$result->fetch_object();
    if($row) {
      $this->vonMir=true;
    } else {
      $this->vonMir=false;
    }
    $result->free();
    if($this->vonMir && $this->notenstatus=='ok') {
      $result=$db->query("select * from gpb_note where kursid=".$this->id." and tnid=".$ich->id." limit 1");
      $this->note=$result->fetch_object();
      $result->free();
    }
    if($this->vonMir && $this->istPV) {
      if($row) {
        $result=$db->query("select * from gpb_pruefungsvorbereitung_klasse where kursid=".$this->id." and klasseid=".$row->klasseid);
        $this->pv=$result->fetch_object();
        $result->free();
      }
      if(!isset($this->pv)) {
        $this->pv=(object)array('hatAP1'=>false,'hatAP2'=>false,'hatMuendliche'=>false);
      }
      if(!$this->pv->hatMuendliche) {
        $this->hatProjektantrag=false;
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
    <th>Klasse</th>
    <th>Dozent</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
    <th>Note</th>
    <th></th>
  </tr>
<?php
  }
  function makeTr($mitAnzahlTN) {
?>
  <tr>
<?php
    $this->makeZeitraumTds();
    $this->makeTitelTd();
    $this->makeLocationTds();
    $this->makeKlassenTd();
    $this->makeDozentenTd();
    $this->makeMoodleTd();
    $this->makeNoteTd();
    $this->makeBearbeitenTd();
?>
  </tr>
<?php
  }
  function makeDozentenTd($extras=null) {
?>
    <td class="dozenten" <?= empty($extras) ? '' : $extras ?>>
<?php
    foreach($this->dozenten as $d) {
?>
      <div><?= $d->vorname ?> <?= $d->nachname ?></div>
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
      <div><?= $k->bezeichnung ?></div>
<?php
    }
?>
    </td>
<?php
  }
  function makeNoteTd() {
?>
    <td class="note">
<?php
    if($this->vonMir) {
      if($this->notenstatus=='keine') {
        echo 'Kurs ohne Note';
      } else if($this->notenstatus=='todo') {
        echo 'Noch nicht freigeschaltet';
      } else if(isset($this->note)) {
        if($this->note->nachnote>0) {
          echo $this->note->nachnote;
        } else if($this->note->fehlt) {
          echo 'Abwesend';
          $this->makeNachklausurDiv($this->note);
        } else {
          echo $this->note->note;
          $this->makeNachklausurDiv($this->note);
        }
      } else {
        echo 'Dozent hat keine Note eingetragen';
        $this->makeNachklausurDiv(null);
      }
    }
?>
    </td>
<?php
  }
  static $tagnamen=array('Mon'=>'Mo','Tue'=>'Di','Wed'=>'Mi','Thu'=>'Do','Fri'=>'Fr','Sat'=>'Sa','Sun'=>'So');
  function makeNachklausurDiv($note) {
    global $db,$nachschreibetermine;
    if(empty($note) || empty($note->nachschreibetermin)) {
      $termin='';
    } else {
      $termin=strtotime($note->nachschreibetermin);
      $termin=TnKurs::$tagnamen[date('D',$termin)].' '.date('d.m.Y H:i',$termin);
    }
    switch(empty($note) ? null : $note->nachschreibestatus) {
      case 'beantragt':
        echo '<div>Nachschreiben beantragt<br />'.$termin.'</div>';
        break;
      case 'genehmigt':
        echo '<div class="done" style="font-size:1.25em;font-weight:bold;">Nachschreiben genehmigt<br />'.$termin.'</div>';
        break;
      case 'abgelehnt':
        echo '<div class="nok">Nachschreiben abgelehnt</div>';
        break;
      case 'geschrieben':
      case 'Dozent reagiert nicht':
        echo '<div>Nachklausur in Bewertung</div>';
        break;
      case 'keine Abgabe':
        echo '<div>Nachklausur nicht abgegeben</div>';
        break;
      case 'erledigt':
        break;
      default:
        if(!empty($note) && !$note->fehlt && ((!empty($note->nachnote) && $note->nachnote>=50) || (!empty($note->note) && $note->note>=50))) {
          break;
        }
        if(!isset($nachschreibetermine)) {
          $nachschreibetermine=array();
          $result=$db->query("select * from gpb_nachschreibetermin where ort='".addslashes($this->ort)."' and termin>'".date('Y-m-d')."' order by termin");
          while($row=$result->fetch_object()) {
            $nachschreibetermine[]=$row;
          }
          $result->free();
        }
?>
      <script>
      function nachschreiben_beantragen_<?= $this->id ?>() {
        if(confirm('Nachschreiben beantragen, sicher?')) {
          document.getElementById('nachschreiben_beantragen_<?= $this->id ?>_form').submit();
        }
      }
      </script>
      <form action="nachschreiben_beantragen.php" id="nachschreiben_beantragen_<?= $this->id ?>_form" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
        <input type="hidden" name="kursid" value="<?= $this->id ?>" />
        <input type="hidden" name="redirect" value="<?= htmlentities((empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],ENT_COMPAT) ?>" />
        Nachschreibewunsch am<br />
        <select name="termin">
<?php
      foreach($nachschreibetermine as $t) {
        $wann=strtotime($t->termin);
?>
          <option value="<?= $t->termin ?>"><?= TnKurs::$tagnamen[date('D',$wann)] ?> <?= date('d.m.Y H:i',$wann) ?></option>
<?php
      }
?>
        </select><br />
        <button type="button" onclick="nachschreiben_beantragen_<?= $this->id ?>()">beantragen</button>
      </form>
<?php
    }
  }
  function makeBearbeitenTd() {
?>
    <td>
      <a href="kurs_sehen.php?kursid=<?= $this->id ?>">Sehen</a>
<?php
    if($this->vonMir) {
      if($this->bewertungStatus!='nochnicht') {
?>
      <a href="kurs_bewertung.php?kursid=<?= $this->id ?>"><?= $this->bewertungStatus=='offen' ? 'Bewerten' : 'Bewertung' ?></a>
<?php
      }
      if($this->hatProjektantrag) {
?>
      <a href="ihkprojektantrag.php?kursid=<?= $this->id ?>">Projektantrag</a>
<?php
      }
    }
?>
    </td>
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
    <td class="raum"><?= $this->raum ?></td>
  </tr>
  <tr>
    <th>Klasse</th>
<?php
    $this->makeKlassenTd();
?>
  </tr>
  <tr>
    <th>Dozent</th>
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
    <th>Zeugnis-Modul</th>
    <td><?= empty($this->modulid) ? '(keines)' : $this->modultitel.' ('.$this->moduldauer.' Wochen)' ?></td>
  </tr>
<?php
    if($this->vonMir) {
?>
  <tr>
    <th>Note</th>
<?php
      $this->makeNoteTd();
?>
  </tr>
<?php
    }
?>
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