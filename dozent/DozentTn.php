<?php
require_once '../Tn.php';
class DozentTn extends Tn {
  
  static function makeHeaderTr($pv=false) {
    global $moodleisttest;
?>
  <tr>
    <th>Anrede</th>
    <th>Vorname</th>
    <th>Nachname</th>
    <th>Beruf</th>
    <th class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle-ID</th>
<?php
    if($pv) {
?>
    <th>PV</th>
<?php
    }
?>
  </tr>
<?php
  }
  
  function init() {
    parent::init();
    $this->anwesenheiten=array();
  }
  
  function makeTr($pv=false) {
?>
  <tr>
<?php
    $this->makeNameTds();
    $this->makeBerufTd();
    $this->makeMoodleTd();
    if($pv) {
      $this->makePVTd();
    }
?>
  </tr>
<?php
  }
  function makePVTd() {
    $pv='';
    if(isset($this->pv)) {
      $pv=$this->pv->hatAP1 ? 'AP1' : '';
      $pv.=$this->pv->hatAP2 ? (empty($pv) ? 'AP2/schriftliche' : ' + AP2/schriftliche') : '';
      $pv.=$this->pv->hatMuendliche ? (empty($pv) ? 'mündliche/Projekt/Fachgespräch' : ' + mündliche/Projekt/Fachgespräch') : '';
    }
?>
    <td><?= $pv ?></td>
<?php
  }
  
  static function makeCSVHeader($out,$windows) {
    fputcsv($out,array('Anrede','Vorname','Nachname','Beruf'),';');
  }
  function makeCSV($out,$windows) {  
    $data=array(
      $this->anrede
      ,$this->vorname
      ,$this->nachname
      ,$this->berufkuerzel
    );
    if($windows) {
      $data=mb_convert_encoding($data,'iso-8859-15','utf-8');
    }
    fputcsv($out,$data,';');
  }
}
?>