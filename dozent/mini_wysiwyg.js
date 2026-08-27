/**
 * Custom WYSIWYG-Editor für gpb_praktikum (Ersatz für TinyMCE)
 * Phase 2: Editor-Grundgerüst
 *   - HTML-Grundgerüst (contenteditable-Fläche + Toolbar-Markup)
 *   - Aufbau Selection-/Range-Zugriff
 *   - Formatierungsfunktionen Fett/Kursiv/Unterstrichen/Farbe inkl. Toggle-Logik
 * Phase 3: Liste + Quellcode-Ansicht
 *   - Listenfunktion (toggleList): zeilenweise <ul><li>, Toggle-Logik analog
 *     zu den Inline-Formatierungen, aber auf Block-Ebene
 *   - Quellcode-Ansicht (toggleSourceView): rohes HTML direkt bearbeitbar,
 *     löst die contenteditable-Fläche temporär durch eine <textarea> ab
 *
 * Kein document.execCommand() (deprecated) - Formatierung wird selbst über
 * die Range-API umgesetzt, analog zum Prinzip der bestehenden Plain-Text-
 * Toolbar (mini-format-btn: öffnen/schließen bzw. Toggle bei exakter Markierung).
 *
 * Einbindung pro Textarea (in mini_kurs_sehen.php statt tinymce.init):
 *   MiniWysiwyg.init(textareaElement);
 *
 * Bekannte Einschränkung (wie beim bisherigen Plain-Text-Toggle bewusst
 * in Kauf genommen): bei überlappenden/verschachtelten Markierungen kann
 * unsauberes HTML entstehen.
 */
var MiniWysiwyg = (function () {
  'use strict';

  var instances = {}; // textareaId -> { editor, textarea, toolbar, wrapper }

  // ---------------------------------------------------------------------
  // HTML-Grundgerüst
  // ---------------------------------------------------------------------

  function buildToolbar() {
    var toolbar = document.createElement('div');
    toolbar.className = 'mini-cwysiwyg-toolbar';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Formatierung');
    toolbar.innerHTML =
      '<button type="button" class="mini-cwysiwyg-btn" data-cmd="bold" title="Fett"><strong>F</strong></button>' +
      '<button type="button" class="mini-cwysiwyg-btn" data-cmd="italic" title="Kursiv"><em>K</em></button>' +
      '<button type="button" class="mini-cwysiwyg-btn" data-cmd="underline" title="Unterstrichen"><u>U</u></button>' +
      '<span class="mini-cwysiwyg-sep"></span>' +
      '<button type="button" class="mini-cwysiwyg-btn mini-cwysiwyg-color" data-cmd="color" data-color="red" title="Rot" style="color:red;">A</button>' +
      '<button type="button" class="mini-cwysiwyg-btn mini-cwysiwyg-color" data-cmd="color" data-color="green" title="Grün" style="color:green;">A</button>' +
      '<span class="mini-cwysiwyg-sep"></span>' +
      '<button type="button" class="mini-cwysiwyg-btn" data-cmd="removeformat" title="Formatierung entfernen">⨯</button>' +
      '<span class="mini-cwysiwyg-sep"></span>' +
      '<button type="button" class="mini-cwysiwyg-btn" data-cmd="list" title="Liste">☰ Liste</button>' +
      '<button type="button" class="mini-cwysiwyg-btn" data-cmd="source" title="Quellcode-Ansicht">&lt;/&gt;</button>';
    return toolbar;
  }

  function buildEditableArea() {
    var editor = document.createElement('div');
    editor.className = 'mini-cwysiwyg-editor';
    editor.setAttribute('contenteditable', 'true');
    editor.setAttribute('spellcheck', 'true');
    return editor;
  }

  // ---------------------------------------------------------------------
  // Selection-/Range-Zugriff
  // ---------------------------------------------------------------------

  // Liefert die aktuelle Range, aber nur, wenn sie tatsächlich innerhalb
  // der Editor-Fläche liegt (Schutz gegen Selektionen außerhalb, z.B. wenn
  // der Nutzer irgendwo anders auf der Seite markiert hat).
  function getEditorRange(editor) {
    var sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return null;
    var range = sel.getRangeAt(0);
    if (!editor.contains(range.commonAncestorContainer)) return null;
    return range;
  }

  // Verhindert, dass ein Klick auf die Toolbar die Selektion im Editor
  // aufhebt (der Browser würde sonst beim Fokuswechsel die Range verwerfen).
  function keepSelectionOnMousedown(toolbar) {
    toolbar.addEventListener('mousedown', function (e) {
      if (e.target.closest('.mini-cwysiwyg-btn')) e.preventDefault();
    });
  }

  // ---------------------------------------------------------------------
  // Formatierung: Toggle-Logik über die Range-API
  // ---------------------------------------------------------------------

  // Sucht ausgehend von einem Knoten das nächste umschließende Element
  // mit dem angegebenen Selector, aber nicht über die Editor-Grenze hinaus.
  function closestWithinEditor(node, selector, editor) {
    var el = node.nodeType === 3 ? node.parentElement : node;
    while (el && el !== editor) {
      if (el.matches && el.matches(selector)) return el;
      el = el.parentElement;
    }
    return null;
  }

  // Löst ein Element auf, indem seine Kindknoten an seine Stelle gesetzt
  // werden (Tag entfernen, Inhalt bleibt erhalten).
  function unwrapElement(el) {
    var parent = el.parentNode;
    while (el.firstChild) parent.insertBefore(el.firstChild, el);
    parent.removeChild(el);
    parent.normalize();
  }

  // Bugfix: range.extractContents() lässt beim Splitten von Tag-Grenzen
  // leere "Hüllen" zurück (z.B. <strong></strong>), wenn die Selektion
  // genau an einer Tag-Grenze beginnt/endet. Nach jeder Formatierungs-
  // Aktion werden solche leeren Inline-Elemente entfernt.
  function cleanupEmptyInlineTags(editor) {
    var changed = true;
    while (changed) {
      changed = false;
      editor.querySelectorAll('strong, em, u, span').forEach(function (el) {
        if (el.textContent === '') {
          el.remove();
          changed = true;
        }
      });
    }
  }

  // Bugfix Teil 2: neben leeren Hüllen kann auch doppelte Verschachtelung
  // desselben Tags entstehen (z.B. <strong><em><strong>x</strong></em></strong>),
  // wenn eine Formatierung mehrfach auf dieselbe/überlappende Auswahl
  // angewendet wird. Direkt verschachtelte identische Tags (bei <span>
  // nur bei gleicher Farbe) werden zusammengeführt.
  function mergeRedundantNesting(editor) {
    var changed = true;
    while (changed) {
      changed = false;
      ['strong', 'em', 'u'].forEach(function (tag) {
        editor.querySelectorAll(tag + ' ' + tag).forEach(function (inner) {
          unwrapElement(inner);
          changed = true;
        });
      });
      editor.querySelectorAll('span span').forEach(function (inner) {
        var outer = inner.parentElement && inner.parentElement.closest('span');
        if (outer && inner.style.color && inner.style.color === outer.style.color) {
          unwrapElement(inner);
          changed = true;
        }
      });
    }
  }

  // Fasst beide Aufräumschritte zusammen - nach jeder Formatierungs-Aktion
  // aufgerufen, damit der Editor-Inhalt sauberes HTML bleibt.
  function normalizeInlineTags(editor) {
    cleanupEmptyInlineTags(editor);
    mergeRedundantNesting(editor);
    cleanupEmptyInlineTags(editor); // falls Merge neue leere Reste hinterlässt
  }

  // Prüft grob, ob die Selektion zusätzlich zum Selector auch einen
  // bestimmten Style-Wert tragen muss (für die Farb-Buttons: derselbe
  // Tag <span>, aber nur "aktiv", wenn die Farbe übereinstimmt).
  function matchesStyle(el, styleProp, styleValue) {
    if (!styleProp) return true;
    return el.style[styleProp] === styleValue;
  }

  // Farb-Buttons brauchen eine eigene Logik: bei derselben Farbe wie
  // bisher (Toggle aus) wie gehabt entpacken. Bei einer ANDEREN Farbe soll
  // aber nicht eine weitere <span> verschachtelt werden, sondern die
  // bestehende Farb-Span einfach umgefärbt werden (kein HTML-Aufblähen).
  function toggleColor(editor, colorValue) {
    var range = getEditorRange(editor);
    if (!range || range.collapsed) return;

    var selector = 'span[style*="color"]';
    var startEl = closestWithinEditor(range.startContainer, selector, editor);
    var endEl = closestWithinEditor(range.endContainer, selector, editor);

    if (startEl && startEl === endEl) {
      var sel = window.getSelection();
      var newRange = document.createRange();

      if (startEl.style.color === colorValue) {
        // Toggle aus: exakt dieselbe Farbe nochmal geklickt -> entfernen
        newRange.selectNodeContents(startEl);
        unwrapElement(startEl);
      } else {
        // Farbe wechseln: bestehende Span umfärben statt neu verschachteln
        startEl.style.color = colorValue;
        newRange.selectNodeContents(startEl);
      }
      sel.removeAllRanges();
      sel.addRange(newRange);
      normalizeInlineTags(editor);
      return;
    }

    // Keine Farb-Span über die komplette Auswahl -> wie gehabt neu einwickeln
    toggleInline(editor, 'span', 'color', colorValue);
  }

  /**
   * Schaltet eine Inline-Formatierung für die aktuelle Selektion um.
   * @param {HTMLElement} editor  Die contenteditable-Fläche
   * @param {string} tagName      z.B. 'strong', 'em', 'u', 'span'
   * @param {string} [styleProp]  z.B. 'color' (nur bei span nötig)
   * @param {string} [styleValue] z.B. 'red'
   */
  function toggleInline(editor, tagName, styleProp, styleValue) {
    var range = getEditorRange(editor);
    if (!range || range.collapsed) return; // ohne Markierung nichts zu tun

    var selector = tagName.toLowerCase();
    var startEl = closestWithinEditor(range.startContainer, selector, editor);
    var endEl = closestWithinEditor(range.endContainer, selector, editor);

    // Toggle AUS: komplette Auswahl liegt bereits in genau einem
    // passenden Element (gleicher Tag, ggf. gleicher Style) -> entpacken.
    if (startEl && startEl === endEl && matchesStyle(startEl, styleProp, styleValue)) {
      var innerRange = document.createRange();
      innerRange.selectNodeContents(startEl);
      unwrapElement(startEl);
      var sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(innerRange);
      normalizeInlineTags(editor);
      return;
    }

    // Toggle AN: Auswahl in neues Element einwickeln.
    var el = document.createElement(tagName);
    if (styleProp) el.style[styleProp] = styleValue;
    var content = range.extractContents();
    el.appendChild(content);
    range.insertNode(el);

    var newRange = document.createRange();
    newRange.selectNodeContents(el);
    var sel2 = window.getSelection();
    sel2.removeAllRanges();
    sel2.addRange(newRange);
    normalizeInlineTags(editor);
  }

  // Bugfix: range.extractContents() extrahiert bei einer Selektion, die
  // exakt dem Inhalt eines umschließenden Tags entspricht, NUR den reinen
  // Text - das leere Tag bleibt an Ort und Stelle zurück, und der wieder
  // eingefügte Text landet erneut genau darin (keine sichtbare Änderung).
  // Zwei Fälle werden deshalb unterschieden:
  //  1) Auswahl liegt komplett innerhalb EINES Inline-Tags (z.B. genau der
  //     Inhalt einer <span>) - über Textknoten-Ersetzung lösen, das räumt
  //     die leere Hülle zuverlässig mit auf.
  //  2) Auswahl umfasst mehrere Blöcke/Listenelemente - hier NICHT alles
  //     zu einem Textknoten flachklopfen (das würde <ul>/<li> zerstören),
  //     sondern die Block-Struktur erhalten und nur Inline-Tags darin
  //     entfernen.
  function removeFormatting(editor) {
    var range = getEditorRange(editor);
    if (!range) return;

    var inlineSelector = 'strong, em, u, span';

    // Cursor ohne Auswahl: alle umschließenden Formatierungen direkt
    // auflösen (von innen nach außen), kein vorheriges Markieren nötig -
    // analog zum Listen-Toggle. Erst ALLE betroffenen Vorfahren einsammeln
    // und danach unwrappen, statt zwischendurch neu zu suchen: unwrapElement()
    // ruft intern normalize() auf, das benachbarte Textknoten verschmilzt
    // und eine erneute Live-Suche vom (ggf. verschmolzenen) Startknoten aus
    // unzuverlässig machen würde.
    if (range.collapsed) {
      var node = range.startContainer;
      var el = node.nodeType === 3 ? node.parentElement : node;
      var ancestors = [];
      while (el && el !== editor) {
        if (el.matches && el.matches(inlineSelector)) ancestors.push(el);
        el = el.parentElement;
      }
      if (ancestors.length === 0) return;
      ancestors.forEach(function (a) { unwrapElement(a); });
      window.getSelection().removeAllRanges();
      normalizeInlineTags(editor);
      return;
    }

    var startInline = closestWithinEditor(range.startContainer, inlineSelector, editor);
    var endInline = closestWithinEditor(range.endContainer, inlineSelector, editor);

    if (startInline && startInline === endInline && startInline.textContent === range.toString()) {
      var text = range.toString();
      range.deleteContents();
      var textNode = document.createTextNode(text);
      range.insertNode(textNode);

      var changed = true;
      while (changed) {
        changed = false;
        var ancestor = closestWithinEditor(textNode, inlineSelector, editor);
        if (ancestor && ancestor.textContent === text) {
          unwrapElement(ancestor);
          changed = true;
        }
      }

      // Keine exakte Reselektion des Textknotens: unwrapElement() ruft
      // intern parent.normalize() auf, das benachbarte Textknoten
      // verschmilzt - der ursprüngliche textNode wäre danach ggf. bereits
      // aus dem DOM entfernt ("InvalidNodeTypeError"). Selektion daher
      // einfach leeren.
      window.getSelection().removeAllRanges();
      normalizeInlineTags(editor);
      return;
    }

    // Fall 2: Block-Struktur (Listen, Absätze) bleibt erhalten, nur
    // Inline-Formatierung innerhalb der Selektion wird entfernt.
    var frag = range.extractContents();
    frag.querySelectorAll(inlineSelector).forEach(function (el) { unwrapElement(el); });
    range.insertNode(frag);

    // An den Rändern können dabei leere <p></p>- bzw. <li></li>-Reste
    // entstehen (dort, wo die Selektion einen Absatz/Listenpunkt mittendrin
    // geteilt hat) - gleich mit aufräumen, betrifft nur diese eine Aktion.
    editor.querySelectorAll('p, li').forEach(function (p) {
      if (p.textContent.trim() === '' && p.children.length === 0) p.remove();
    });

    window.getSelection().removeAllRanges();
    normalizeInlineTags(editor);
  }

  // ---------------------------------------------------------------------
  // Liste (Phase 3): Toggle-Logik analog zu den Inline-Formatierungen,
  // aber auf Block-Ebene - eine <li> pro markierter Zeile.
  // ---------------------------------------------------------------------

  // Ermittelt die "Zeilen" einer Selektion anhand der DOM-Struktur statt
  // über range.toString(): Zeilenumbrüche zwischen Block-Elementen (<p>,
  // <div>, <li>) bzw. an <br> werden dabei zuverlässig erkannt, unabhängig
  // von browserspezifischem Verhalten bei der Text-Serialisierung.
  // Liefert pro Zeile ein DOM-Fragment (nicht nur reinen Text) - dadurch
  // bleibt Inline-Formatierung (Fett, Farbe, ...) innerhalb der Zeilen beim
  // Umwandeln in eine Liste erhalten.
  function extractLineFragmentsFromRange(range) {
    var cloned = range.cloneContents();
    var lines = [];
    var current = document.createDocumentFragment();

    function hasContent(frag) {
      return Array.prototype.some.call(frag.childNodes, function (n) {
        return n.textContent.trim() !== '';
      });
    }

    function flush() {
      if (hasContent(current)) lines.push(current);
      current = document.createDocumentFragment();
    }

    Array.prototype.forEach.call(cloned.childNodes, function (node) {
      if (node.nodeType === 1 && /^(P|DIV|LI)$/.test(node.tagName)) {
        flush();
        var inner = document.createDocumentFragment();
        while (node.firstChild) inner.appendChild(node.firstChild);
        if (hasContent(inner)) lines.push(inner);
      } else if (node.nodeType === 1 && node.tagName === 'BR') {
        flush();
      } else {
        current.appendChild(node);
      }
    });
    flush();
    return lines;
  }

  // Ermittelt, ob eine komplette Liste (ul/ol) von der Selektion umschlossen
  // wird - und zwar in ZWEI Varianten, die Browser je nach Selektionsart
  // erzeugen können:
  //  a) Start/Ende liegen in einem Textknoten INNERHALB der Liste (typisch
  //     bei normalem Maus-Ziehen über den sichtbaren Text).
  //  b) Start/Ende liegen auf Container-Ebene, wobei die Liste als ganzes
  //     Kind-Element selektiert ist (typisch bei Strg+A / "Select All"
  //     über mehrere Blockelemente hinweg - der Browser markiert dann oft
  //     den gesamten Container statt tief in die Textknoten zu gehen).
  function findEnclosingList(range, editor) {
    var startList = closestWithinEditor(range.startContainer, 'ul, ol', editor);
    var endList = range.collapsed ? startList : closestWithinEditor(range.endContainer, 'ul, ol', editor);
    if (startList && startList === endList) return startList;

    // Variante b) prüfen: Start-/Endpunkt referenziert per Kindindex direkt
    // dieselbe <ul>/<ol> als selektiertes Element.
    if (!range.collapsed && range.startContainer.nodeType === 1 && range.endContainer.nodeType === 1) {
      var startChild = range.startContainer.childNodes[range.startOffset];
      var endChild = range.endContainer.childNodes[range.endOffset - 1];
      if (startChild && startChild === endChild && /^(UL|OL)$/.test(startChild.tagName)) {
        return startChild;
      }
    }
    return null;
  }

  function toggleList(editor) {
    var range = getEditorRange(editor);
    if (!range) return;

    // Auflösen einer Liste soll auch bei reinem Cursor (ohne markierten
    // Text) funktionieren - man will ja nicht erst extra markieren müssen,
    // nur um eine Liste wieder loszuwerden. Eine NEUE Liste erzeugen
    // braucht dagegen zwingend eine echte Auswahl (sonst gäbe es keinen
    // Text zum Umwandeln), siehe Prüfung weiter unten.
    var enclosingList = findEnclosingList(range, editor);

    // Toggle AUS: komplette Auswahl liegt bereits in derselben Liste ->
    // jedes <li> wird wieder zu einem eigenen <p> (Formatierung innerhalb
    // der Zeilen, z.B. Fett, bleibt dabei erhalten).
    if (enclosingList) {
      var frag = document.createDocumentFragment();
      Array.prototype.forEach.call(enclosingList.children, function (li) {
        var p = document.createElement('p');
        while (li.firstChild) p.appendChild(li.firstChild);
        frag.appendChild(p);
      });
      enclosingList.parentNode.insertBefore(frag, enclosingList);
      enclosingList.remove();
      window.getSelection().removeAllRanges();
      normalizeInlineTags(editor);
      return;
    }

    // Toggle AN: braucht eine echte Auswahl, sonst gäbe es keinen Text,
    // der in eine Liste umgewandelt werden könnte.
    if (range.collapsed) return;

    // Toggle AN: markierten Text zeilenweise in eine Liste umwandeln.
    // Inline-Formatierung (Fett, Farbe, ...) innerhalb der Zeilen bleibt
    // dabei erhalten (siehe extractLineFragmentsFromRange).
    var lineFragments = extractLineFragmentsFromRange(range);

    var ul = document.createElement('ul');
    if (lineFragments.length === 0) {
      // Fallback, falls keine Zeilenstruktur erkannt wurde: reiner Text.
      var liFallback = document.createElement('li');
      liFallback.textContent = range.toString();
      ul.appendChild(liFallback);
    } else {
      lineFragments.forEach(function (frag) {
        var li = document.createElement('li');
        li.appendChild(frag);
        ul.appendChild(li);
      });
    }

    range.deleteContents();
    range.insertNode(ul);

    // Beim Einfügen können an den Rändern leere <p></p>-Reste entstehen
    // (dort, wo die Selektion einen Absatz mittendrin geteilt hat) -
    // gleich mit aufräumen, betrifft nur diese eine Aktion.
    editor.querySelectorAll('p').forEach(function (p) {
      if (p.textContent.trim() === '' && p.children.length === 0) p.remove();
    });

    window.getSelection().removeAllRanges();
    normalizeInlineTags(editor);
  }

  // ---------------------------------------------------------------------
  // Quellcode-Ansicht (Phase 3): rohes HTML direkt bearbeitbar machen.
  // ---------------------------------------------------------------------

  function setOtherButtonsDisabled(toolbar, disabled) {
    toolbar.querySelectorAll('.mini-cwysiwyg-btn').forEach(function (btn) {
      if (btn.getAttribute('data-cmd') !== 'source') btn.disabled = disabled;
    });
  }

  function toggleSourceView(instance) {
    var editor = instance.editor;
    if (!instance.sourceTextarea) {
      // Quellcode-Ansicht aktivieren: contenteditable ausblenden, rohes
      // HTML in einer eigenen <textarea> zum direkten Bearbeiten zeigen.
      var source = document.createElement('textarea');
      source.className = 'mini-cwysiwyg-source';
      source.value = editor.innerHTML;
      source.addEventListener('input', function () { syncTextarea(instance); });
      editor.parentNode.insertBefore(source, editor.nextSibling);
      editor.style.display = 'none';
      instance.sourceTextarea = source;
      setOtherButtonsDisabled(instance.toolbar, true);
    } else {
      // Zurück zur normalen Ansicht: eingegebenes HTML übernehmen.
      editor.innerHTML = instance.sourceTextarea.value;
      instance.sourceTextarea.remove();
      instance.sourceTextarea = null;
      editor.style.display = '';
      setOtherButtonsDisabled(instance.toolbar, false);
      syncTextarea(instance);
    }
  }

  // ---------------------------------------------------------------------
  // Sync mit der ursprünglichen Textarea (Formular-Kompatibilität)
  // ---------------------------------------------------------------------

  // Der Browser legt beim Tippen im contenteditable eigenständig <p>-Absätze
  // an (z.B. durch Enter) - eine zusätzliche Leerzeile am Ende hinterlässt
  // dabei einen leeren <p></p>-Rest, der inhaltlich nichts beiträgt. Wird
  // NUR beim Auslesen entfernt, nie am sichtbaren Editor selbst, damit dem
  // Nutzer beim Tippen nichts unter dem Cursor verschwindet.
  function stripTrailingEmptyParagraphs(html) {
    return html.replace(/(<p>(?:\s|&nbsp;)*<\/p>\s*)+$/i, '');
  }

  function syncTextarea(instance) {
    // Während aktiver Quellcode-Ansicht ist editor.innerHTML noch nicht
    // aktualisiert (erst beim Zurückschalten) - der aktuelle Stand steht
    // bis dahin in der Quellcode-Textarea.
    var html = instance.sourceTextarea ? instance.sourceTextarea.value : instance.editor.innerHTML;
    instance.textarea.value = stripTrailingEmptyParagraphs(html);
  }

  // ---------------------------------------------------------------------
  // Init / Destroy (Schnittstelle analog miniWysiwygAn/Aus für TinyMCE)
  // ---------------------------------------------------------------------

  function init(textarea) {
    if (!textarea || !textarea.id || instances[textarea.id]) return;

    var wrapper = document.createElement('div');
    wrapper.className = 'mini-cwysiwyg';

    var toolbar = buildToolbar();
    var editor = buildEditableArea();
    editor.innerHTML = textarea.value; // vorhandenen Inhalt übernehmen

    wrapper.appendChild(toolbar);
    wrapper.appendChild(editor);

    textarea.style.display = 'none';
    textarea.parentNode.insertBefore(wrapper, textarea.nextSibling);

    var instance = { editor: editor, textarea: textarea, toolbar: toolbar, wrapper: wrapper };
    instances[textarea.id] = instance;

    keepSelectionOnMousedown(toolbar);

    toolbar.addEventListener('click', function (e) {
      var btn = e.target.closest('.mini-cwysiwyg-btn');
      if (!btn) return;
      var cmd = btn.getAttribute('data-cmd');

      // Quellcode-Ansicht zuerst behandeln: editor ist dabei ggf. versteckt,
      // die übliche focus()/Selektions-Logik unten würde darauf ins Leere
      // laufen bzw. wäre für diesen Fall gar nicht sinnvoll.
      if (cmd === 'source') {
        toggleSourceView(instance);
        return;
      }
      if (instance.sourceTextarea) return; // während Quellcode-Ansicht: andere Buttons ignorieren (zusätzlich zu disabled)

      // Bugfix: Solange die Editor-Fläche noch nie echten Fokus hatte
      // (z.B. gleich der erste Formatierungs-Klick nach dem Markieren),
      // setzt editor.focus() die gerade getroffene Selektion zurück auf
      // einen Cursor ohne Auswahl. Deshalb vorher sichern und danach bei
      // Bedarf wiederherstellen.
      var savedRange = getEditorRange(editor);
      editor.focus();
      if (savedRange) {
        var sel = window.getSelection();
        if (sel.rangeCount === 0 || sel.getRangeAt(0).collapsed) {
          sel.removeAllRanges();
          sel.addRange(savedRange);
        }
      }

      switch (cmd) {
        case 'bold': toggleInline(editor, 'strong'); break;
        case 'italic': toggleInline(editor, 'em'); break;
        case 'underline': toggleInline(editor, 'u'); break;
        case 'color': toggleColor(editor, btn.getAttribute('data-color')); break;
        case 'removeformat': removeFormatting(editor); break;
        case 'list': toggleList(editor); break;
      }
      syncTextarea(instance);
    });

    editor.addEventListener('input', function () { syncTextarea(instance); });
  }

  function destroy(textareaId) {
    var instance = instances[textareaId];
    if (!instance) return;
    if (instance.sourceTextarea) instance.editor.innerHTML = instance.sourceTextarea.value;
    syncTextarea(instance);
    instance.wrapper.remove();
    instance.textarea.style.display = '';
    delete instances[textareaId];
  }

  function getContent(textareaId) {
    var instance = instances[textareaId];
    if (!instance) return null;
    var html = instance.sourceTextarea ? instance.sourceTextarea.value : instance.editor.innerHTML;
    return stripTrailingEmptyParagraphs(html);
  }

  function insertContent(textareaId, html) {
    var instance = instances[textareaId];
    if (!instance) return;
    var range = getEditorRange(instance.editor);
    if (!range) {
      instance.editor.focus();
      range = document.createRange();
      range.selectNodeContents(instance.editor);
      range.collapse(false);
    }
    var frag = range.createContextualFragment(html);
    range.deleteContents();
    range.insertNode(frag);
    syncTextarea(instance);
  }

  return {
    init: init,
    destroy: destroy,
    getContent: getContent,
    insertContent: insertContent
  };
})();
