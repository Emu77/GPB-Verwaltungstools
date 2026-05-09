<?php
require_once '../Seite.php';
class IntrainSeite extends Seite {
  function __construct($titel,$baseDir='',$target=null) {
    parent::__construct($titel,$baseDir,$target);
  }
  function getTitle() {
    return 'inTrain - '.$this->titel;
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
Seite::$menueByUrl['logout.php']=new IntrainSeite('Logout');
Seite::$menueByUrl['index.php']=new IntrainSeite('Anwesenheit');
Seite::$menueByUrl['tn.php']=new IntrainSeite('Teilnehmer');
Seite::$menueByUrl['selfkurse.php']=new IntrainSeite('inTrain-Kurse');
Seite::$menueByUrl['../dozent/']=new IntrainSeite('Dozenten-Bereich','','dozent');
?>