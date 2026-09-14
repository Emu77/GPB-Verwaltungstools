<?php
require_once 'Aufgabe.php';
class AufgabeTextUpload extends Aufgabe {

  // Ordner für Uploads, geschützt durch .htaccess ("deny from all") -
  // Download muss über ein access-geprüftes PHP-Script laufen (siehe
  // aufgabe_anhang_download.php), analog zu MiniAnhang.php/mini_anhang_download.php.
  static function uploadOrdner($aufgabeid) {
    return dirname(__FILE__).'/uploads/aufgabe_abgaben/'.(int)$aufgabeid;
  }

  function uploadOrdnerVonMir() {
    return self::uploadOrdner($this->id);
  }

  static function erlaubteEndungen() {
    return array(
      'jpg','jpeg','png','svg','gif','webp', // Bilder
      'pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip', // Dokumente
    );
  }

  static function maxBytes() {
    return 15*1024*1024; // 15 MB
  }

  // Zufälliger, nicht erratbarer Dateiname für die Platte (Original-Name
  // bleibt nur in der DB und wird beim Download wieder verwendet)
  static function zufallsDateiname($originalName) {
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $extSauber = preg_replace('/[^A-Za-z0-9]/', '', $ext);
    return bin2hex(random_bytes(16)).($extSauber!=='' ? '.'.$extSauber : '');
  }

  function makeAbgabeformular($tnid) {
    $abgabe = $this->ladeAbgabe($tnid);
?>
    <form method="post" enctype="multipart/form-data" action="aufgabe_abgabe_speichern.php">
      <input type="hidden" name="aufgabeid" value="<?= $this->id ?>" />
      <input type="hidden" name="kursid" value="<?= $this->kursid ?>" />
      <p>
        <label for="aufgabe_text">Antwort:</label><br />
        <textarea id="aufgabe_text" name="aufgabe_text" rows="6" cols="60"><?= htmlentities($abgabe->text ?? '') ?></textarea>
      </p>
      <p>
        <label for="aufgabe_datei">Datei hochladen (optional, max. 15 MB):</label>
        <input type="file" id="aufgabe_datei" name="aufgabe_datei" />
<?php
    if(!empty($abgabe->dateiname)) {
?>
        <br /><em>Bereits hochgeladen: <a href="../aufgabe_anhang_download.php?abgabeid=<?= $abgabe->id ?>"><?= htmlentities($abgabe->dateiname) ?></a></em>
<?php
    }
?>
      </p>
<?php
    if(!empty($abgabe->abgegeben_am)) {
?>
      <p><em>Zuletzt abgegeben am <?= date('d.m.Y H:i',strtotime($abgabe->abgegeben_am)) ?></em></p>
<?php
    }
?>
      <p><button type="submit">Abgeben</button></p>
    </form>
<?php
  }

  // liefert Fehlermeldung oder null bei Erfolg
  function speichereAbgabe($tnid) {
    global $db;

    $text = isset($_POST['aufgabe_text']) ? trim($_POST['aufgabe_text']) : '';
    $bestehend = $this->ladeAbgabe($tnid);

    $dateiname = $bestehend->dateiname ?? null;
    $gespeichert = $bestehend->gespeicherter_dateiname ?? null;
    $groesse = $bestehend->groesse ?? null;
    $altGespeichert = null; // wird nur gesetzt, wenn eine alte Datei ersetzt wird

    $hatDatei = isset($_FILES['aufgabe_datei']) && $_FILES['aufgabe_datei']['error'] != UPLOAD_ERR_NO_FILE;
    if($hatDatei) {
      $originalName = basename($_FILES['aufgabe_datei']['name']);
      $endung = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

      if($_FILES['aufgabe_datei']['error'] != UPLOAD_ERR_OK) {
        $fehlerCode = $_FILES['aufgabe_datei']['error'];
        error_log('Aufgabe-Upload: Fehler-Code='.$fehlerCode);
        if($fehlerCode==UPLOAD_ERR_INI_SIZE || $fehlerCode==UPLOAD_ERR_FORM_SIZE) {
          return 'Datei ist zu groß.';
        }
        return 'Datei konnte nicht hochgeladen werden.';
      }
      if(!in_array($endung, self::erlaubteEndungen(), true)) {
        return 'Dateityp ".'.htmlspecialchars($endung).'" ist nicht erlaubt. Erlaubt: '.implode(', ', self::erlaubteEndungen()).'.';
      }
      if((int)$_FILES['aufgabe_datei']['size'] > self::maxBytes()) {
        return 'Datei ist zu groß (max. '.round(self::maxBytes()/1024/1024).' MB).';
      }

      $neuGroesse = (int)$_FILES['aufgabe_datei']['size'];
      $neuGespeichert = self::zufallsDateiname($originalName);
      $ordner = $this->uploadOrdnerVonMir();
      if(!is_dir($ordner) && !mkdir($ordner, 0755, true) && !is_dir($ordner)) {
        error_log('Aufgabe-Upload: Konnte Ordner nicht anlegen: '.$ordner);
        return 'Datei konnte nicht gespeichert werden.';
      }
      if(!move_uploaded_file($_FILES['aufgabe_datei']['tmp_name'], $ordner.'/'.$neuGespeichert)) {
        error_log('Aufgabe-Upload: move_uploaded_file fehlgeschlagen nach '.$ordner.'/'.$neuGespeichert);
        return 'Datei konnte nicht gespeichert werden.';
      }

      $altGespeichert = $gespeichert; // alte Datei nach erfolgreichem DB-Update löschen
      $dateiname = $originalName;
      $gespeichert = $neuGespeichert;
      $groesse = $neuGroesse;
    }

    if($text==='' && $gespeichert===null) {
      return 'Bitte Text eingeben oder Datei hochladen.';
    }

    $stmt = $db->prepare("insert into gpb_aufgabe_abgabe (aufgabeid,tnid,text,dateiname,gespeicherter_dateiname,groesse,abgegeben_am)
      values (?,?,?,?,?,?,now())
      on duplicate key update text=values(text), dateiname=values(dateiname), gespeicherter_dateiname=values(gespeicherter_dateiname), groesse=values(groesse), abgegeben_am=values(abgegeben_am)");
    $stmt->bind_param('iisssi', $this->id, $tnid, $text, $dateiname, $gespeichert, $groesse);
    $stmt->execute();
    $stmt->close();

    if($altGespeichert!==null) {
      $alterPfad = $this->uploadOrdnerVonMir().'/'.$altGespeichert;
      if(is_file($alterPfad)) {
        unlink($alterPfad);
      }
    }

    return null;
  }
}
?>
