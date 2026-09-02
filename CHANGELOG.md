# Changelog

## 2026-09

### Repo-Migration
- Kompletter Inhalt des Repos `gpb_praktikum` (inkl. vollständiger Git-Historie) nach `GPB-Verwaltungstools` verschoben (`git push --mirror`), passend zur Umbenennung der Anwendung von "gpb_praktikum" auf "GPB Verwaltungstools" (Betreuer-Feedback, siehe Projektantrag). Lokaler Remote `origin` entsprechend umgestellt (SSH statt HTTPS).
- Altes Repo `gpb_praktikum` nach Verifikation identischer Commit-Historie gelöscht.

### Mini: Custom-WYSIWYG-Editor (IHK-Projektarbeit, Phase 5 – Testing)
- 34 Testfälle durchgeführt und dokumentiert (`docs/Testprotokoll_WYSIWYG_Editor.md`): 13 Funktionstests, 3 Browsertests Desktop (Chrome/Firefox/Edge), 3 Browsertests mobile/responsive, 15 XSS-Testfälle gegen die Sanitisierung. Alle 34 bestanden.
- Während der Testphase gefundener Bug (Testfall X9: unvollständige rekursive Bereinigung beim Hochziehen von Kindelementen in `MiniHtmlSanitizer.php`) behoben und erneut verifiziert.

### Mini: Custom-WYSIWYG-Editor – Kann-Kriterien
- Tastenkombinationen (Strg+B/I/U) bei Durchsicht als bereits in Phase 2 mitimplementiert festgestellt (`bindKeyboardShortcuts()` in `mini_wysiwyg.js`) – kein zusätzlicher Aufwand nötig.
- Responsives Toolbar-Layout ergänzt: CSS-Media-Query (`max-width: 480px`) in `mini_wysiwyg.css` – größere Tap-Ziele für die Toolbar-Buttons, Text-Label des Listen-Buttons wird auf schmalen Screens ausgeblendet (nur Symbol + Tooltip bleiben). Reaktion auf die in Testfall M2 (320×480) dokumentierte Beobachtung enger Toolbar-Buttons.
- Undo/Redo weiterhin bewusst nicht umgesetzt (eigene History-Verwaltung wäre bei der Range-API-basierten Formatierungslogik unverhältnismäßig aufwändig gewesen).

### Bugfix: Bearbeiten-Link auf schmalen Screens
- In `dozent/mini_kurs_sehen.php` fehlte in der Kopfzeile jedes Paragraphen (Titel + Auf/Ab/Bearbeiten/Löschen-Buttons) ein `flex-wrap`; bei sehr schmalen Viewports wurde der "Paragraph bearbeiten"-Link durch `justify-content:space-between` aus dem sichtbaren Bereich geschoben, ohne umzubrechen. Behoben durch `flex-wrap:wrap` plus `gap`.

## 2026-08

### Serverseitige HTML-Sanitisierung (Mini-WYSIWYG-Editor)
- Neue Datei `MiniHtmlSanitizer.php` mit `gpbMiniHtmlSaeubern()`: Whitelist-basierte Sanitisierung des vom Mini-WYSIWYG-Editor gelieferten HTML-Inhalts auf DOMDocument-Basis (kein HTMLPurifier, da nicht praktikabel einzubinden).
- Erlaubte Tags: `strong`, `em`, `u`, `span` (nur `style="color:..."`), `ul`, `ol`, `li`, `p`, `br`, `img` (nur `src`, `alt`). Alle anderen Tags werden entfernt, ihr Inhalt bleibt (nach rekursiver Bereinigung) erhalten.
- `<script>`, `<iframe>`, `<object>`, `<style>`, `<embed>`, `<link>`, `<meta>` werden inkl. Inhalt vollständig entfernt.
- Event-Handler-Attribute (`onclick`, `onload`, `onmouseover` etc.) sowie alle nicht auf der Whitelist stehenden Attribute werden entfernt.
- `img`-Quellen: nur eigene Pfade zu `mini_anhang_download.php` erlaubt, externe URLs sowie `javascript:`-/`data:`-URIs werden abgelehnt.
- Bugfix während der Testphase: Kinder eines entfernten, nicht erlaubten Tags wurden beim Hochziehen nicht rekursiv mitgesäubert (z. B. `<b>` in `<div>` entkam der Prüfung) – behoben durch rekursiven Aufruf vor dem Hochziehen.
- Einbindung in `dozent/mini_kurs_sehen.php`: Sanitisierung läuft bei den Aktionen `hinzufuegen` und `speichern` direkt vor dem Schreiben in `gpb_mini_paragraph` (nach `gpbMiniNormalisieren()`).
- Getestet: lokal per PHP-CLI-Testfällen (Script-Injection, Event-Handler, `javascript:`-URI, externe Bildquelle, verschachtelte nicht erlaubte Tags, erlaubte Formatierungen) sowie live im Dozentenbereich (XSS-Payload im Formular eingegeben, gespeichert, Anzeige und gespeicherter Rohcode geprüft).

### Mini: Custom-WYSIWYG-Editor – Live-Integration
- Editor produktiv in `dozent/mini_kurs_sehen.php` eingebunden: TinyMCE-CDN-Script entfernt, stattdessen `mini_wysiwyg.js`/`mini_wysiwyg.css` (Objekt `MiniWysiwyg`).
- `miniWysiwygAn()`/`miniWysiwygAus()`, Submit-Sync, Vorschau-Befüllung und "Bild einfügen" auf `MiniWysiwyg.init()`/`.destroy()`/`.getContent()`/`.insertContent()` umgestellt statt `tinymce.init()`/`.get()`/`.remove()`; alter Plain-Text-Toggle (ohne WYSIWYG) unverändert funktionsfähig.
- Live auf kronisoft.net getestet: Formatierung, Bild einfügen, Vorschau, Speichern laufen fehlerfrei.

### Sicherheit
- `secret/`-Ordner (u. a. Klartext-Passwort für den `gpbintern`-Basic-Auth-Account in `intern_pwd.php`/`.internpass`) war rund 2 Monate lang im öffentlichen GitHub-Repo einsehbar. Ordner lokal gelöscht, per `git filter-repo --path secret/ --invert-paths` aus der kompletten Git-Historie entfernt und per `git push --force` überschrieben. `.gitignore` um `secret/` ergänzt.
- Zusätzlich `.gitignore` um `*_alt`/`*_alt.*` ergänzt (lokale Backup-Dateien wie `mini_kurs_sehen.php_alt` sollen nicht versioniert werden).

### Mini: Custom-WYSIWYG-Editor (IHK-Projektarbeit, Phase 2 – Editor-Grundgerüst)
- Neues eigenständiges Editor-Modul als Ersatz für TinyMCE (siehe `docs/Projektantrag_gpb_WYSIWYG_Editor.pdf`, `docs/Umsetzungs_Zeitplan_WYSIWYG_Editor.pdf`): HTML-Grundgerüst (contenteditable-Fläche + Toolbar-Markup), Selection-/Range-Zugriff, Formatierungsfunktionen Fett/Kursiv/Unterstrichen/Farbe inkl. Toggle-Logik.
- Kein `document.execCommand()` (deprecated) – Formatierung läuft über eigene Range-API-Logik, analog zum Prinzip der bestehenden Plain-Text-Toolbar (`miniFormatToggle()`).
- Mehrere Bugfixes während der Standalone-Tests:
  - Leere Tag-Hüllen (`<strong></strong>`) nach einem Toggle bereinigt (`cleanupEmptyInlineTags()`).
  - Doppelt verschachtelte identische Tags zusammengeführt (`mergeRedundantNesting()`).
  - Selektionsverlust beim allerersten Formatierungs-Klick behoben: `editor.focus()` setzte die gerade getroffene Markierung zurück, solange die Editor-Fläche noch nie echten Fokus hatte; Selektion wird jetzt vorher gesichert und danach bei Bedarf wiederhergestellt.
  - Farb-Buttons ersetzen jetzt die bestehende Farbe einer Markierung, statt eine weitere `<span>` zu verschachteln (Toggle aus bei erneutem Klick auf dieselbe Farbe).
- Alle Fixes mit automatisierten jsdom-Tests abgesichert, nicht nur manuell im Browser geprüft.

### Mini (Todos & PropertySheet)
- Auf der Mini-Kurs-Seite selbst (TN und Dozent) wird die allgemeine Todo-Box nicht mehr angezeigt (`$miniSeiteOhneTodos`); auf allen anderen Seiten ist sie jetzt standardmäßig aufgeklappt (`<details open>`) statt eingeklappt.
- "Weniger anzeigen"-Button auf der Mini-Seite schaltet jetzt zwischen zwei komplett generierten Tabellen um (`Kurs::makeSehenMitKompaktToggle()`/`makeSehenVollesTabelle()`/`makeSehenKompakteTabelle()`), statt nur einzelne Zeilen zu verstecken: Kompakt fasst KW/Beginn/Ende zu "Kurszeitraum" zusammen, Ort+Raum in eine Zeile, Klassen und Dozenten stehen nebeneinander. Umgesetzt für Dozent (`Kurs.php`) und TN (eigene Kopie in `tn/TnKurs.php`, da TN dort `makeSehen()` komplett überschreibt).
- Selbstreferenzielle "Sehen/Mini/Bewerten"-Aktionszeile wird in der vollen Tabelle unterdrückt, wenn man sich bereits auf der Mini-Seite selbst befindet (`$miniSeiteVersteckeAktionszeile`).
- Bugfixes am Kompakt-Toggle: Beschriftung "mehr"/"weniger anzeigen" war anfangs vertauscht; kompakte Tabelle war durch ein eigenes `display:none` plus das Verstecken der vollen Tabelle anfangs komplett unsichtbar (musste explizit per Script wieder eingeblendet werden).
- `VerwaltungKurs::makeSehen()`/`LeitungKurs::makeSehen()`-Signatur an das neue `Kurs::makeSehen($classname, $kompaktToggle)` angepasst (PHP-Warning wegen inkompatibler Methoden-Signatur behoben).

### Mini (Anhänge & Bilder)
- Bild-Anhänge (jpg/jpeg/png/svg/gif/webp) werden jetzt als kleines Vorschaubild angezeigt statt als reiner Download-Link; Klick vergrößert/verkleinert per JavaScript (`MiniAnhang.php: miniAnhangAnzeigen()`, CSS in den jeweiligen rollenspezifischen `styles.css`). Umgesetzt für TN, Dozent und Verwaltung.
- `mini_anhang_download.php` liefert Bilder jetzt mit korrektem `Content-Type` und `Content-Disposition: inline` statt als erzwungenen Download aus (Voraussetzung für die `<img>`-Vorschau).
- Neuer Button "Bild einfügen" im Paragraph-Formular: `<select>` mit allen Bild-Anhängen des Paragraphen, fügt beim Klick ein `<img>`-Tag an der Cursorposition ein (funktioniert sowohl im normalen Textfeld als auch mit aktivem WYSIWYG-Editor).
- Upload-Validierung: Dateityp-Whitelist (Bilder + PDF/Office/TXT/ZIP) und 15-MB-Größenlimit mit klarer Fehlermeldung statt nur Eintrag im `php_error_log`.
- Neue zentrale Anhang-Übersicht pro Kurs (aufklappbar, Text wechselt dynamisch zwischen "aufklappen"/"zuklappen" wie bei den Todos): listet alle Anhänge aller Paragraphen mit Paragraph-Link, Vorschau, Größe, Datum und Lösch-Button.
- Verwendet/verwaist-Erkennung für Bild-Anhänge: prüft per Regex, ob die Download-URL des Bildes im Paragraph-Text vorkommt (also per "Bild einfügen" tatsächlich verwendet wurde). Datei-Anhänge (PDF, Office, …) werden nicht als verwaist markiert, da sie sich nicht in den Text einfügen lassen.
- Paragraph-Löschen entfernt jetzt auch alle zugehörigen Anhänge (Datei + DB-Zeile), nicht nur den Paragraph selbst; Lösch-Button steht jetzt sowohl in der Ansicht als auch direkt im Bearbeiten-Formular.

### Mini (Editor)
- Bearbeiten-Formular hat jetzt einen hellgelben Hintergrund, die Eingabefelder selbst bleiben weiß (`.mini-paragraph-bearbeiten-formular`).
- Vorschau nutzt jetzt dieselbe CSS-Klasse (`.mini-inhalt`) wie die spätere echte Anzeige (kein eigenes Gelb/Rahmen-Styling mehr) und hat einen separaten "Vorschau aktualisieren"-Button neben "Vorschau ausblenden".
- Erfolgsmeldungen laufen jetzt über die Session statt über URL-Parameter und werden nur noch angezeigt, wenn das Ergebnis nicht ohnehin sofort sichtbar ist (z. B. "Paragraph gespeichert" bei "Speichern und weiter bearbeiten"; kein Hinweis mehr bei Hinzufügen/Löschen/Hoch-/Runterladen, da das Ergebnis direkt auf der Seite zu sehen ist).
- WYSIWYG-Editor (TinyMCE) pro Paragraph ein-/ausschaltbar über eine Checkbox; Wahl wird im Browser gemerkt (`localStorage`). Eigene Fett/Kursiv/…-Buttons werden bei aktivem TinyMCE ausgeblendet, "Bild einfügen" und Vorschau funktionieren in beiden Modi. Eingebunden über die tiny.cloud-CDN mit Emus eigenem kostenlosem API-Key (`kronisoft.net` unter "Approved Domains" freigeschaltet).

### Datenbank
- `gpb_mini_paragraph.id` hatte kein `AUTO_INCREMENT`/`PRIMARY KEY` und vergab bei jedem Insert `id=0`, was zu Dopplungen und falschem Löschen führte; Spalte nachträglich korrigiert (`ALTER TABLE gpb_mini_paragraph MODIFY id INT(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (id)`), betroffene Testzeilen bereinigt.

## 2026-07

### TODO-Liste (Teilnehmer & Dozent)
- Die "TODO"-Tabelle mit offenen Bewertungen/Berichten wird jetzt standardmäßig eingeklappt angezeigt, statt immer vollständig sichtbar zu sein (`tn/TnSeite.php`, `dozent/DozentSeite.php`).
- Umsetzung über natives HTML `<details>`/`<summary>`, ganz ohne JavaScript.
- Der Klapptext zeigt die aktuelle Anzahl offener Todos an (z. B. "59 Todos aufklappen" / "1 Todo aufklappen") und wechselt je nach Zustand automatisch zwischen "aufklappen" und "zuklappen" (reines CSS über den `details[open]`-Selektor).

### Login / Deployment
- `db.php` um eine Umgebungs-Weiche ergänzt: lädt je nach `$_SERVER['SERVER_NAME']` automatisch `db.local.php` (lokal) oder `db.prod.php` (Produktion), statt fest codierter Zugangsdaten in einer einzigen Datei.
- MySQL-Passwort für den Live-Nutzer `web32` erneuert und in `db.prod.php`/`db.php` synchronisiert.
- Ursache für gescheiterte Teilnehmer-Logins geklärt: kein Code-Fehler, sondern Tests mit nicht existierenden Nutzernamen; mit vorhandenen Testnutzern (`gpb_tn`, Passwort `tn`) funktioniert der Login korrekt.

### Kurs-PropertySheet
- Auf-/Zuklapp-Button für die PropertySheet der Kursseite ergänzt (`Kurs.php`, `tn/TnKurs.php`, `verwaltung/VerwaltungKurs.php`, `leitung/LeitungKurs.php`).
- Jede Zeile der PropertySheet bekommt in `makeSehen()` eine eigene ID; Zusatzzeilen zusätzlich die Klasse `kurs-ps-mehr`.
- Standardmäßig eingeklappt: nur Titel, Zeitraum (KW/Beginn/Ende), Klassen, Dozenten und die Aktionsleiste sind sichtbar. Der Rest (Ort, Raum, Anzahl TN, Moodle/Mini, Zeugnis-Modul, und bei Verwaltung/Leitung zusätzlich PV, IHK-Projektantrag, Planungsnotiz, Noten, Bewertung u. a.) lässt sich per Button ein-/ausblenden.
- Ohne JavaScript bleiben alle Zeilen sichtbar (kein dauerhaft verstecktes Formular).

### Mini (Betreuer-Feedback)
- "Paragraph hinzufügen" öffnet jetzt sofort das Bearbeiten-Formular, ohne dass der Dozent zusätzlich aufs Stiftchen klicken muss (`dozent/mini_kurs_sehen.php`).
- "+ neuer Paragraph"-Buttons gibt es jetzt oben, unten und zwischen jedem Paragraph (statt nur einem Button ganz oben am Anfang).
- Mit JavaScript öffnet ein Klick auf "+ neuer Paragraph" direkt an der Klickstelle ein Titel/Inhalt-Formular, ohne Page-Reload; beim Speichern wird der Paragraph in einem Schritt mit Inhalt an der richtigen Position angelegt.
- Ohne JavaScript (Fallback): Klick legt sofort einen leeren Paragraph an der gewünschten Position an und springt automatisch ins Bearbeiten-Formular.
- Neuer Button "Speichern und weiter bearbeiten" beim Bearbeiten bestehender Paragraphen.
- Alle Aktionen (hinzufügen/speichern/löschen/hoch/runter) schließen jetzt konsistent mit einem Redirect ab (Post/Redirect/Get-Pattern), Erfolgsmeldungen werden über `?erfolg=...` transportiert.
- Vorschau-Button im Paragraph-Formular (Bearbeiten + neu anlegen): kopiert den Textarea-Inhalt per JavaScript in ein deutlich gekennzeichnetes Vorschau-Div darüber (gestrichelter Rahmen, Hinweistext), `\n` wird zu `<br>` ersetzt. Erneuter Klick blendet die Vorschau wieder aus.
- Formatierungs-Buttons im Paragraph-Formular (Bearbeiten + neu anlegen): Fett, Kursiv, Unterstrichen, Rot, Grün, Liste. Umschließen die Markierung mit dem passenden HTML-Tag; erneuter Klick auf eine bereits umschlossene Markierung entfernt die Tags wieder (Toggle). Funktioniert zuverlässig bei korrekter Markierung.

### Mini: Anhänge (Upload/Download)
- Neue Tabelle `gpb_mini_anhang` (Migration `migrations/2026-07-19_gpb_mini_anhang.sql`): Original-Dateiname, zufälliger Speicherdateiname, Größe, Zeitstempel.
- Dateien werden pro Dozent in `mini_uploads/dozent_<id>/` gespeichert; der Ordner ist per `.htaccess` komplett gesperrt (`Require all denied` / `Deny from all`).
- Downloads laufen ausschließlich über `mini_anhang_download.php` im Projektroot, das je nach eingeloggter Rolle (Dozent/TN/Verwaltung) prüft, ob Zugriff auf den zugehörigen Kurs besteht, bevor die Datei ausgeliefert wird.
- Neue gemeinsame Hilfsdatei `MiniAnhang.php` (Liste laden, Dateigröße anzeigen, Speicherpfad ermitteln), genutzt von `dozent/`, `tn/` und `verwaltung/mini_kurs_sehen.php`.
- Dozent: Anhänge hochladen/löschen im Bearbeiten-Formular jedes Paragraphen; Downloadliste zusätzlich im Ansichtsmodus.
- TN und Verwaltung: nur Downloadliste (read-only), keine Upload-/Löschmöglichkeit.
- `.gitignore` ergänzt: hochgeladene Anhänge (`mini_uploads/dozent_*`) werden nicht versioniert.
- Fehlerbehandlung beim Upload verbessert: fehlgeschlagene Uploads (z. B. Ordner nicht beschreibbar) werden jetzt als Fehlermeldung angezeigt statt fälschlich als Erfolg, Details landen im `php_error_log`.

### Mini (Betreuer-Feedback)
- Moodle- und Mini-Spalte in der Kursliste sowie im Kurs-Property-Sheet zu einer gemeinsamen Zelle "Moodle / Mini" zusammengelegt (`Kurs.php` sowie die Unterklassen `dozent/DozentKurs.php`, `tn/TnKurs.php`, `verwaltung/VerwaltungKurs.php`, `leitung/LeitungKurs.php`).
- `nl2br()` bei der Anzeige des Mini-Paragraphinhalts ergänzt (`dozent/mini_kurs_sehen.php`, `tn/mini_kurs_sehen.php`, `verwaltung/mini_kurs_sehen.php`).
- Zeilenumbrüche beim Speichern eines Paragraphs normalisiert (`\r\n`/`\r` → `\n`), um Darstellungsprobleme durch Windows-Zeilenumbrüche zu vermeiden (`dozent/mini_kurs_sehen.php`).

### Deployment
- `db.php` aus der Versionskontrolle entfernt (enthielt Live-Zugangsdaten) und zu `.gitignore` hinzugefügt.
- Live-`db.php` auf `kronisoft.net` mit korrekten Zugangsdaten (User `web32`, Datenbank `usr_web32_4`) aktualisiert.

### Server-/Deployment-Fixes
- Fehlerhafte `DocumentRoot` im SSL-VirtualHost (`/opt/lampp/etc/extra/httpd-ssl.conf`) korrigiert: zeigte fälschlich auf `/opt/lampp/htdocs` statt auf `/media/emu/daten/arbeit/htdocs`. Ursache für 403-Fehler bei allen Projekten unter HTTPS.
- Doppelte `Include`-Kette in `/opt/lampp/etc/httpd.conf` bereinigt (auskommentiert), die eine zweite, unveränderte `httpd.conf` mit alten Pfaden nachgeladen hat.

### Datenbank
- Tabelle `gpb_kurs`: Spalte `mini` (`TINYINT(1) NOT NULL DEFAULT 0`) ergänzt.
- View `gpb_kurs_view` neu erstellt (unveränderte Definition aus `gpb.views.sql`), sodass `mini` automatisch über `k.*` mit ausgegeben wird.

### Migration
- `klassen_kalender.php` und `klasse_sollplanvergleich.php` von `leitung/` nach `verwaltung/` migriert (Zugriffsschutz entsprechend angepasst).
- `planung.php` inkl. zugehöriger JS-Dateien (`Planung*.js`) und PHP-Hilfsdateien (`planung_*.php`, `planungkonfig*.php`) von `leitung/` nach `verwaltung/` verschoben/ergänzt.

### Neue Dateien
- `fpdi/` – FPDI-Bibliothek für PDF-Erzeugung eingebunden.
- `beratung/vorlagen/` – neue Vorlagen (`BBILogo.png`, `QualifizierungsangebotBBI.html`, `certqua.png`).
- `verwaltung/vorlagen/BafoegFormblatt2.pdf` – neue Formblatt-Vorlage.
- `verwaltung/beruf_module_finden.php`, `verwaltung/tn_bafoegformblatt2_pdf.php`, `verwaltung/vonmitis/klassen_infokurs.php`, `intern/raumplan_bildschirme_tag.php`.

### .gitignore erweitert
- `gpb_bu*.sql` (Datenbank-Backups)
- `db.local.php`, `db.localprod.php`, `db.prod.php`, `db.test.php`, `db.verwaltungtest.php` (umgebungsspezifische DB-Zugangsdaten)
- `beratung/signaturbilder/`, `beratung/temp/` (Laufzeit-Uploads/Temp-Dateien)
