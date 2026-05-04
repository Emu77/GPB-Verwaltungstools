<?php
class Seite {
  static $menueByUrl=array();
  
  function __construct($titel,$baseDir='',$target=null) {
    $this->titel=$titel;
    $this->baseDir=$baseDir;
    $this->target=$target;
  }
  function getTitle() {
    return $this->titel;
  }
  function anfangGenerieren() {
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <link rel="stylesheet" href="<?= $this->baseDir ?>styles.css" />
  <title><?= $this->getTitle() ?></title>
</head>
<body>
<div id="menue">
<?php
    $this->menueGenerieren();
?>
</div>
<h1><?= $this->getTitle() ?></h1>
<div id="inhalt">
<?php
    if(isset($_SESSION['nachricht'])) {
      echo $_SESSION['nachricht'];
      unset($_SESSION['nachricht']);
    }
  }
  function menueGenerieren() {
    global $ich;
    foreach(Seite::$menueByUrl as $url=>$seite) {
      if($seite->titel=='Logout') {
?>
  <span style="float:right;"><?= empty($ich) ? '' : $ich->vorname.' '.$ich->nachname ?> &nbsp; <a href="<?= $this->baseDir ?><?= $url ?>" <?= $seite->target ? 'target="<?= $seite->target ?>"' : '' ?>>Logout</a></span>
<?php
      } else {
?>
  <a href="<?= $this->baseDir ?><?= $url ?>" <?= $seite->target ? 'target="<?= $seite->target ?>"' : '' ?>><?= $seite->titel ?></a>
<?php
      }
    }
  }
  function endeGenerieren() {
?>
</div>
</body>
</html>
<?php
    exit;
  }
}
?>