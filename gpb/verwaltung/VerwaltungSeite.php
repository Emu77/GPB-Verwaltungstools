<?php
require_once '../Seite.php';
class VerwaltungSeite extends Seite {
  function __construct($titel,$baseDir='',$target=null) {
    parent::__construct($titel,$baseDir,$target);
  }
  function getTitle() {
    return 'Verwaltung - '.$this->titel;
  }
  function endeGenerieren() {
    if(isset($_SESSION['fehler'])) {
      foreach($_SESSION['fehler'] as $k=>$txt) {
?>
<div class="<?= $k=='done' ? 'done' : 'fehler' ?>"><?= $k=='done' ? '' : $k.' ' ?><?= $txt ?></div>
<?php
      }
      unset($_SESSION['fehler']);
    }
    parent::endeGenerieren();
  }
}
Seite::$menueByUrl['logout.php']=new VerwaltungSeite('Logout');
Seite::$menueByUrl['../verwaltung/verwalter.php']=new VerwaltungSeite('Verwalter');
Seite::$menueByUrl['../verwaltung/berufe.php']=new VerwaltungSeite('Berufe');
if($ich->massnahmenanzeigen) Seite::$menueByUrl['../verwaltung/massnahmen.php']=new VerwaltungSeite('Maßnahmen');
Seite::$menueByUrl['../verwaltung/klassen.php']=new VerwaltungSeite('Klassen');
Seite::$menueByUrl['../verwaltung/ferien.php']=new VerwaltungSeite('Ferien');
Seite::$menueByUrl['../verwaltung/tn.php']=new VerwaltungSeite('Teilnehmer');
Seite::$menueByUrl['../verwaltung/dozenten.php']=new VerwaltungSeite('Dozenten');
Seite::$menueByUrl['../verwaltung/kurse.php']=new VerwaltungSeite('Kurse');
Seite::$menueByUrl['../verwaltung/nachschreiber.php']=new VerwaltungSeite('Nachschreiber');
Seite::$menueByUrl['../verwaltung/raeume.php']=new VerwaltungSeite('Räume');
Seite::$menueByUrl['../verwaltung/raumplan.php']=new VerwaltungSeite('Raumplan');
?>