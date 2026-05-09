<?php
class Massnahme {
  static function eineLaden($id,$className) {
    global $db;
    if($id>0){
      $result=$db->query("select m.* from gpb_massnahme_view m where m.id=".$id." limit 1");
      $massnahme=$result->fetch_object($className);
      $result->free();
    } else {
      $massnahme=false;
    }
    if(!empty($massnahme)) {
      $massnahme->init();
    }
    return $massnahme;
  }
  
  function init() {
    $this->von=empty($this->beginn) || $this->beginn=='0000-00-00' ? 0 : strtotime($this->beginn);
    $this->bis=empty($this->ende) || $this->ende=='0000-00-00' ? 0 : strtotime($this->ende);
  }
  
  function ferienLaden() {
    global $db;
    $this->ferien=array();
    $this->ferienids=array();
    $result=$db->query("select * from gpb_ferien where (art='Feiertag' or art='GPB Ferien') and beginn<='".date('Y-m-d',$this->bis)."' and ende>='".date('Y-m-d',$this->von)."' order by beginn,ende,art");
    while($row=$result->fetch_object()) {
      $row->von=strtotime($row->beginn);
      $row->bis=strtotime($row->ende);
      $this->ferien[]=$row;
      $this->ferienids[]=$row;
    }
    $result->free();
    $result=$db->query("select f.* from gpb_ferien f where f.art='Institutsferien'
      and f.beginn<='2025-02-19' and f.ende>='2023-02-20'
      and exists(
        select k.* from gpb_klasse k
        where k.ort=f.ort and f.beginn<=k.ende and f.ende>=k.beginn and k.id in(
          select ktn.klasseid from gpb_klasse_tn ktn where ktn.tnid in(
            select mtn.tnid from gpb_massnahme_tn mtn where mtn.massnahmeid=9844
          )
        )
      )
      order by f.beginn,f.ende,f.art");
    while($row=$result->fetch_object()) {
      $row->von=strtotime($row->beginn);
      $row->bis=strtotime($row->ende);
      $this->ferien[]=$row;
      $this->ferienids[]=$row;
    }
    $result->free();
    $result=$db->query("select f.* from gpb_ferien f where f.art='Klassenferien' and f.id in(
        select kf.ferienid from gpb_klasse_ferien kf where kf.klasseid in(
          select ktn.klasseid from gpb_klasse_tn ktn where ktn.tnid in(
            select mtn.tnid from gpb_massnahme_tn mtn where mtn.massnahmeid=9844
          )
        )
      ) order by f.beginn,f.ende,f.art");
    while($row=$result->fetch_object()) {
      $row->von=strtotime($row->beginn);
      $row->bis=strtotime($row->ende);
      $this->ferien[]=$row;
      $this->ferienids[]=$row;
    }
    $result->free();
  }
  
  function makeSehen() {
?>
<table border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th>Titel</th>
    <td><?= $this->titel ?></td>
  </tr>
  <tr>
    <th>Kürzel</th>
    <td><?= $this->kuerzel ?></td>
  </tr>
  <tr>
    <th>Beruf</th>
    <td><?= $this->berufkuerzel ?></td>
  </tr>
  <tr>
    <th>Beginn</th>
    <td><?= empty($this->von) ? '-' : date('d.m.Y',$this->von) ?></td>
  </tr>
  <tr>
    <th>Ende</th>
    <td><?= empty($this->bis) ? '-' : date('d.m.Y',$this->bis) ?></td>
  </tr>
  <tr>
    <th>Anzahl TN</th>
    <td><?= $this->anzahlTN ?></td>
  </tr>
  <tr>
    <th></th>
<?php
    $this->makeBearbeitenTd();
?>
  </tr>
</table>
<br />
<?php
  }
  function makeBearbeitenTd() {
?>
    <td></td>
<?php
  }
}
?>