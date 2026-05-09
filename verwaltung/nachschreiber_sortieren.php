<?php
require_once 'check_login.php';
$kriterium=isset($_GET['kriterium']) ? $_GET['kriterium'] : false;
$sortierung=isset($_SESSION['nachschreiber_sortierung']) ? $_SESSION['nachschreiber_sortierung'] : array('n.nachschreibetermin','k.titel','tn.nachname');
if($kriterium && in_array($kriterium,$sortierung)) {
  $sortierung=array_diff($sortierung,array($kriterium));
  $sortierung=array_merge(array($kriterium),$sortierung);
  $_SESSION['nachschreiber_sortierung']=$sortierung;
}
header('Location:nachschreiber.php');
exit;
?>