<?php
require_once 'check_login.php';
require_once '../Seite.php';
class BeratungSeite extends Seite {
  function __construct($titel,$baseDir='',$target=null) {
    parent::__construct($titel,$baseDir,$target);
  }
  function getTitle() {
    return 'GPB Beratung - '.$this->titel;
  }
}
Seite::$menueByUrl['logout.php']=new BeratungSeite('Logout');
Seite::$menueByUrl['berater.php']=new BeratungSeite('Berater');
Seite::$menueByUrl['eignungstests.php']=new BeratungSeite('Eignungstests');
Seite::$menueByUrl['intrain.php']=new BeratungSeite('inTrain');
Seite::$menueByUrl['spezialisten.php']=new BeratungSeite('Spezialisten');
Seite::$menueByUrl['vertragszahlen.php']=new BeratungSeite('Vertragszahlen');
Seite::$menueByUrl['ferien.php']=new BeratungSeite('Ferien');
?>