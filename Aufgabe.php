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
