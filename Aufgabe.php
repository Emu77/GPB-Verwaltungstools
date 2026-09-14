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
