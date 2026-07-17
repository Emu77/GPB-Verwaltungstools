# Changelog

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
