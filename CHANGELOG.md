# Changelog

## 2026-07-23

### TODO-Liste (Teilnehmer & Dozent)
- Die "TODO"-Tabelle mit offenen Bewertungen/Berichten wird jetzt standardmäßig eingeklappt angezeigt, statt immer vollständig sichtbar zu sein (`tn/TnSeite.php`, `dozent/DozentSeite.php`).
- Umsetzung über natives HTML `<details>`/`<summary>`, ganz ohne JavaScript.
- Der Klapptext zeigt die aktuelle Anzahl offener Todos an (z. B. "59 Todos aufklappen" / "1 Todo aufklappen") und wechselt je nach Zustand automatisch zwischen "aufklappen" und "zuklappen" (reines CSS über den `details[open]`-Selektor).

### Login / Deployment
- `db.php` um eine Umgebungs-Weiche ergänzt: lädt je nach `$_SERVER['SERVER_NAME']` automatisch `db.local.php` (lokal) oder `db.prod.php` (Produktion), statt fest codierter Zugangsdaten in einer einzigen Datei.
- MySQL-Passwort für den Live-Nutzer `web32` erneuert und in `db.prod.php`/`db.php` synchronisiert.
- Ursache für gescheiterte Teilnehmer-Logins geklärt: kein Code-Fehler, sondern Tests mit nicht existierenden Nutzernamen; mit vorhandenen Testnutzern (`gpb_tn`, Passwort `tn`) funktioniert der Login korrekt.

## 2026-07-19

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

## 2026-07-17

### Mini (Betreuer-Feedback)
- Moodle- und Mini-Spalte in der Kursliste sowie im Kurs-Property-Sheet zu einer gemeinsamen Zelle "Moodle / Mini" zusammengelegt (`Kurs.php` sowie die Unterklassen `dozent/DozentKurs.php`, `tn/TnKurs.php`, `verwaltung/VerwaltungKurs.php`, `leitung/LeitungKurs.php`).
- `nl2br()` bei der Anzeige des Mini-Paragraphinhalts ergänzt (`dozent/mini_kurs_sehen.php`, `tn/mini_kurs_sehen.php`, `verwaltung/mini_kurs_sehen.php`).
- Zeilenumbrüche beim Speichern eines Paragraphs normalisiert (`\r\n`/`\r` → `\n`), um Darstellungsprobleme durch Windows-Zeilenumbrüche zu vermeiden (`dozent/mini_kurs_sehen.php`).

### Deployment
- `db.php` aus der Versionskontrolle entfernt (enthielt Live-Zugangsdaten) und zu `.gitignore` hinzugefügt.
- Live-`db.php` auf `kronisoft.net` mit korrekten Zugangsdaten (User `web32`, Datenbank `usr_web32_4`) aktualisiert.

## 2026-07-15

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
