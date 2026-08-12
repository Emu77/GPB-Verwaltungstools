<?php
require_once '../Seite.php';
class DozentSeite extends Seite {
  function __construct($titel,$baseDir='',$target=null) {
    parent::__construct($titel,$baseDir,$target);
  }
  function getTitle() {
    return 'Dozent - '.$this->titel;
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
#todos details .zuklappen {
  display:none;
}
#todos details[open] .aufklappen {
  display:none;
}
#todos details[open] .zuklappen {
  display:inline;
}
</style>
<div id="todos">
<details>
  <summary class="todo"><?= count($todos) ?> Todo<?= count($todos)==1 ? '' : 's' ?> <span class="aufklappen">aufklappen</span><span class="zuklappen">zuklappen</span></summary>
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
      <?= $kurs->kurzbericht_ok ? '' : '<a href="kurzbericht.php?kursid='.$kurs->id.'" class="todo">Kurzbericht</a>' ?>
      <?= $kurs->tagesbericht_ok ? '' : '<a href="tagesbericht.php?kursid='.$kurs->id.'" class="todo">Tagesbericht</a>' ?>
      <?= $kurs->notenstatus=='todo' ? '<a href="kurs_il_noten.php?kursid='.$kurs->id.'" class="todo">Noten</a>' : '' ?>
    </td>
  </tr>
<?php
      }
?>
</table>
</details>
<?php
    }
  }
}
Seite::$menueByUrl['logout.php']=new DozentSeite('Logout');
Seite::$menueByUrl['index.php']=new DozentSeite('Anwesenheit');
Seite::$menueByUrl['kurse.php']=new DozentSeite('Kurse');
Seite::$menueByUrl['kursvorlagen.php']=new DozentSeite('Kursvorlagen');
Seite::$menueByUrl['raumplan.php']=new DozentSeite('Raumplan');
Seite::$menueByUrl['faq']=new DozentSeite('FAQ','','faq');
?>