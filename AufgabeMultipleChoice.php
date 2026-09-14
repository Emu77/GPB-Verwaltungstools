<?php
require_once 'Aufgabe.php';
class AufgabeMultipleChoice extends Aufgabe {
  // Fragen + Optionen, automatische Bewertung. Detailimplementierung folgt.

  function kannAutomatischBewerten() {
    return true;
  }
}
?>
