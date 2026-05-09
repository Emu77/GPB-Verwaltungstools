<?php
/**
 * Ein Termin der Prüfungsvorbereitung
 */
class PVTermin {  
  
  function init() {
    $this->von=strtotime($this->beginn.' '.$this->beginn_uhrzeit);
    $this->bis=strtotime($this->ende.' '.$this->ende_uhrzeit);
    $this->keinEnde=(empty($this->ende) || $this->ende=='0000-00-00') && (empty($this->ende_uhrzeit) || $this->ende_uhrzeit=='00:00:00');
  }
}
?>