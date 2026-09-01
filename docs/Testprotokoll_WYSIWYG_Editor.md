# Testprotokoll – Custom-WYSIWYG-Editor (gpb_praktikum)

Projektzeitraum: 24.08.2026 – 06.09.2026 · Phase 5: Testing (01.–02.09.2026)

Legende Status: ✅ bestanden · ❌ fehlgeschlagen · ⬜ noch offen

---

## 1. Funktionstests (Editor selbst)

| Nr | Testfall | Erwartetes Ergebnis | Tatsächliches Ergebnis | Status |
|----|----------|----------------------|--------------------------|--------|
| F1 | Text markieren, "Fett" klicken | Markierung wird fett dargestellt (`<strong>`) | | ⬜ |
| F2 | Fette Markierung erneut "Fett" klicken (Toggle) | Fettung wird wieder entfernt | | ⬜ |
| F3 | Text markieren, "Kursiv" klicken | Markierung kursiv (`<em>`) | | ⬜ |
| F4 | Text markieren, "Unterstrichen" klicken | Markierung unterstrichen (`<u>`) | | ⬜ |
| F5 | Text markieren, Farbe "Rot"/"Grün" klicken | Markierung farbig (`<span style="color:...">`) | | ⬜ |
| F6 | Bereits farbige Markierung, andere Farbe klicken | Alte Farbe wird ersetzt, nicht verschachtelt | | ⬜ |
| F7 | Fett + Kursiv gleichzeitig auf gleicher Markierung | Beide Formatierungen gleichzeitig sichtbar, kein kaputtes HTML | | ⬜ |
| F8 | Mehrere Zeilen markieren, "Liste" klicken | `<ul><li>`-Struktur entsteht | | ⬜ |
| F9 | Bild-Anhang aus Dropdown wählen, "Bild einfügen" | `<img>` wird an Cursorposition eingefügt | | ⬜ |
| F10 | Wechsel WYSIWYG-Checkbox an → aus → an | Inhalt bleibt beim Wechsel erhalten, keine Datenverluste | | ⬜ |
| F11 | Quellcode-Ansicht (falls vorhanden) umschalten | Rohes HTML wird angezeigt/zurückgewandelt, Inhalt bleibt synchron | | ⬜ |
| F12 | "Speichern und weiter bearbeiten" | Inhalt bleibt im Formular, Erfolgsmeldung erscheint | | ⬜ |
| F13 | "Vorschau" klicken | Vorschau zeigt Inhalt wie in echter Anzeige (kein Extra-Styling) | | ⬜ |

## 2. Browsertests (Desktop)

| Nr | Browser | Getestete Funktionen | Ergebnis | Status |
|----|---------|------------------------|----------|--------|
| B1 | Chrome (aktuell) | Formatierung, Bild einfügen, Vorschau, Speichern | Live auf kronisoft.net erfolgreich getestet (siehe CHANGELOG 2026-08) | ✅ |
| B2 | Firefox (aktuell) | Formatierung, Bild einfügen, Vorschau, Speichern | | ⬜ |
| B3 | Edge (aktuell) | Formatierung, Bild einfügen, Vorschau, Speichern | | ⬜ |

## 3. Browsertests (Mobile Ansicht)

| Nr | Browser | Getestete Funktionen | Ergebnis | Status |
|----|---------|------------------------|----------|--------|
| M1 | Chrome (mobile Ansicht/Emulation) | Toolbar bedienbar, Markierung per Touch möglich | | ⬜ |
| M2 | Firefox (mobile Ansicht/Emulation) | Toolbar bedienbar, Markierung per Touch möglich | | ⬜ |
| M3 | Edge (mobile Ansicht/Emulation) | Toolbar bedienbar, Markierung per Touch möglich | | ⬜ |

## 4. XSS-Testfälle gegen die Sanitisierung (Phase 4)

| Nr | Eingabe | Erwartetes Ergebnis | Tatsächliches Ergebnis | Status |
|----|---------|------------------------|--------------------------|--------|
| X1 | `<script>alert(1)</script>` | Vollständig entfernt | Vollständig entfernt (lokal PHP-CLI, 31.08.2026) | ✅ |
| X2 | `<strong onclick="alert(1)">fett</strong>` | `onclick` entfernt, `<strong>fett</strong>` bleibt | Wie erwartet (lokal PHP-CLI, 31.08.2026) | ✅ |
| X3 | `<span style="color:red" onmouseover="alert(1)">rot</span>` | `onmouseover` entfernt, `style="color:red;"` bleibt | Wie erwartet (lokal PHP-CLI, 31.08.2026) | ✅ |
| X4 | `<img src="javascript:alert(1)" alt="x">` | `src` entfernt, `alt` bleibt | Wie erwartet (lokal PHP-CLI, 31.08.2026) | ✅ |
| X5 | `<img src="mini_anhang_download.php?id=5" alt="ok">` | Bleibt vollständig erhalten (eigener Anhang-Pfad) | Wie erwartet (lokal PHP-CLI, 31.08.2026) | ✅ |
| X6 | `<img src="https://evil.example.com/x.png" alt="boese">` | `src` entfernt, `alt` bleibt (externe Quelle abgelehnt) | Wie erwartet (lokal PHP-CLI, 31.08.2026) | ✅ |
| X7 | `<iframe src="https://evil.example.com"></iframe>` | Vollständig entfernt | Vollständig entfernt (lokal PHP-CLI, 31.08.2026) | ✅ |
| X8 | `<p>Text <em>kursiv</em> und <u>unterstrichen</u></p>` | Unverändert (alles erlaubte Tags) | Unverändert (lokal PHP-CLI, 31.08.2026) | ✅ |
| X9 | `<div class="foo"><b>bold</b></div>` | `<div>` und `<b>` entfernt, nur Text "bold" bleibt | Nach Bugfix: nur "bold" (lokal PHP-CLI, 31.08.2026) | ✅ |
| X10 | `<script>alert(1)</script> oder <b onclick="alert(1)">test</b>` (End-to-End über echtes Formular) | Nach Speichern nur "oder test" sichtbar, kein Alert, DB-Inhalt selbst bereinigt | Bestätigt: "oder test", Alert blieb aus, Rohcode im Bearbeiten-Formular ebenfalls sauber (Live-Test kronisoft.net, 31.08.2026) | ✅ |
| X11 | Verschachteltes `<script>` innerhalb erlaubtem Tag, z. B. `<p><script>alert(1)</script>Text</p>` | `<script>` entfernt, `<p>Text</p>` bleibt | | ⬜ |
| X12 | `data:`-URI als Bildquelle, z. B. `<img src="data:text/html,<script>alert(1)</script>" alt="x">` | `src` entfernt | | ⬜ |
| X13 | Verschachtelte, mehrfach nicht erlaubte Tags, z. B. `<div><table><tr><td>text</td></tr></table></div>` | Alle Tags entfernt, "text" bleibt | | ⬜ |
| X14 | `<style>body{display:none}</style>` | Vollständig entfernt | | ⬜ |
| X15 | Case-Insensitive Umgehungsversuch, z. B. `<ScRiPt>alert(1)</sCriPt>` | Vollständig entfernt (DOMDocument normalisiert Tag-Namen) | | ⬜ |

## 5. Zusammenfassung

- Anzahl Testfälle gesamt: 31
- Bestanden: 10
- Offen: 21
- Fehlgeschlagen: 0
- Bekannte Bugs während der Testphase gefunden und behoben: 1 (siehe X9 – rekursive Bereinigung hochgezogener Kindelemente, CHANGELOG 2026-08)

## 6. Fazit

*(Nach Abschluss aller Testfälle auszufüllen: Gesamteinschätzung der Stabilität und Sicherheit des Editors, offene Punkte, Empfehlung für Ausrollung auf weitere Formulare.)*
