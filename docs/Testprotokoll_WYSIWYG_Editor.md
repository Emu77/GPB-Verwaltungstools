# Testprotokoll – Custom-WYSIWYG-Editor (gpb_praktikum)

Projektzeitraum: 24.08.2026 – 06.09.2026 · Phase 5: Testing (01.–02.09.2026)

Legende Status: ✅ bestanden · ❌ fehlgeschlagen · ⬜ noch offen

---

## 1. Funktionstests (Editor selbst)

| Nr | Testfall | Erwartetes Ergebnis | Tatsächliches Ergebnis | Status |
|----|----------|----------------------|--------------------------|--------|
| F1 | Text markieren, "Fett" klicken | Markierung wird fett dargestellt (`<strong>`) | Wie erwartet (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| F2 | Fette Markierung erneut "Fett" klicken (Toggle) | Fettung wird wieder entfernt | Wie erwartet (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| F3 | Text markieren, "Kursiv" klicken | Markierung kursiv (`<em>`) | Wie erwartet (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| F4 | Text markieren, "Unterstrichen" klicken | Markierung unterstrichen (`<u>`) | Wie erwartet (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| F5 | Text markieren, Farbe "Rot"/"Grün" klicken | Markierung farbig (`<span style="color:...">`) | Wie erwartet, beide Farben getestet (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| F6 | Bereits farbige Markierung, andere Farbe klicken | Alte Farbe wird ersetzt, nicht verschachtelt | Grün ersetzte Rot korrekt, keine Verschachtelung (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| F7 | Fett + Kursiv gleichzeitig auf gleicher Markierung | Beide Formatierungen gleichzeitig sichtbar, kein kaputtes HTML | Wie erwartet (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| F8 | Mehrere Zeilen markieren, "Liste" klicken | `<ul><li>`-Struktur entsteht | Wie erwartet (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| F9 | Bild-Anhang aus Dropdown wählen, "Bild einfügen" | `<img>` wird an Cursorposition eingefügt | Bild korrekt an Cursorposition eingefügt (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| F10 | Wechsel WYSIWYG-Checkbox an → aus → an | Inhalt bleibt beim Wechsel erhalten, keine Datenverluste | Kompletter Inhalt (Formatierung, Liste, Bild) blieb erhalten (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| F11 | Quellcode-Ansicht (Checkbox aus) umschalten | Rohes HTML wird angezeigt/zurückgewandelt, Inhalt bleibt synchron | Rohcode korrekt angezeigt inkl. `<img src="../mini_anhang_download.php?id=7">`, kein kaputtes HTML (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| F12 | "Speichern und weiter bearbeiten" | Inhalt bleibt im Formular, Erfolgsmeldung erscheint | "Paragraph gespeichert." erschien, Formular blieb offen (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| F13 | "Vorschau" klicken | Vorschau zeigt Inhalt wie in echter Anzeige (kein Extra-Styling) | Vorschau identisch zum Editor-Inhalt, "Vorschau aktualisieren"-Button erschien (Live-Test kronisoft.net, 01.09.2026) | ✅ |

## 2. Browsertests (Desktop)

| Nr | Browser | Getestete Funktionen | Ergebnis | Status |
|----|---------|------------------------|----------|--------|
| B1 | Chrome (aktuell) | Formatierung, Bild einfügen, Vorschau, Speichern | Live auf kronisoft.net erfolgreich getestet (siehe CHANGELOG 2026-08) | ✅ |
| B2 | Firefox (aktuell) | Formatierung, Bild einfügen, Vorschau, Speichern | Alle Funktionstests F1–F13 sowie XSS-Tests X10–X15 heute in Firefox durchgeführt, alle bestanden (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| B3 | Edge (aktuell) | Formatierung, Bild einfügen, Vorschau, Speichern | Text formatiert (fett, kursiv, grün), Bild an Cursorposition eingefügt, Vorschau korrekt, Speichern erfolgreich (Live-Test kronisoft.net, 01.09.2026) | ✅ |

## 3. Browsertests (Mobile Ansicht)

| Nr | Browser | Getestete Funktionen | Ergebnis | Status |
|----|---------|------------------------|----------|--------|
| M1 | Chrome (DevTools Responsive-Modus, 400×808) | Toolbar bedienbar, Markierung per Touch möglich | Alle Toolbar-Buttons sichtbar und bedienbar, Formatierung/Bild-Einfügen funktionierte, Rohcode korrekt verschachtelt (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| M2 | Firefox (Responsive Design Modus, 320×480) | Toolbar bedienbar, Markierung per Touch möglich | Editor voll bedienbar, Texteingabe funktioniert, Toolbar-Buttons vollständig sichtbar (kein Abschneiden/Überlappen), Buttons bei dieser Breite aber eng beieinander (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| M3 | Edge (DevTools Responsive-Modus, 400×902) | Toolbar bedienbar, Markierung per Touch möglich | Editor bedienbar, Texteingabe und Formatierung funktionierten, Layout innerhalb Viewport (Live-Test kronisoft.net, 01.09.2026, verifiziert per Edge-DevTools-Willkommensbildschirm) | ✅ |

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
| X11 | Verschachteltes `<script>` innerhalb erlaubtem Tag, z. B. `<p><script>alert(1)</script>Text</p>` | `<script>` entfernt, `<p>Text</p>` bleibt | Kein Alert, `<p>Text</p>` korrekt gespeichert (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| X12 | `data:`-URI als Bildquelle, z. B. `<img src="data:text/html,<script>alert(1)</script>" alt="x">` | `src` entfernt | `<img alt="x">` blieb, `src` vollständig entfernt (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| X13 | Verschachtelte, mehrfach nicht erlaubte Tags, z. B. `<div><table><tr><td>text</td></tr></table></div>` | Alle Tags entfernt, "text" bleibt | Nur "text" blieb übrig, alle Tags entfernt (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| X14 | `<style>body{display:none}</style>` | Vollständig entfernt | Textfeld nach Speichern leer (Live-Test kronisoft.net, 01.09.2026) | ✅ |
| X15 | Case-Insensitive Umgehungsversuch, z. B. `<ScRiPt>alert(1)</sCriPt>` | Vollständig entfernt (DOMDocument normalisiert Tag-Namen) | Textfeld nach Speichern leer (Live-Test kronisoft.net, 01.09.2026) | ✅ |

## 5. Zusammenfassung

- Anzahl Testfälle gesamt: 34
- Bestanden: 34
- Offen: 0
- Fehlgeschlagen: 0
- Bekannte Bugs während der Testphase gefunden und behoben: 1 (siehe X9 – rekursive Bereinigung hochgezogener Kindelemente, CHANGELOG 2026-08)

## 6. Fazit

Alle 34 Testfälle wurden erfolgreich abgeschlossen (34 bestanden, 0 offen, 0 fehlgeschlagen). Der Custom-WYSIWYG-Editor funktioniert stabil über alle drei getesteten Desktop-Browser (Chrome, Firefox, Edge) sowie in der jeweiligen mobilen/responsiven Ansicht. Sämtliche Kernfunktionen (Formatierung inkl. Toggle-Verhalten, verschachtelte Formatierungen, Listen, Bild-Einfügen, WYSIWYG-/Quellcode-Wechsel, Vorschau) arbeiten wie im Feinkonzept vorgesehen.

Die serverseitige Sanitisierung (Phase 4) hat alle 15 XSS-Testfälle bestanden, inklusive Randfällen wie verschachtelten `<script>`-Tags in erlaubten Elementen, `data:`-URIs, mehrfach verschachtelten nicht erlaubten Tags, `<style>`-Injektion und Groß-/Kleinschreibungs-Umgehungsversuchen. Ein während der Testphase gefundener Bug (Kinder eines entfernten, nicht erlaubten Tags entkamen der Bereinigung beim Hochziehen) wurde noch vor Abschluss der Tests behoben und erneut erfolgreich verifiziert.

Einzige Beobachtung ohne Fehlercharakter: Auf sehr schmalen Viewports (320px) liegen die Toolbar-Buttons dicht beieinander; das Layout selbst passt sich nicht responsiv an (kein eigenes Mobile-CSS), bleibt aber vollständig innerhalb des Viewports und funktional bedienbar. Für eine spätere Ausrollung des Editors auf weitere Formulare im System wäre ein eigenes, für schmale Bildschirme optimiertes Toolbar-Layout ein sinnvoller nächster Schritt, ist aber für den aktuellen Einsatzzweck (Dozenten-Bearbeitung, primär Desktop) nicht kritisch.
