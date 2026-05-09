<?php
require_once 'check_login.php';

$zusaetzlicheklassen=isset($_POST['zusaetzlicheklassen']) ? str_replace("\r","\n",str_replace("\r\n","\n",$_POST['zusaetzlicheklassen'])) : false;
if(!empty($zusaetzlicheklassen)) {
  if(file_exists('vonmitis/zusaetzlicheklassen.txt')) {
    if(file_exists('vonmitis/zusaetzlicheklassen.txt.bak')) {
      unlink('vonmitis/zusaetzlicheklassen.txt.bak');
    }
    rename('vonmitis/zusaetzlicheklassen.txt','vonmitis/zusaetzlicheklassen.txt.bak');
  }
  file_put_contents('vonmitis/zusaetzlicheklassen.txt',$zusaetzlicheklassen);
  $_SESSION['zusaetzlicheklassen_nachricht']='<div style="color:green;">Gespeichert!</div>';
}
header('Location:klassen_zusaetzlich.php');
exit;
?>