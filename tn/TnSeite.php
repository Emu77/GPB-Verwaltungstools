<?php
require_once 'check_login.php';
require_once '../Seite.php';
class TnSeite extends Seite {
  function __construct($titel,$baseDir='',$target=null) {
    parent::__construct($titel,$baseDir,$target);
  }
  function getTitle() {
    return 'GPB - '.$this->titel;
  }
   function menueGenerieren() {
    parent::menueGenerieren();
    global $todos;
    if(!empty($todos)) {
?>
</div>
<style>
#todos {
  background-color:white;
  padding:10px;
  padding-top:0;
  margin-bottom:1em;
}
#todos * {
  font-size:16px;
}
#todos h2 {
  font-size:22px;
  margin:0;
  padding:10px 10px 1em 0;
}
</style>
<div id="todos"><h2 class="todo">TODO</h2>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Kurs</th>
    <th>Beginn</th>
    <th>Ende</th>
    <th></th>
  </tr>
<?php
      foreach($todos as $kurs) {
?>
  <tr>
    <td><a href="kurs_sehen.php?kursid=<?= $kurs->id ?>"><?= $kurs->titel ?></a></td>
    <td><?= date('d.m.Y',$kurs->von) ?></td>
    <td><?= date('d.m.Y',$kurs->bis) ?></td>
    <td>
      <a href="kurs_bewertung.php?kursid=<?= $kurs->id ?>" class="todo">Bewerten</a>
    </td>
  </tr>
<?php
      }
?>
</table>
<?php
    }
  }
}
Seite::$menueByUrl['logout.php']=new TnSeite('Logout');
Seite::$menueByUrl['kurse.php']=new TnSeite('Kurse');
Seite::$menueByUrl['anwesenheit.php']=new TnSeite('Anwesenheit');
Seite::$menueByUrl['noten.php']=new TnSeite('Noten');
if($ich->berichtsheft_offen) {
  Seite::$menueByUrl['berichtsheft.php']=new TnSeite('Berichtsheft');
}
Seite::$menueByUrl['ferien.php']=new TnSeite('Ferien');
Seite::$menueByUrl['raumplan.php']=new TnSeite('Raumplan');
?>