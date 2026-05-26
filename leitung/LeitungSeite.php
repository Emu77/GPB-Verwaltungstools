<?php
require_once '../verwaltung/VerwaltungSeite.php';
class LeitungSeite extends VerwaltungSeite {
  function __construct($titel,$baseDir='',$target=null) {
    parent::__construct($titel,$baseDir,$target);
  }
  function getTitle() {
    return 'Leitung - '.$this->titel;
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
Seite::$menueByUrl['../verwaltung/planung.php']=new LeitungSeite('Planung');
Seite::$menueByUrl['../leitung/bewertung.php']=new LeitungSeite('Bewertungen');
?>