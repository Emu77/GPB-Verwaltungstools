<?php
// Basisklasse für alle Aufgabentypen (Test/Aufgabe).
// Konkrete Typen: AufgabeTextUpload, AufgabeMultipleChoice (jeweils eigene Datei).
class Aufgabe {

  var $id, $kursid, $typ, $titel, $beschreibung, $punkteMax, $sichtbarAb, $faelligAm, $erstelltVon, $erstelltAm;

  // Mapping typ => Klassenname. Neue Typen hier eintragen.
  static $typKlassen = array(
    'text_upload' => 'AufgabeTextUpload',
    'multiple_choice' => 'AufgabeMultipleChoice',
  );

  function init() {
  }

  // liefert eine Instanz der passenden Subklasse für eine DB-Zeile
  static function fromRow($row) {
    $klasse = isset(self::$typKlassen[$row->typ]) ? self::$typKlassen[$row->typ] : 'Aufgabe';
    $a = new $klasse();
    $a->id = $row->id;
    $a->kursid = $row->kursid;
    $a->typ = $row->typ;
    $a->titel = $row->titel;
    $a->beschreibung = $row->beschreibung;
    $a->punkteMax = $row->punkte_max;
    $a->sichtbarAb = $row->sichtbar_ab;
    $a->faelligAm = $row->faellig_am;
    $a->erstelltVon = $row->erstellt_von;
    $a->erstelltAm = $row->erstellt_am;
    $a->init();
    return $a;
  }

  static function laden($id) {
    global $db;
    $result = $db->query("select * from gpb_aufgabe where id=".intval($id));
    $row = $result->fetch_object();
    $result->free();
    if(!$row) return null;
    return self::fromRow($row);
  }

  static function ladenFuerKurs($kursid) {
    global $db;
    $aufgaben = array();
    $result = $db->query("select * from gpb_aufgabe where kursid=".intval($kursid)." order by sichtbar_ab, id");
    while($row = $result->fetch_object()) {
      $aufgaben[] = self::fromRow($row);
    }
    $result->free();
    return $aufgaben;
  }

  function speichernNeu() {
    global $db;
    $stmt=$db->prepare("insert into gpb_aufgabe (kursid,typ,titel,beschreibung,punkte_max,sichtbar_ab,faellig_am,erstellt_von,erstellt_am) values (?,?,?,?,?,?,?,?,now())");
    $stmt->bind_param('isssdssi',
      $this->kursid,
      $this->typ,
      $this->titel,
      $this->beschreibung,
      $this->punkteMax,
      $this->sichtbarAb,
      $this->faelligAm,
      $this->erstelltVon);
    $stmt->execute();
    $this->id = $stmt->insert_id;
    $stmt->close();
  }

  function speichernUpdate() {
    global $db;
    $stmt=$db->prepare("update gpb_aufgabe set titel=?,beschreibung=?,punkte_max=?,sichtbar_ab=?,faellig_am=? where id=? limit 1");
    $stmt->bind_param('ssdssi',
      $this->titel,
      $this->beschreibung,
      $this->punkteMax,
      $this->sichtbarAb,
      $this->faelligAm,
      $this->id);
    $stmt->execute();
    $stmt->close();
  }

  // Umrechnung zwischen DB-Format (Y-m-d H:i:s) und <input type="datetime-local">
  static function datumAusFormular($wert) {
    if(empty($wert)) return null;
    return str_replace('T', ' ', $wert).':00';
  }
  static function datumFuerFormular($wert) {
    if(empty($wert)) return '';
    return date('Y-m-d\TH:i', strtotime($wert));
  }

  // Punktzahl deutsch formatieren, ohne überflüssige Nachkommastellen
  // (10 statt 10,00; 7,5 statt 7,50)
  static function punkteFormat($punkte) {
    if($punkte===null || $punkte==='') return '';
    return rtrim(rtrim(number_format((float)$punkte, 2, ',', ''), '0'), ',');
  }

  // true, wenn ein Fälligkeitsdatum gesetzt und bereits überschritten ist -
  // dann nimmt die Aufgabe keine (neuen/geänderten) Abgaben mehr an.
  function istAbgelaufen() {
    return !empty($this->faelligAm) && strtotime($this->faelligAm) < time();
  }

  // Grund, warum der TN (nicht mehr) abgeben darf, oder null wenn Abgabe
  // möglich ist. Subklassen können weitere Gründe ergänzen (z.B. MC: nur
  // eine Abgabe). Wird vom Formular UND serverseitig beim Speichern genutzt.
  function abgabeSperrgrund($tnid) {
    if($this->istAbgelaufen()) {
      return 'Abgabefrist abgelaufen – keine Abgabe/Änderung mehr möglich.';
    }
    return null;
  }

  // true, wenn die Aufgabe für TN schon sichtbar ist
  function istSichtbar() {
    return empty($this->sichtbarAb) || strtotime($this->sichtbarAb) <= time();
  }

  // Bewertung zu einer Abgabe laden (oder null)
  function ladeBewertung($abgabeid) {
    global $db;
    if(empty($abgabeid)) return null;
    $result = $db->query("select * from gpb_aufgabe_bewertung where abgabeid=".intval($abgabeid));
    $row = $result->fetch_object();
    $result->free();
    return $row ? $row : null;
  }

  // Bewertung für den TN anzeigen (nur wenn schon etwas bewertet wurde)
  function makeBewertungFuerTn($tnid) {
    $abgabe = $this->ladeAbgabe($tnid);
    $bw = $this->ladeBewertung($abgabe->id ?? null);
    if(empty($bw) || ($bw->punkte===null && $bw->note===null && $bw->kommentar===null)) return;
?>
    <div class="aufgabe-bewertung">
      <strong>Bewertung:</strong>
<?php if($bw->punkte!==null): ?>
      <?= self::punkteFormat($bw->punkte) ?><?= $this->punkteMax!==null ? ' von '.self::punkteFormat($this->punkteMax) : '' ?> Punkte<?= $bw->note!==null ? ',' : '' ?>
<?php endif; ?>
<?php if($bw->note!==null): ?>
      Note <?= htmlspecialchars($bw->note) ?>
<?php endif; ?>
<?php if($bw->kommentar!==null): ?>
      <div class="aufgabe-bewertung-kommentar"><?= nl2br(htmlspecialchars($bw->kommentar)) ?></div>
<?php endif; ?>
    </div>
<?php
  }

  // Einzelne Abgabe eines TN zurücksetzen (Dozent): Bewertung, MC-Antworten,
  // hochgeladene Datei und die Abgabe selbst werden gelöscht. Danach kann der
  // TN neu abgeben (z.B. wenn er MC versehentlich zu früh abgeschickt hat).
  // Liefert false, wenn die Abgabe nicht zu dieser Aufgabe gehört.
  function abgabeZuruecksetzen($abgabeid) {
    global $db;
    $abgabeid = intval($abgabeid);
    $result = $db->query("select id, gespeicherter_dateiname from gpb_aufgabe_abgabe where id=".$abgabeid." and aufgabeid=".intval($this->id));
    $abgabe = $result->fetch_object();
    $result->free();
    if(!$abgabe) return false;

    $db->query("delete from gpb_aufgabe_bewertung where abgabeid=".$abgabeid);
    $db->query("delete from gpb_aufgabe_abgabe_antwort where abgabeid=".$abgabeid);
    $db->query("delete from gpb_aufgabe_abgabe where id=".$abgabeid." limit 1");

    if(!empty($abgabe->gespeicherter_dateiname)) {
      $pfad = dirname(__FILE__).'/uploads/aufgabe_abgaben/'.intval($this->id).'/'.basename($abgabe->gespeicherter_dateiname);
      if(is_file($pfad)) unlink($pfad);
    }
    return true;
  }

  // Aufgabe komplett löschen: Bewertungen, Antworten, Abgaben (inkl.
  // hochgeladener Dateien), Fragen/Optionen und die Aufgabe selbst.
  // Explizit statt per ON DELETE CASCADE, damit auch die Dateien auf der
  // Platte mit verschwinden.
  function loeschen() {
    global $db;
    $id = intval($this->id);

    $abgabeids = array();
    $result = $db->query("select id from gpb_aufgabe_abgabe where aufgabeid=".$id);
    while($row = $result->fetch_object()) {
      $abgabeids[] = (int)$row->id;
    }
    $result->free();

    if(!empty($abgabeids)) {
      $liste = implode(',', $abgabeids);
      $db->query("delete from gpb_aufgabe_bewertung where abgabeid in (".$liste.")");
      $db->query("delete from gpb_aufgabe_abgabe_antwort where abgabeid in (".$liste.")");
      $db->query("delete from gpb_aufgabe_abgabe where id in (".$liste.")");
    }

    $frageids = array();
    $result = $db->query("select id from gpb_aufgabe_frage where aufgabeid=".$id);
    while($row = $result->fetch_object()) {
      $frageids[] = (int)$row->id;
    }
    $result->free();
    if(!empty($frageids)) {
      $db->query("delete from gpb_aufgabe_option where frageid in (".implode(',', $frageids).")");
      $db->query("delete from gpb_aufgabe_frage where aufgabeid=".$id);
    }

    $db->query("delete from gpb_aufgabe where id=".$id." limit 1");

    // Upload-Ordner der Aufgabe (nur Text/Upload hat einen) samt Inhalt entfernen
    $ordner = dirname(__FILE__).'/uploads/aufgabe_abgaben/'.$id;
    if(is_dir($ordner)) {
      foreach(glob($ordner.'/*') ?: array() as $datei) {
        if(is_file($datei)) unlink($datei);
      }
      @rmdir($ordner);
    }
  }

  // Abgabe eines TN laden (oder null wenn noch keine existiert)
  function ladeAbgabe($tnid) {
    global $db;
    $result = $db->query("select * from gpb_aufgabe_abgabe where aufgabeid=".intval($this->id)." and tnid=".intval($tnid));
    $row = $result->fetch_object();
    $result->free();
    return $row;
  }

  // Alle TN des Kurses inkl. ihrer Abgabe und Bewertung zu dieser Aufgabe
  // (Abgabe/Bewertung sind null-Felder, wenn der TN noch nichts abgegeben hat).
  // Für die Dozenten-Notenansicht.
  function ladeTeilnehmerMitAbgabe() {
    global $db;
    $result = $db->query("select beginn,ende from gpb_kurs where id=".intval($this->kursid));
    $kurs = $result->fetch_object();
    $result->free();
    if(empty($kurs)) return array();

    $result = $db->query(
      "select distinct tn.id as tnid, tn.vorname, tn.nachname,
        ab.id as abgabeid, ab.text, ab.dateiname, ab.gespeicherter_dateiname, ab.groesse, ab.abgegeben_am,
        bw.punkte, bw.note, bw.kommentar, bw.bewertet_am
      from gpb_kurs_klasse kk
      join gpb_klasse_tn ktn on ktn.klasseid=kk.klasseid
        and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<='".$db->real_escape_string($kurs->ende)."')
        and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>='".$db->real_escape_string($kurs->beginn)."')
      join gpb_tn tn on tn.id=ktn.tnid
      left join gpb_aufgabe_abgabe ab on ab.aufgabeid=".intval($this->id)." and ab.tnid=tn.id
      left join gpb_aufgabe_bewertung bw on bw.abgabeid=ab.id
      where kk.kursid=".intval($this->kursid)."
      order by tn.nachname, tn.vorname"
    );
    $liste = array();
    while($row = $result->fetch_object()) {
      $liste[] = $row;
    }
    $result->free();
    return $liste;
  }

  // --- ab hier von Subklassen zu überschreiben ---

  // Eingabeformular für den TN (typabhängig: Text/Upload-Felder oder MC-Fragen)
  function makeAbgabeformular($tnid) {
?>
    <p><em>Aufgabentyp "<?= htmlentities($this->typ) ?>" noch nicht implementiert.</em></p>
<?php
  }

  // Abgabe eines TN speichern, typabhängig ($_POST/$_FILES auswerten)
  function speichereAbgabe($tnid) {
    // in Subklasse überschreiben
  }

  // true, wenn die Aufgabe automatisch bewertet werden kann (z.B. Multiple Choice)
  function kannAutomatischBewerten() {
    return false;
  }

  // führt die automatische Bewertung durch und schreibt sie nach gpb_aufgabe_bewertung
  function automatischBewerten($abgabeid) {
    // in Subklasse überschreiben (nur wenn kannAutomatischBewerten()==true)
  }
}
?>
