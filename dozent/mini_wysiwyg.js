/**
 * Custom WYSIWYG-Editor für gpb_praktikum (Ersatz für TinyMCE)
 * Phase 2: Editor-Grundgerüst
 *   - HTML-Grundgerüst (contenteditable-Fläche + Toolbar-Markup)
 *   - Aufbau Selection-/Range-Zugriff
 *   - Formatierungsfunktionen Fett/Kursiv/Unterstrichen/Farbe inkl. Toggle-Logik
 *
 * Kein document.execCommand() (deprecated) - Formatierung wird selbst über
 * die Range-API umgesetzt, analog zum Prinzip der bestehenden Plain-Text-
 * Toolbar (mini-format-btn: öffnen/schließen bzw. Toggle bei exakter Markierung).
 *
 * Einbindung pro Textarea (später in mini_kurs_sehen.php statt tinymce.init):
 *   MiniWysiwyg.init(textareaElement);
 *
 * Bekannte Einschränkung (wie beim bisherigen Plain-Text-Toggle bewusst
 * in Kauf genommen): bei überlappenden/verschachtelten Markierungen kann
 * unsauberes HTML entstehen. Für Phase 3/4 ggf. verfeinern.
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
      '<button type="button" class="mini-cwysiwyg-btn" data-cmd="removeformat" title="Formatierung entfernen">⨯</button>';
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
  // Deshalb: Text durch einen reinen Textknoten ersetzen und danach jede
  // umschließende Formatierung (deren gesamter Inhalt jetzt nur noch dieser
  // Text ist) gezielt von außen nach innen auflösen.
  function removeFormatting(editor) {
    var range = getEditorRange(editor);
    if (!range || range.collapsed) return;

    var text = range.toString();
    range.deleteContents();
    var textNode = document.createTextNode(text);
    range.insertNode(textNode);

    var changed = true;
    while (changed) {
      changed = false;
      var ancestor = closestWithinEditor(textNode, 'strong, em, u, span', editor);
      if (ancestor && ancestor.textContent === text) {
        unwrapElement(ancestor);
        changed = true;
      }
    }

    // Keine exakte Reselektion des Textknotens: unwrapElement() ruft intern
    // parent.normalize() auf, das benachbarte Textknoten verschmilzt - der
    // ursprüngliche textNode wäre danach ggf. bereits aus dem DOM entfernt
    // (führte zu "InvalidNodeTypeError"). Selektion daher einfach leeren.
    window.getSelection().removeAllRanges();
    normalizeInlineTags(editor);
  }

  // ---------------------------------------------------------------------
  // Sync mit der ursprünglichen Textarea (Formular-Kompatibilität)
  // ---------------------------------------------------------------------

  function syncTextarea(instance) {
    instance.textarea.value = instance.editor.innerHTML;
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

      var cmd = btn.getAttribute('data-cmd');
      switch (cmd) {
        case 'bold': toggleInline(editor, 'strong'); break;
        case 'italic': toggleInline(editor, 'em'); break;
        case 'underline': toggleInline(editor, 'u'); break;
        case 'color': toggleColor(editor, btn.getAttribute('data-color')); break;
        case 'removeformat': removeFormatting(editor); break;
      }
      syncTextarea(instance);
    });

    editor.addEventListener('input', function () { syncTextarea(instance); });
  }

  function destroy(textareaId) {
    var instance = instances[textareaId];
    if (!instance) return;
    syncTextarea(instance);
    instance.wrapper.remove();
    instance.textarea.style.display = '';
    delete instances[textareaId];
  }

  function getContent(textareaId) {
    var instance = instances[textareaId];
    return instance ? instance.editor.innerHTML : null;
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
