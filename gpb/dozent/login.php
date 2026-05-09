<?php
require_once '../login_funktion.php';
$ich=login('gpb_dozent');
if($ich) {
  $_SESSION['dozent_ich']=$ich;
  if($ich->istintrain) {
    $_SESSION['intrain_ich']=$ich;
  }
  if(isset($_SESSION['redirect'])) {
    $url=$_SESSION['redirect'];
    unset($_SESSION['redirect']);
    if(strpos($url,'/dozent/')!==false || ($ich->intrain && strpos($url,'/intrain/')!==false)) {
      header('Location:'.$url);
      exit;
    }
  }
  header('Location:index.php');
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <link rel="stylesheet" href="styles.css" />
  <title>GPB Dozenten-Login</title>
</head>
<body>
<h1>GPB Dozenten-Login</h1>
<div id="inhalt">
<form action="login.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Nutzername oder Emailadresse</th>
    <td><input type="text" name="nutzername" value="<?= htmlentities($nutzername,ENT_COMPAT) ?>" style="width:200px;" /></td>
  </tr>
  <tr>
    <th>Passwort</th>
    <td><input type="password" name="passwort" value="" style="width:200px;" /></td>
  </tr>
  <tr>
    <th></th>
    <td><input type="submit" value="Einloggen" /></td>
  </tr>
</table>
</form>
</div>
</body>
</html>