<?php
require_once '../Seite.php';
class InternSeite extends Seite {
  function __construct($titel,$url='') {
    parent::__construct($titel,$url);
  }
  function getTitle() {
    return 'GPB - '.$this->titel;
  }
}
//Seite::$menueByUrl['index.php']=new InternSeite('Raumplan');
?>