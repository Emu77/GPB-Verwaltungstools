<?php
class DozentVerfuegbarkeit {
  static $instanzen=array();
  static $instanzenByWert=array();
  
  static function makeSelect($ausgewaehlt) {
?>
    <select name="verfuegbar">
<?php
    foreach(DozentVerfuegbarkeit::$instanzen as $instanz) {
?>
      <option value="<?= htmlentities($instanz->wert,ENT_COMPAT) ?>" <?= $ausgewaehlt==$instanz->wert ? 'selected' : '' ?>><?= $instanz->wert ?></option>
<?php
    }
?>
    </select>
<?php
  }
  
  function __construct($wert,$verfuegbar,$farbe) {
    $this->wert=$wert;
    $this->verfuegbar=$verfuegbar;
    $this->farbe=$farbe;
    DozentVerfuegbarkeit::$instanzen[]=$this;
    DozentVerfuegbarkeit::$instanzenByWert[$this->wert]=$this;
  }
}
new DozentVerfuegbarkeit('Verfügbar',true,'rgba(0,255,0,0.1)');
new DozentVerfuegbarkeit('Nicht verfügbar',false,'rgba(255,0,0,0.2)');
new DozentVerfuegbarkeit('Zu klären',false,'rgba(255,128,0,0.2)');
new DozentVerfuegbarkeit('Angefragt',false,'rgba(255,255,0,0.4)');
new DozentVerfuegbarkeit('Bestätigt',true,'rgba(0,255,0,0.2)');
new DozentVerfuegbarkeit('Urlaub',false,'rgba(255,0,0,0.2)');
?>