<?php
require_once 'Suche.php';
@session_start();
function logout() {
  setcookie('nutzername','',time()-60*60,'/');
  setcookie('passwort','',time()-60*60,'/');
}
?>