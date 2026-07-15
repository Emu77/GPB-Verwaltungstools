# Changelog

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
