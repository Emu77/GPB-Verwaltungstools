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
    $sperrgrund = $this->abgabeSperrgrund($tnid);
    $gesperrt = $sperrgrund!==null;
    $abgabe = $this->ladeAbgabe($tnid);
    $antworten = $this->ladeAntworten($abgabe->id ?? null);
    $fragen = $this->ladeFragen();
    // Nach Fristablauf sieht der TN die Lösung: richtige Antwort grün, eigene
    // falsche Antwort rot. Vorher nicht, sonst könnten TN die Lösungen an
    // andere weitergeben, die noch nicht abgegeben haben.
    $zeigeLoesung = $this->istAbgelaufen();

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
        $klasse = '';
        $markierung = '';
        if($zeigeLoesung && $option->ist_richtig) {
          $klasse = 'mc-loesung-richtig';
          $markierung = $checked ? ' ✓ richtig' : ' ✓ richtige Antwort';
        } elseif($zeigeLoesung && $checked) {
          $klasse = 'mc-loesung-falsch';
          $markierung = ' ✗ deine Antwort';
        }
?>
        <label style="display:block;"<?= $klasse!=='' ? ' class="'.$klasse.'"' : '' ?>>
          <input type="radio" name="antwort[<?= $frage->id ?>]" value="<?= $option->id ?>" <?= $checked ? 'checked' : '' ?> />
          <?= htmlentities($option->text) ?><?php if($markierung!==''): ?><span class="mc-loesung-markierung"><?= $markierung ?></span><?php endif; ?>
        </label>
<?php
      }
?>
      </fieldset>
<?php
    }
?>
<?php
    if($zeigeLoesung) {
      if(!empty($abgabe)) {
        $auswertung = $this->auswerten($abgabe->id);
?>
      <p class="mc-loesung-zusammenfassung"><strong>Lösung:</strong> <?= $auswertung['richtig'] ?> von <?= $auswertung['gesamt'] ?> Fragen richtig beantwortet.</p>
<?php
      } else {
?>
      <p class="mc-loesung-zusammenfassung"><strong>Lösung:</strong> Du hast nicht abgegeben – die richtigen Antworten sind grün markiert.</p>
<?php
      }
    }
    if(!empty($abgabe->abgegeben_am)) {
?>
      <p><em>Zuletzt abgegeben am <?= date('d.m.Y H:i',strtotime($abgabe->abgegeben_am)) ?></em></p>
<?php
    }
?>
      </fieldset>
<?php
    if($gesperrt) {
      // Fristablauf steht schon rot bei "Fällig am" (tn/mini_kurs_sehen.php),
      // andere Gründe (z.B. MC bereits abgegeben) hier anzeigen
      if(!$this->istAbgelaufen()) {
?>
      <p class="aufgabe-gesperrt"><?= htmlspecialchars($sperrgrund) ?></p>
<?php
      }
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

  // Fragen/Antworten aus dem Bearbeiten-Formular übernehmen (Dozent):
  // Texte ändern, Fragen/Optionen löschen und hinzufügen, richtige Antwort
  // wählen. Erst wird der komplette Endzustand geprüft, nur wenn er gültig
  // ist (>=1 Frage, je >=2 Optionen und genau 1 richtige) wird geschrieben.
  // Liefert array('fehler'=>string|null, 'geaendert'=>bool). 'geaendert'
  // heißt: Bewertung kann sich ändern -> Abgaben neu bepunkten.
  //
  // Erwartete Felder:
  //   frage_text[fid], frage_loeschen[fid]
  //   option_text[oid], option_loeschen[oid]
  //   neue_option[fid][key] (key z.B. "n3")
  //   richtig[fid] = oid oder key einer neuen Option
  //   neue_frage[i][text], neue_frage[i][optionen][j], neue_frage[i][richtig]=j
  function fragenAusFormularSpeichern($post) {
    global $db;
    $fragen = $this->ladeFragen();
    $plan = array(); // Endzustand der bestehenden Fragen
    $geaendert = false;

    foreach($fragen as $frage) {
      $fid = (int)$frage->id;
      if(!empty($post['frage_loeschen'][$fid])) {
        $plan[$fid] = array('loeschen'=>true);
        $geaendert = true;
        continue;
      }
      $text = isset($post['frage_text'][$fid]) ? trim($post['frage_text'][$fid]) : $frage->frage;
      if($text==='') return array('fehler'=>'Fragetext darf nicht leer sein (Frage '.htmlspecialchars($frage->frage).').', 'geaendert'=>false);
      $richtig = isset($post['richtig'][$fid]) ? (string)$post['richtig'][$fid] : null;

      $optionen = array(); // key => text (bestehende: oid, neue: "nX")
      $loeschOptionen = array();
      foreach($frage->optionen as $o) {
        $oid = (int)$o->id;
        if(!empty($post['option_loeschen'][$oid])) {
          $loeschOptionen[] = $oid;
          continue;
        }
        $otext = isset($post['option_text'][$oid]) ? trim($post['option_text'][$oid]) : $o->text;
        if($otext==='') return array('fehler'=>'Antworttext darf nicht leer sein (Frage "'.$text.'").', 'geaendert'=>false);
        $optionen[(string)$oid] = $otext;
      }
      $neue = array();
      if(isset($post['neue_option'][$fid]) && is_array($post['neue_option'][$fid])) {
        foreach($post['neue_option'][$fid] as $key => $otext) {
          $otext = trim($otext);
          if($otext==='') continue;
          $key = 'n'.preg_replace('/\D/', '', (string)$key);
          $neue[$key] = $otext;
          $optionen[$key] = $otext;
        }
      }
      if(count($optionen) < 2) return array('fehler'=>'Frage "'.$text.'" braucht mindestens zwei Antwortoptionen.', 'geaendert'=>false);
      if($richtig!==null && strpos($richtig, 'n')===0) $richtig = 'n'.preg_replace('/\D/', '', substr($richtig, 1));
      if($richtig===null || !array_key_exists($richtig, $optionen)) {
        return array('fehler'=>'Bitte bei der Frage "'.$text.'" eine (nicht gelöschte) richtige Antwort markieren.', 'geaendert'=>false);
      }
      if(!empty($loeschOptionen) || !empty($neue)) $geaendert = true;
      $plan[$fid] = array('loeschen'=>false, 'text'=>$text, 'optionen'=>$optionen, 'neue'=>$neue, 'loeschOptionen'=>$loeschOptionen, 'richtig'=>$richtig, 'frage'=>$frage);
    }

    $neueFragen = array();
    if(isset($post['neue_frage']) && is_array($post['neue_frage'])) {
      foreach($post['neue_frage'] as $nf) {
        $text = isset($nf['text']) ? trim($nf['text']) : '';
        if($text==='') continue;
        $optionen = array();
        if(isset($nf['optionen']) && is_array($nf['optionen'])) {
          foreach($nf['optionen'] as $j => $otext) {
            $otext = trim($otext);
            if($otext!=='') $optionen[(string)$j] = $otext;
          }
        }
        if(count($optionen) < 2) return array('fehler'=>'Neue Frage "'.$text.'" braucht mindestens zwei Antwortoptionen.', 'geaendert'=>false);
        $richtig = isset($nf['richtig']) ? (string)$nf['richtig'] : null;
        if($richtig===null || !array_key_exists($richtig, $optionen)) return array('fehler'=>'Bitte bei der neuen Frage "'.$text.'" die richtige Antwort markieren.', 'geaendert'=>false);
        $neueFragen[] = array('text'=>$text, 'optionen'=>$optionen, 'richtig'=>$richtig);
        $geaendert = true;
      }
    }

    $verbleibend = count($neueFragen);
    foreach($plan as $p) if(!$p['loeschen']) $verbleibend++;
    if($verbleibend==0) return array('fehler'=>'Eine Multiple-Choice-Aufgabe braucht mindestens eine Frage.', 'geaendert'=>false);

    // --- ab hier ist alles gültig: schreiben ---
    $stmtFrageText = $db->prepare("update gpb_aufgabe_frage set frage=? where id=?");
    $stmtOptUpd = $db->prepare("update gpb_aufgabe_option set text=?, ist_richtig=? where id=?");
    $stmtOptNeu = $db->prepare("insert into gpb_aufgabe_option (frageid,text,ist_richtig,reihenfolge) values (?,?,?,?)");
    foreach($plan as $fid => $p) {
      if($p['loeschen']) {
        $db->query("delete from gpb_aufgabe_abgabe_antwort where frageid=".$fid);
        $db->query("delete from gpb_aufgabe_option where frageid=".$fid);
        $db->query("delete from gpb_aufgabe_frage where id=".$fid." and aufgabeid=".intval($this->id));
        continue;
      }
      if($p['text']!==$p['frage']->frage) {
        $stmtFrageText->bind_param('si', $p['text'], $fid);
        $stmtFrageText->execute();
      }
      if(!empty($p['loeschOptionen'])) {
        $liste = implode(',', array_map('intval', $p['loeschOptionen']));
        $db->query("delete from gpb_aufgabe_abgabe_antwort where optionid in (".$liste.")");
        $db->query("delete from gpb_aufgabe_option where id in (".$liste.") and frageid=".$fid);
      }
      $maxReihenfolge = 0;
      foreach($p['frage']->optionen as $o) {
        $maxReihenfolge = max($maxReihenfolge, (int)$o->reihenfolge);
        if(in_array((int)$o->id, $p['loeschOptionen'])) continue;
        $neuText = $p['optionen'][(string)$o->id];
        $neuRichtig = $p['richtig']===(string)$o->id ? 1 : 0;
        if($neuText!==$o->text || $neuRichtig!==(int)$o->ist_richtig) {
          if($neuRichtig!==(int)$o->ist_richtig) $geaendert = true;
          $stmtOptUpd->bind_param('sii', $neuText, $neuRichtig, $o->id);
          $stmtOptUpd->execute();
        }
      }
      foreach($p['neue'] as $key => $otext) {
        $maxReihenfolge++;
        $istRichtig = $p['richtig']===$key ? 1 : 0;
        $stmtOptNeu->bind_param('isii', $fid, $otext, $istRichtig, $maxReihenfolge);
        $stmtOptNeu->execute();
      }
    }

    if(!empty($neueFragen)) {
      $result = $db->query("select coalesce(max(reihenfolge),0) m from gpb_aufgabe_frage where aufgabeid=".intval($this->id));
      $reihenfolge = (int)$result->fetch_object()->m;
      $result->free();
      $stmtFrageNeu = $db->prepare("insert into gpb_aufgabe_frage (aufgabeid,frage,reihenfolge) values (?,?,?)");
      foreach($neueFragen as $nf) {
        $reihenfolge++;
        $stmtFrageNeu->bind_param('isi', $this->id, $nf['text'], $reihenfolge);
        $stmtFrageNeu->execute();
        $neueFid = $stmtFrageNeu->insert_id;
        $optR = 0;
        foreach($nf['optionen'] as $j => $otext) {
          $optR++;
          $istRichtig = $nf['richtig']===(string)$j ? 1 : 0;
          $stmtOptNeu->bind_param('isii', $neueFid, $otext, $istRichtig, $optR);
          $stmtOptNeu->execute();
        }
      }
      $stmtFrageNeu->close();
    }
    $stmtFrageText->close();
    $stmtOptUpd->close();
    $stmtOptNeu->close();

    return array('fehler'=>null, 'geaendert'=>$geaendert);
  }

  // Abgaben-Anzahl (für Hinweise beim Bearbeiten)
  function anzahlAbgaben() {
    global $db;
    $result = $db->query("select count(*) c from gpb_aufgabe_abgabe where aufgabeid=".intval($this->id));
    $c = (int)$result->fetch_object()->c;
    $result->free();
    return $c;
  }

  // Multiple Choice ist nur EINMAL abgebbar - sonst könnte der TN nach
  // Anzeige der Punkte so lange neu abgeben, bis alles richtig ist.
  function abgabeSperrgrund($tnid) {
    $grund = parent::abgabeSperrgrund($tnid);
    if($grund!==null) return $grund;
    if($this->ladeAbgabe($tnid)) {
      return 'Bereits abgegeben – bei Multiple Choice ist nur eine Abgabe möglich.';
    }
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
