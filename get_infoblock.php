<?php
require_once 'Suche.php';
@session_start();
header('Access-Control-Allow-Origin: *');
header('Content-Type:text/plain; charset=utf-8');

if(!isset($_SESSION['ich']) && !isset($_SESSION['moodle_ich'])) {
  
  $courseid=isset($_GET['courseid']) ? (int)$_GET['courseid'] : 0;
  $userfullname=isset($_GET['userfullname']) ? $_GET['userfullname'] : false;
  
  if(!empty($userfullname)) {
    require_once 'db.php';
    function such($sql,$type,$value) {
      global $db;
      $ergebnis=false;
      $stmt=$db->prepare($sql);
      $stmt->bind_param($type,$value);
      $stmt->execute();
      $result=$stmt->get_result();
      if($result->num_rows==1) {
        $ergebnis=$result->fetch_object();
      }
      $result->free();
      return $ergebnis;
    }
    if(!isset($_SESSION['moodle_ich'])) {
      $ergebnis=such("select * from gpb_tn_view where tnname=?",'s',$userfullname);
      if($ergebnis) {
        $ergebnis->rolle='TN';
        $_SESSION['moodle_ich']=$ergebnis;
      }
    }
    if(!isset($_SESSION['moodle_ich'])) {
      $ergebnis=such("select * from gpb_dozent where concat(concat(vorname,' '),nachname)=?",'s',$userfullname);
      if($ergebnis) {
        $ergebnis->rolle='Dozent';
        $_SESSION['moodle_ich']=$ergebnis;
      }
    }
    if(!isset($_SESSION['moodle_ich'])) {
      $ergebnis=such("select * from gpb_verwalter where concat(concat(vorname,' '),nachname)=?",'s',$userfullname);
      if($ergebnis) {
        $ergebnis->rolle='Verwalter';
        $_SESSION['moodle_ich']=$ergebnis;
      }
    }
    if(!isset($_SESSION['moodle_ich'])) {
      $daten=(object)array(
        'fullname'=>$userfullname
      );
      $url=$moodleurl.'webservice/rest/server.php?wstoken='.$moodletoken.'&wsfunction=local_gpbwebservice_bearbeite_anfragen&moodlewsrestformat=json&aktion=nutzer_finden&daten='.urlencode(json_encode($daten));
      $ergebnis=json_decode(file_get_contents($url));
      if(is_object($ergebnis) && isset($ergebnis->exception)) {
        $infos='<script>console.log(.json_encode($ergebnis).)</script>';
      } else if($ergebnis) {
        if(!is_object($ergebnis)) {
          $ergebnis=json_decode($ergebnis);
        }
        $moodleid=$ergebnis->id;
        if(!isset($_SESSION['moodle_ich'])) {
          $ergebnis=such("select * from gpb_tn_view where moodleid=?",'i',$moodleid);
          if($ergebnis) {
            $ergebnis->rolle='TN';
            $_SESSION['moodle_ich']=$ergebnis;
          }
        }
        if(!isset($_SESSION['moodle_ich'])) {
          $ergebnis=such("select * from gpb_dozent where moodleid=?",'i',$moodleid);
          if($ergebnis) {
            $ergebnis->rolle='Dozent';
            $_SESSION['moodle_ich']=$ergebnis;
          }
        }
        if(!isset($_SESSION['moodle_ich'])) {
          $ergebnis=such("select * from gpb_verwalter where moodleid=?",'i',$moodleid);
          if($ergebnis) {
            $ergebnis->rolle='Verwalter';
            $_SESSION['moodle_ich']=$ergebnis;
          }
        }
      }
    }
  }
}

if(isset($_SESSION['verwalter_ich']) || (isset($_SESSION['moodle_ich']) && $_SESSION['moodle_ich']->rolle='Verwalter')) {
  echo 'Verwalter';
} else if(isset($_SESSION['dozent_ich']) || (isset($_SESSION['moodle_ich']) && $_SESSION['moodle_ich']->rolle='Dozent')) {
  echo '<a href="https://verwaltung.gpbmedien.de/dozent/index.php">Anwesenheit</a>';
} else if(isset($_SESSION['tn_ich']) || (isset($_SESSION['moodle_ich']) && $_SESSION['moodle_ich']->rolle='TN')) {
  echo 'TN';
}
exit;
?>