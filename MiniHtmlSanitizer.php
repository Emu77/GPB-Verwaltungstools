<?php
/**
 * Serverseitige Sanitisierung für den Mini-WYSIWYG-Editor.
 * Whitelist-Prinzip: nur explizit erlaubte Tags/Attribute bleiben erhalten,
 * alles andere wird entfernt bzw. der Tag selbst entfernt (Inhalt ggf. promoted).
 */

function gpbMiniHtmlSaeubern(string $html): string
{
    $erlaubteTags = ['strong', 'em', 'u', 'span', 'ul', 'ol', 'li', 'p', 'br', 'img'];

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);

    // UTF-8 sicherstellen, in Wrapper-Div packen, damit wir einen definierten Root haben
    $wrapped = '<?xml encoding="utf-8" ?><div>' . $html . '</div>';
    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $wurzel = $dom->getElementsByTagName('div')->item(0);
    if ($wurzel === null) {
        return '';
    }

    gpbMiniKnotenSaeubern($wurzel, $erlaubteTags);

    $ergebnis = '';
    foreach ($wurzel->childNodes as $kind) {
        $ergebnis .= $dom->saveHTML($kind);
    }

    return $ergebnis;
}

function gpbMiniKnotenSaeubern(DOMNode $knoten, array $erlaubteTags): void
{
    // Snapshot der Kinder, da wir die Liste während des Durchlaufs verändern
    $kinder = iterator_to_array($knoten->childNodes);

    foreach ($kinder as $kind) {
        if ($kind->nodeType === XML_TEXT_NODE) {
            continue;
        }

        if ($kind->nodeType !== XML_ELEMENT_NODE) {
            // Kommentare, CDATA etc. raus
            $knoten->removeChild($kind);
            continue;
        }

        /** @var DOMElement $kind */
        $tagName = strtolower($kind->tagName);

        if (!in_array($tagName, $erlaubteTags, true)) {
            // gefährliche Tags: komplett samt Inhalt entfernen
            if (in_array($tagName, ['script', 'iframe', 'object', 'style', 'embed', 'link', 'meta'], true)) {
                $knoten->removeChild($kind);
                continue;
            }

            // sonstige nicht erlaubte Tags: zuerst die Kinder dieses Tags selbst
            // rekursiv säubern (sonst würde z.B. ein <b> oder <script> innerhalb
            // eines <div> beim Hochziehen ungeprüft "durchrutschen", da es nicht
            // mehr im äußeren Kinder-Snapshot dieser Schleife enthalten ist),
            // dann Tag entfernen und die (bereits sauberen) Kinder hochziehen
            gpbMiniKnotenSaeubern($kind, $erlaubteTags);
            while ($kind->firstChild) {
                $knoten->insertBefore($kind->firstChild, $kind);
            }
            $knoten->removeChild($kind);
            continue;
        }

        // rekursiv zuerst die Kinder säubern, dann die Attribute dieses Knotens
        gpbMiniKnotenSaeubern($kind, $erlaubteTags);
        gpbMiniAttributeSaeubern($kind, $tagName);
    }
}

function gpbMiniAttributeSaeubern(DOMElement $element, string $tagName): void
{
    $attribute = iterator_to_array($element->attributes ?? []);

    foreach ($attribute as $attr) {
        $name = strtolower($attr->name);

        if ($tagName === 'span' && $name === 'style') {
            $neuerStyle = gpbMiniStyleFiltern($attr->value);
            if ($neuerStyle === '') {
                $element->removeAttribute($attr->name);
            } else {
                $element->setAttribute('style', $neuerStyle);
            }
            continue;
        }

        if ($tagName === 'img' && $name === 'src') {
            if (!gpbMiniBildQuelleGueltig($attr->value)) {
                $element->removeAttribute($attr->name);
            }
            continue;
        }

        if ($tagName === 'img' && $name === 'alt') {
            continue; // alt bleibt unverändert erlaubt
        }

        // alles andere: onclick, onload, class, id, style (außer span), href, ...
        $element->removeAttribute($attr->name);
    }
}

function gpbMiniStyleFiltern(string $style): string
{
    // nur "color: ..." erlauben, alles andere im style-Attribut verwerfen
    if (preg_match('/color\s*:\s*(#[0-9a-fA-F]{3,8}|rgb\([0-9,\s]+\)|[a-zA-Z]+)\s*;?/', $style, $treffer)) {
        return rtrim(trim($treffer[0]), ';') . ';';
    }
    return '';
}

function gpbMiniBildQuelleGueltig(string $src): bool
{
    $src = trim($src);
    if ($src === '') {
        return false;
    }

    // Protokoll-Angaben komplett verbieten (auch //example.com)
    if (preg_match('#^([a-zA-Z][a-zA-Z0-9+.-]*:)?//#', $src)) {
        return false;
    }
    if (stripos($src, 'javascript:') === 0 || stripos($src, 'data:') === 0) {
        return false;
    }

    // nur Pfade akzeptieren, die auf den bekannten Download-Endpunkt zeigen
    // z.B. "mini_anhang_download.php?id=123" oder "/gpb/dozent/mini_anhang_download.php?id=123"
    $pfad = parse_url($src, PHP_URL_PATH);
    if ($pfad === false || $pfad === null) {
        return false;
    }

    return basename($pfad) === 'mini_anhang_download.php';
}
