<?php
require_once '../../db.php';
$klassen=array();
$klassenById=array();
$result=$db->query("select id,bezeichnung,moodleid from gpb_klasse where length(moodleid)>0 and moodleid<>'0' order by id desc");
while($row=$result->fetch_object()){
  $row->tnmoodleids=array();
  $klassen[]=$row;
  $klassenById[$row->id]=$row;
}
$result->free();
$result=$db->query("select distinct ktn.klasseid,tn.moodleid
  from gpb_klasse_tn ktn
  join gpb_tn tn on tn.id=ktn.tnid
  where length(tn.moodleid)>0
  and ktn.einstieg is not null and ktn.einstieg<>'0000-00-00'");
while($row=$result->fetch_object()) {
  if(isset($klassenById[$row->klasseid])) {
    $klassenById[$row->klasseid]->tnmoodleids[]=$row->moodleid;
  }
}
$result->free();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <link rel="stylesheet" href="styles.css" />
  <title>Aktualisierung der Anmeldungen der TN in Klassen-Kurse</title>
  <script>
  const klassen=<?= json_encode($klassen) ?>;
  var idx=0;
  var timer=null;
  var stoppen=true;
  function aktualisieren() {
    if(timer) {
      clearTimeout(timer);
      timer=null;
    }
    if(idx>=klassen.length) {
      let div=document.createElement('div');
      div.innerText='Fertig!';
      document.body.appendChild(div);
      return;
    }
    let klasse=klassen[idx];
    document.getElementById('klasse_td').innerText=klasse.bezeichnung+' ('+klasse.tnmoodleids.length+' TN)';
    document.getElementById('ok_td').innerText='';
    
    let daten={
      'rolename':'student',
      'courseid':klasse.moodleid,
      'userids':klasse.tnmoodleids,
      'ersetzen':true
    };
    const req=new XMLHttpRequest();
    req.onreadystatechange = function() {
      if (req.readyState != 4) return;
      let resp=JSON.parse(req.responseText);
      if (resp!='ok' && resp!='"ok"') {
        let div=document.createElement('div');
        div.innerText=klasse.bezeichnung+' Moodleid= '+klasse.moodleid+'\n'+req.responseText;
        document.getElementById('fehler_td').appendChild(div);
      } else {
        document.getElementById('ok_td').innerText='OK';
      }
    };
    req.open('GET','<?= $moodleurl ?>webservice/rest/server.php?wstoken=<?= $moodletoken ?>&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_zuweisen&daten='+encodeURIComponent(JSON.stringify(daten)));   
    req.send();
    ++idx;
    document.getElementById('idx_td').innerText=idx;
    if(!stoppen) {
      timer=setTimeout(aktualisieren,500);
    }
  }
  function stop() {
    if(stoppen) {
      stoppen=false;
      aktualisieren();
    } else {
      stoppen=true;
    }
  }
  </script>
</head>
<body>
<h1>Aktualisierung der Anmeldungen der TN in Klassen-Kurse</h1>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Anzahl Klassen</th>
    <td><?= count($klassen) ?></td>
  </tr>
  <tr>
    <th></th>
    <td><button type="button" onclick="stop()">Stop/Start</button></td>
  </tr>
  <tr>
    <th>Schon gemacht</th>
    <td id="idx_td">0</td>
  </tr>
  <tr>
    <th>Aktuell</th>
    <td id="klasse_td"></td>
  </tr>
  <tr>
    <th>OK</th>
    <td id="ok_td"></td>
  </tr>
  <tr>
    <th>Fehler</th>
    <td id="fehler_td"></td>
  </tr>
</table>
</body>
</html>