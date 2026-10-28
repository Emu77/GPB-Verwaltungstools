<?php
require_once 'Aufgabe.php';
class AufgabeMultipleChoice extends Aufgabe {

  // Fragen inkl. Optionen laden, sortiert wie angelegt
  function ladeFragen() {
    global $db;
    $fragen = array();
    $result = $db->query("select * from gpb_aufgabe_frage where aufgabeid=".intval($this->id)." order by reihenfolge, id");
    while($row = $result->fetch_object()) {
      $row->optionen = array();
      $fragen[$row->id] = $row;
    }
    $result->free();

    if(!empty($fragen)) {
      $result = $db->query("select * from gpb_aufgabe_option where frageid in (".implode(',', array_keys($fragen)).") order by reihenfolge, id");
      while($row = $result->fetch_object()) {
        if(isset($fragen[$row->frageid])) {
          $fragen[$row->frageid]->optionen[] = $row;
        }
      }
      $result->free();
    }

    return array_values($fragen);
  }

  // bereits gegebene Antworten eines TN: frageid => optionid
  function ladeAntworten($abgabeid) {
    global $db;
    $antworten = array();
    if(empty($abgabeid)) return $antworten;
    $result = $db->query("select frageid,optionid from gpb_aufgabe_abgabe_antwort where abgabeid=".intval($abgabeid));
    while($row = $result->fetch_object()) {
      $antworten[$row->frageid] = $row->optionid;
    }
    $result->free();
    return $antworten;
  }

  function makeAbgabeformular($tnid) {
    $gesperrt = $this->istAbgelaufen();
    $abgabe = $this->ladeAbgabe($tnid);
    $antworten = $this->ladeAntworten($abgabe->id ?? null);
    $fragen = $this->ladeFragen();

    if(empty($fragen)) {
?>
    <p><em>Für diese Aufgabe sind noch keine Fragen hinterlegt.</em></p>
<?php
      return;
    }
?>
    <form method="post" action="aufgabe_abgabe_speichern.php">
      <input type="hidden" name="aufgabeid" value="<?= $this->id ?>" />
      <input type="hidden" name="kursid" value="<?= $this->kursid ?>" />
      <fieldset class="aufgabe-abgabe-felder" style="border:0;padding:0;margin:0;"<?= $gesperrt ? ' disabled' : '' ?>>
<?php
    foreach($fragen as $frage) {
?>
      <fieldset style="margin-bottom:1em;">
        <legend><?= htmlentities($frage->frage) ?></legend>
<?php
      foreach($frage->optionen as $option) {
        $checked = isset($antworten[$frage->id]) && $antworten[$frage->id]==$option->id;
?>
        <label style="display:block;">
          <input type="radio" name="antwort[<?= $frage->id ?>]" value="<?= $option->id ?>" <?= $checked ? 'checked' : '' ?> />
          <?= htmlentities($option->text) ?>
        </label>
<?php
      }
?>
      </fieldset>
<?php
    }
?>
<?php
    if(!empty($abgabe->abgegeben_am)) {
?>
      <p><em>Zuletzt abgegeben am <?= date('d.m.Y H:i',strtotime($abgabe->abgegeben_am)) ?></em></p>
<?php
    }
?>
      </fieldset>
<?php
    if($gesperrt) {
?>
      <p class="nok">Abgabefrist abgelaufen – keine Abgabe/Änderung mehr möglich.</p>
<?php
    } else {
?>
      <p><button type="submit">Abgeben</button></p>
<?php
    }
?>
    </form>
<?php
  }

  // liefert Fehlermeldung oder null bei Erfolg
  function speichereAbgabe($tnid) {
    global $db;

    $fragen = $this->ladeFragen();
    if(empty($fragen)) {
      return 'Für diese Aufgabe sind noch keine Fragen hinterlegt.';
    }

    $gueltigeOptionenProFrage = array();
    foreach($fragen as $frage) {
      $gueltigeOptionenProFrage[$frage->id] = array();
      foreach($frage->optionen as $option) {
        $gueltigeOptionenProFrage[$frage->id][$option->id] = true;
      }
    }

    $eingereicht = isset($_POST['antwort']) && is_array($_POST['antwort']) ? $_POST['antwort'] : array();

    // nur gültige, zu dieser Aufgabe gehörende Antworten übernehmen
    $antworten = array();
    foreach($eingereicht as $frageid => $optionid) {
      $frageid = (int)$frageid;
      $optionid = (int)$optionid;
      if(isset($gueltigeOptionenProFrage[$frageid][$optionid])) {
        $antworten[$frageid] = $optionid;
      }
    }

    if(empty($antworten)) {
      return 'Bitte mindestens eine Frage beantworten.';
    }

    // Abgabe-Datensatz anlegen/aktualisieren
    $stmt = $db->prepare("insert into gpb_aufgabe_abgabe (aufgabeid,tnid,abgegeben_am)
      values (?,?,now())
      on duplicate key update abgegeben_am=values(abgegeben_am)");
    $stmt->bind_param('ii', $this->id, $tnid);
    $stmt->execute();
    $stmt->close();

    $abgabe = $this->ladeAbgabe($tnid);
    $abgabeid = $abgabe->id;

    // alte Antworten ersetzen
    $stmt = $db->prepare("delete from gpb_aufgabe_abgabe_antwort where abgabeid=?");
    $stmt->bind_param('i', $abgabeid);
    $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare("insert into gpb_aufgabe_abgabe_antwort (abgabeid,frageid,optionid) values (?,?,?)");
    foreach($antworten as $frageid => $optionid) {
      $stmt->bind_param('iii', $abgabeid, $frageid, $optionid);
      $stmt->execute();
    }
    $stmt->close();

    $this->automatischBewerten($abgabeid);

    return null;
  }

  function kannAutomatischBewerten() {
    return true;
  }

  // Auswertung einer Abgabe: array('richtig'=>n, 'gesamt'=>m, 'details'=>[...])
  // details pro Frage: frage, gewaehlt (Text oder null), richtig (Text), korrekt (bool)
  function auswerten($abgabeid) {
    $fragen = $this->ladeFragen();
    $antworten = $this->ladeAntworten($abgabeid);
    $ergebnis = array('richtig'=>0, 'gesamt'=>count($fragen), 'details'=>array());
    foreach($fragen as $frage) {
      $richtigText = null;
      $richtigId = null;
      $gewaehltText = null;
      foreach($frage->optionen as $option) {
        if($option->ist_richtig) {
          $richtigId = $option->id;
          $richtigText = $option->text;
        }
        if(isset($antworten[$frage->id]) && $antworten[$frage->id]==$option->id) {
          $gewaehltText = $option->text;
        }
      }
      $korrekt = $richtigId!==null && isset($antworten[$frage->id]) && $antworten[$frage->id]==$richtigId;
      if($korrekt) $ergebnis['richtig']++;
      $ergebnis['details'][] = array(
        'frage' => $frage->frage,
        'gewaehlt' => $gewaehltText,
        'richtig' => $richtigText,
        'korrekt' => $korrekt,
      );
    }
    return $ergebnis;
  }

  function automatischBewerten($abgabeid) {
    global $db;

    $auswertung = $this->auswerten($abgabeid);
    if($auswertung['gesamt']==0) return;
    $anzahlFragen = $auswertung['gesamt'];
    $anzahlRichtig = $auswertung['richtig'];

    $punkte = null;
    if($anzahlFragen>0 && $this->punkteMax!==null) {
      $punkte = round(($anzahlRichtig / $anzahlFragen) * $this->punkteMax, 2);
    }

    $stmt = $db->prepare("insert into gpb_aufgabe_bewertung (abgabeid,punkte,bewertet_am)
      values (?,?,now())
      on duplicate key update punkte=values(punkte), bewertet_am=values(bewertet_am)");
    $stmt->bind_param('id', $abgabeid, $punkte);
    $stmt->execute();
    $stmt->close();
  }
}
?>
