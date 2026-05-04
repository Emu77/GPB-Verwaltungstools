<?php
namespace local_gpbwebservice;

require_once("$CFG->libdir/externallib.php");

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

class bearbeite_anfragen extends \external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'aktion' => new external_value(
                PARAM_RAW,
                'Die Aktion'
            ),
            'daten' => new external_value(
                PARAM_RAW,
                'Die Daten, JSON-enkodiert'
            ),
        ]);
    }
    
    public static function execute_returns(): external_value {
        return new external_value(PARAM_RAW, 'Die Ausgabe');
    }

    public static function execute(string $aktion,string $daten): string {
        $params = self::validate_parameters(self::execute_parameters(), [
            'aktion' => $aktion,
            'daten' => $daten
        ]);
        $daten=json_decode($params['daten']);
        $ergebnis='NOK';
        switch($params['aktion']) {
          case 'kurs_speichern': {
            $ergebnis=bearbeite_anfragen::kurs_speichern($daten);
          } break;
          case 'kurs_loeschen': {
            $ergebnis=bearbeite_anfragen::kurs_loeschen($daten);
          } break;
          case 'nutzer_speichern': {
            $ergebnis=bearbeite_anfragen::nutzer_speichern($daten);
          } break;
          case 'nutzer_finden': {
            $ergebnis=bearbeite_anfragen::nutzer_finden($daten);
          } break;
          case 'nutzer_zuweisen': {
            $ergebnis=bearbeite_anfragen::nutzer_zuweisen($daten);
          } break;
          case 'nutzer_einschreibung': {
            $ergebnis=bearbeite_anfragen::nutzer_einschreibung($daten);
          } break;
          case 'meta_einschreibung': {
            $ergebnis=bearbeite_anfragen::meta_einschreibung($daten);
          } break;
          case 'auswerten': {
            $ergebnis=bearbeite_anfragen::auswerten($daten);
          } break;
          case 'nachklausuren_finden': {
            $ergebnis=bearbeite_anfragen::nachklausuren_finden($daten);
          } break;
          case 'tests_finden': {
            $ergebnis=bearbeite_anfragen::tests_finden($daten);
          } break;
          case 'abgaben': {
            $ergebnis=bearbeite_anfragen::abgaben($daten);
          } break;
          case 'tests_abgaben_loeschen': {
            $ergebnis=bearbeite_anfragen::tests_abgaben_loeschen($daten);
          } break;
          case 'label_speichern': {
            $ergebnis=bearbeite_anfragen::label_speichern($daten);
          } break;
          case 'aufgabe_erstellen': {
            $ergebnis=bearbeite_anfragen::aufgabe_erstellen($daten);
          } break;
          case 'aufgabe_freischalten': {
            $ergebnis=bearbeite_anfragen::aufgabe_freischalten($daten);
          } break;
          case 'noten_finden': {
            $ergebnis=bearbeite_anfragen::noten_finden($daten);
          } break;
          case 'emails_verbergen': {
            $ergebnis=bearbeite_anfragen::emails_verbergen($daten);
          } break;
          default: {
            throw new \invalid_parameter_exception('Unbekannte Aktion: '.$params['aktion']);
          }
        }
        return json_encode($ergebnis);
    }
    
    /**
     * $daten:
     *   courseid (Moodle-ID des zu updatenden Courses, falls bekannt)
     *   kategorie ('ILKurse', 'Klassenkurse', 'Kursvorlagen' oder 'inTrain'), 
     *   titel, 
     *   kursid (entweder
     *     die ID des Kurses in den Verwaltungstools für einen ILKurs, oder
     *    'Klasse_'+MITIS-ID der Klasse für einen Klassenkurs, oder 
     *    'Vorlage_'+ID in den Verw.Tools für eine Vorlage, oder
     *    'inTrain_'+ID in den Verw.Tools für einen Intrain-Kurs
     *   beginn, 
     *   ende,
     *   inhalt (HTML des 1. Kapitels),
     *   inhalt_ueberschreiben (J/N),
     *   keinenoten (J/N, für kategorie == 'ILKurse')
     */
    static function kurs_speichern($daten) {
      global $DB, $CFG;
      require_once("$CFG->dirroot/course/lib.php");
      $cat=$DB->get_record('course_categories', array('idnumber' => $daten->kategorie));
      if(isset($daten->courseid) && $daten->courseid>0) {
        $course=$DB->get_record('course', array('id' => $daten->courseid));
      } else {
        $course=$DB->get_record('course', array('idnumber' => $daten->kursid));
      }
      $zeitraumgeaendert=false;
      if($course) {
        if($course->category!=$cat->id
            || $course->idnumber!=$daten->kursid
            || $course->fullname!=$daten->titel
            || $course->shortname!=$daten->titel 
            || $course->startdate!=strtotime($daten->beginn)
            || $course->enddate!=strtotime($daten->ende)) {
          $catcontext=\context_coursecat::instance($cat->id);
          self::validate_context($catcontext);
          require_capability('moodle/course:update', $catcontext);
          $data=(object)array(
            'id'=>$course->id,
            'category'=>$cat->id
            ,'fullname'=>$daten->titel
            ,'shortname'=>$daten->titel 
            ,'idnumber'=>$daten->kursid
            ,'startdate'=>strtotime($daten->beginn)
            ,'enddate'=>strtotime($daten->ende)
          );
          $zeitraumgeaendert=$course->startdate!=$data->startdate  || $course->enddate!=$data->enddate;
          update_course($data);
          foreach($data as $k=>$v) {
            $course->$k=$v;
          }
        }
      } else {
        $course=$DB->get_record('course', array('shortname' => $daten->titel)); // muss unique sein, das hat das Verwaltungstool für sich geprüft
        if($course) {
          throw new \moodle_exception("Kurstitel schon belegt");
        }
        $catcontext=\context_coursecat::instance($cat->id);
        self::validate_context($catcontext);
        require_capability('moodle/course:create', $catcontext);
        $data=(object)array(
          'category'=>$cat->id
          ,'fullname'=>$daten->titel
          ,'shortname'=>$daten->titel 
          ,'idnumber'=>$daten->kursid
          ,'startdate'=>strtotime($daten->beginn)
          ,'enddate'=>strtotime($daten->ende)
          ,'enablecompletion'=>1
          ,'showcompletionconditions'=>1
          ,'groupmode'=>0 //keine Gruppen
          ,'newsitems'=>0  //Nachrichtenforum ausblenden
          ,'format'=>'topics'
          ,'maxbytes'=>0 //unbegrenzt (PHP-Limit)
          ,'visible'=>1
        );
        $course=create_course($data);
        $zeitraumgeaendert=true;
      }
      if($zeitraumgeaendert) {
        $kapitel=array((object)array('titel'=>'','inhalt'=>isset($daten->inhalt) ? $daten->inhalt : '','','','ilaufgabe'=>false,'klausur'=>false)); // ein leeres Kapitel am Anfang
        if($daten->kategorie=='ILKurse'
            && isset($daten->beginn) && !empty($daten->beginn)
            && isset($daten->ende) && !empty($daten->ende)) {
          $tagnamen=array(1=>'Montag',2=>'Dienstag',3=>'Mittwoch',4=>'Donnerstag',5=>'Freitag');
          for($d=$data->startdate;$d<=$data->enddate;$d=strtotime('+1 day',$d)) {
            $tag=date('w',$d);
            if($tag>=1 && $tag<=5) { //Mo bis Fr
              $kapitel[]=(object)array(
                'titel'=>$tagnamen[$tag].date(' d.m.Y',$d)
                ,'inhalt'=>''
                ,'labeltitel'=>($d==$data->enddate ? 'Kursbewertung' : '')
                ,'labelinhalt'=>($d==$data->enddate ? '<h3>Wichtig!<br /><a class="kursbewertung" href="https://verwaltung.gpbmedien.de/tn/kurs_bewertung.php?kursmoodleid='.$course->id.'&amp;bewertungab='.date('Y-m-d',$d).'&amp;bewertungbis='.date('Y-m-d',strtotime('+6 days',$d)).'" target="gpb">Kursbewertung</a></h3>' : '')
                ,'ilaufgabe'=>($tag>=1 && $tag<=4) //IL-Aufgaben Mo bis Do
                ,'klausur'=>($d==$data->enddate && (!isset($daten->keinenoten) || $daten->keinenoten=='N'))); 
            }
          }
        }
        $existingsections=$DB->get_records_sql("SELECT id,section,summary from {course_sections} WHERE course=? order by section",array($course->id));
        // fehlende Tagesabschnitte hinzufügen, dabei werden ggf. die Klausuren erneut angelegt
        for($kapnummer=count($existingsections);$kapnummer<count($kapitel);++$kapnummer) {
          $kap=$kapitel[$kapnummer];
          bearbeite_anfragen::do_kapitel_erstellen($course,$kapnummer,$kap->titel,$kap->inhalt,$kap->labeltitel,$kap->labelinhalt,$kap->ilaufgabe,$kap->klausur);
        }
      } else if(isset($daten->inhalt) && isset($daten->inhalt_ueberschreiben) && $daten->inhalt_ueberschreiben!='N') {
        $existingsections=$DB->get_records_sql("SELECT id,section,summary from {course_sections} WHERE course=? order by section",array($course->id));
        if(count($existingsections)>0) {
          foreach($existingsections as $sectionid=>$section) { // das erste Kapitel ggf. ändern
            if($section->summary!=$daten->inhalt) {
              $DB->set_field('course_sections','summary',$daten->inhalt,array('id'=>$section->id));
            }
            break; // nur das erste Kapitel ändern
          }
        } else {
          bearbeite_anfragen::do_kapitel_erstellen($course,0,'',$daten->inhalt,'','',false,false);
        }
      }
      return $course;
    }
    /**
     * $daten:
     *   courseid, (Moodle-ID des Courses, falls bekannt)
     *   kursid, (entweder die ID des Kurses in den Verwaltungstools für einen ILKurs, oder 'Klasse_'+MITIS-ID der Klasse für einen Klassenkurs, oder 'Vorlage_'+ID in den Verw.Tools für eine Vorlage), 
     *   kapitelnummer, (<0 für "unbekannt" oder "neues Kapitel erstellen")
     *   kapiteltitel, (wird gesucht, dann erstellt falls nicht gefunden)
     *   aufgabentitel,
     *   benachrichtigungen (0 oder 1),
     *   sichtbar (0 oder 1)
     * Ergebnis:
     *   cmid (modinfo-id)
     *   cminstance (ID der Aufgabe)
     *   kapitelnummer (Nummer des Kapitels)
     *   url (URL der Aufgabe, eventuell ohne https:// und Basis-URL)
     *   modname ('assign')
     *   titel (Titel der Aufgabe)
     *   benachrichtigungen (0 oder 1)
     *   sichtbar (0 oder 1)
     *   inhalt_vorhanden (0, kein Inhalt vorhanden)
     *   abgaben (leeres Array, keine Abgaben vorhanden)
     */
    static function aufgabe_erstellen($daten) {
      global $DB;
      if(isset($daten->courseid) && $daten->courseid>0) {
        $course=$DB->get_record('course', array('id' => $daten->courseid));
      } else {
        $course=$DB->get_record('course', array('idnumber' => $daten->kursid));
      }
      if(!$course) {
        throw new \moodle_exception("Kurs moodleid=".(isset($daten->courseid) ? $daten->courseid : 'unbekannt')." idnumber=".(isset($daten->kursid) ? $daten->kursid : '(unbekannt)')." nicht gefunden");
      }
      $kapitel=null;
      $nummer=null;
      if($daten->kapitelnummer>=0) {
        $kapitel=$DB->get_record('course_sections',array('section'=>$daten->kapitelnummer));
      } else if(!empty($daten->kapiteltitel)) {
        $kapitel=$DB->get_record('course_sections',array('name'=>$daten->kapiteltitel));
      }
      if(empty($kapitel)) {
        $nummer=$DB->get_field_sql("select max(section)+1 as nummer from {course_sections} where course=?",array($course->id));
        if($nummer===null) {
          $nummer=0;
        }
        $kapitelid=bearbeite_anfragen::do_kapitel_erstellen($course,$nummer,$daten->kapiteltitel,'','','',false,false);
      } else {
        $nummer=$kapitel->section;
      }
      $cm=bearbeite_anfragen::do_aufgabe_erstellen($course,$nummer,$daten->aufgabentitel,$daten->benachrichtigungen,$daten->sichtbar);
      return (object)array(
        'cmid'=>$cm->coursemodule
        ,'cminstance'=>$cm->instance
        ,'kapitelnummer'=>$nummer
        ,'url'=>($cm->url ? $cm->url->out() : 'mod/assign/view.php?id='.$cm->coursemodule)
        ,'modname'=>'assign'
        ,'titel'=>$cm->name
        ,'sichtbar'=>$daten->sichtbar
        ,'benachrichtigungen'=>$daten->benachrichtigungen
        ,'inhalt_vorhanden'=>0
        ,'abgaben'=>array()
      );
    }
    static function do_kapitel_erstellen($course,$nummer,$titel,$inhalt,$labeltitel='',$labelinhalt='',$ilaufgabe=false,$klausur=false) {
      global $DB, $CFG;
      require_once("$CFG->dirroot/course/lib.php");
      $cw=(object)array(
        'course'=>$course->id
        ,'section'=>$nummer
        ,'name'=>$titel
        ,'summary'=>$inhalt
        ,'summaryformat'=>FORMAT_HTML
        ,'sequence'=>null
        ,'visible'=>1
        ,'availability'=>null
        ,'timemodified'=>time()
      );
      $kapitelid=$DB->insert_record("course_sections", $cw);
      rebuild_course_cache($course->id, true);
      if(!empty($labeltitel) || !empty($labelinhalt)) {
        $moddoc = (object)array(
          'course'=>$course->id
          ,'section'=>$nummer
          ,'module'=>13
          ,'modulename'=>'label'
          ,'add'=>'label'
          ,'name'=>$labeltitel
          ,'intro'=>'' // wird aus introeditor überschrieben
          ,'introformat'=>"1" // wird aus introeditor überschrieben
          ,'introeditor'=>array('text'=>$labelinhalt,'format'=>"1")
          ,'visible'=>1
          ,'idnumber'=>0
          ,'display'=>"0"
          ,'coursemodule'=>0
          ,'instance'=>0
          ,'cmidnumber'=>"0"
          ,'update'=>0
          ,'availabilityconditionsjson'=>'{"op":"&","c":[],"showc":[]}'
          ,'return'=>0
        );
        create_module($moddoc);
      }
      if($ilaufgabe) {
        bearbeite_anfragen::do_aufgabe_erstellen($course,$nummer,"IL-Aufgabe ".$titel,false,true);
      }
      if($klausur) {
        bearbeite_anfragen::do_aufgabe_erstellen($course,$nummer,"Klausur",false,false);
        bearbeite_anfragen::do_aufgabe_erstellen($course,$nummer,"Nachklausur",true,false);
      }
      return $kapitelid;
    }
    static function do_aufgabe_erstellen($course,$nummer,$titel,$benachrichtigungen,$sichtbar) {
      global $CFG;
      require_once("$CFG->dirroot/course/lib.php");
      $moddoc = (object)array(
          'course'=>$course->id
          ,'section'=>$nummer
          ,'module'=>43
          ,'modulename'=>'assign'
          ,'add'=>'assign'
          ,'name'=>$titel
          ,'intro'=>'' // wird aus introeditor überschrieben
          ,'introformat'=>"0" // wird aus introeditor überschrieben
          ,'introeditor'=>array('text'=>'','format'=>"0")
          ,'thirdparty'=>''
          ,'visible'=>$sichtbar
          ,'idnumber'=>0
          ,'display'=>"0"
          ,'groupmode'=>0
          ,'coursemodule'=>0
          ,'instance'=>0
          ,'cmidnumber'=>"0"
          ,'update'=>0
          ,'availabilityconditionsjson'=>'{"op":"&","c":[],"showc":[]}'
          ,'return'=>0
          ,'sr'=>0
          ,'revision'=>1
          ,'files'=>0
          ,'teamsubmission'=>0
          ,'availablefrom'=>0
          ,'availableuntil'=>0
          ,'showavailability'=>0
          ,'showdescription'=>0
          ,'conditiongradegroup'=>array()
          ,'conditionfieldgroup'=>array()
          ,'assignsubmission_file_enabled'=>1
          ,'assignsubmission_file_maxfiles'=>1
          ,'assignsubmission_onlinetext_enabled'=>1
          ,'assignsubmission_file_maxsizebytes'=>$course->maxbytes
          ,'maxattempts'=>-2
          ,'submissiondrafts'=>0
          ,'requiresubmissionstatement'=>0
          ,'sendnotifications'=>$benachrichtigungen
          ,'sendlatenotifications'=>0
          ,'gradingduedate'=>0
          ,'duedate'=>0
          ,'cutoffdate'=>0
          ,'allowsubmissionsfromdate'=>0
          ,'grade'=>100
          ,'requireallteammemberssubmit'=>0
          ,'blindmarking'=>0
          ,'markingworkflow'=>0
          ,'markingallocation'=>0
          ,'markinganonymous'=>0
          ,'completionunlocked'=>1
          ,'completionunlockednoreset'=>0
          ,'completion'=>2
          ,'completionsubmit'=>1
          ,'completionexpected'=>0
          ,'completionview'=>1
        );
        $modulinfo=create_module($moddoc);
        return $modulinfo;
    }
    
    /**
     * $daten:
     *   courseid (Moodle-ID des zu löschenden Courses, falls bekannt) oder
     *   kursid (entweder die ID des Kurses in den Verwaltungstools für einen ILKurs oder 'Klasse_'+MITIS-ID der Klasse für einen Klassenkurs)
     */
    static function kurs_loeschen($daten) {
      global $DB, $CFG;
      require_once("$CFG->dirroot/course/lib.php");
      if(isset($daten->courseid) && $daten->courseid>0) {
        $course=$DB->get_record('course', array('id' => $daten->courseid));
      } else {
        $course=$DB->get_record('course', array('idnumber' => $daten->kursid));
      }
      if(!$course) {
        throw new \moodle_exception("Kurs moodleid=".(isset($daten->courseid) ? $daten->courseid : 'unbekannt')." idnumber=".(isset($daten->kursid) ? $daten->kursid : '(unbekannt)')." nicht gefunden");
      }
      $context = \context_course::instance($course->id);
      if (!has_capability('moodle/course:delete', $context)) {
        throw new \moodle_exception("Plugin darf Kurs moodleid=".$course->id." nicht löschen");
      }
      require_once("$CFG->dirroot/lib/moodlelib.php");
      require_once("$CFG->dirroot/lib/datalib.php");
      delete_course($course);
      fix_course_sortorder();
      return 'OK';
    }
    
    /**
     * $daten: 
     *   userid (Moodle-ID des zu updatenden Nutzers, falls bekannt),
     *   nutzername,
     *   vorname, 
     *   nachname, 
     *   email,
     *   rolle ('verwalter', 'dozent' oder 'tn', wird zu 'admin', 'teacher' bzw. 'student' übersetzt)
     *   rolle_ersetzen (true, falls bisherige Rollen auf Systemkontext entfernt werden sollen, nicht gesetzt oder false sonst), 
     *   maildisplay (falls Änderung gewünscht),
     *   emailstop (falls Änderung gewünscht),
     *   hash (falls Änderung gewünscht) oder createpassword (falls Generierung + Emailsenden gewünscht)
     *   idnumber (falls Änderung gewünscht)
     */
    static function nutzer_speichern($daten) {
      global $DB, $CFG;
      $systemcontext = \context_system::instance();
      if(isset($daten->userid) and $daten->userid>0) {
        $user=$DB->get_record('user', array('id' => $daten->userid));
        if(!$user) {
          throw new \moodle_exception('Moodle-ID nicht gefunden: '.$daten->userid);
        }
      } else {
        $user=$DB->get_record('user', array('username' => $daten->nutzername));
      }
      if($user) {
        // Nutzer schon vorhanden, aktualisieren
        require_capability('moodle/user:update', $systemcontext);
        $data=(object)array('id'=>$user->id);
        $todo=false;
        if(isset($daten->nutzername) && $user->username!=$daten->nutzername) {
          $data->username=$daten->nutzername;
          $user->username=$daten->nutzername;
          $todo=true;
        }
        if(isset($daten->vorname) && $user->firstname!=$daten->vorname) {
          $data->firstname=$daten->vorname;
          $user->firstname=$daten->vorname;
          $todo=true;
        }
        if(isset($daten->nachname) && $user->lastname!=$daten->nachname) {
          $data->lastname=$daten->nachname;
          $user->lastname=$daten->nachname;
          $todo=true;
        }
        if(isset($daten->email) && $user->email!=$daten->email) {
          $data->email=$daten->email;
          $user->email=$daten->email;
          $todo=true;
        }
        if(isset($daten->maildisplay) && $user->maildisplay!=$daten->maildisplay) {
          $data->maildisplay=$daten->maildisplay;
          $user->maildisplay=$daten->maildisplay;
          $todo=true;
        }
        if(isset($daten->emailstop) && $user->emailstop!=$daten->emailstop) {
          $data->emailstop=$daten->emailstop;
          $user->emailstop=$daten->emailstop;
          $todo=true;
        }
        if(isset($daten->hash) && !empty($daten->hash) && $user->password!=$daten->hash) {
          $data->password=$daten->hash;
          $user->password=$daten->hash;
          $todo=true;
        }
        if(isset($daten->idnumber) && $user->idnumber!=$daten->idnumber) {
          $data->idnumber=$daten->idnumber;
          $user->idnumber=$daten->idnumber;
          $todo=true;
        }
        if($todo) {
          $DB->update_record('user',$data);
        }
        $rolle_ersetzen=isset($daten->rolle_ersetzen) && $daten->rolle_ersetzen;
        $rolle=isset($daten->rolle) && !empty($daten->rolle) ? ($daten->rolle=='verwalter' ? 'manager' : ($daten->rolle=='dozent' ? 'teacher' : 'student')) : false;
        if($rolle_ersetzen || $rolle) {
          require_capability('moodle/role:assign', $systemcontext);
          $roles=get_user_roles($systemcontext,$user->id,false);
          $schon=false;
          foreach($roles as $role) {
            if($role->shortname==$rolle) {
              $schon=true;
              continue;
            }
            if($rolle_ersetzen) {
//              role_unassign($role->id, $user->id, $systemcontext->id); //funktioniert leider nicht
              //Code aus lib/accesslib.php role_unassign_all übernommen
              $DB->delete_records('role_assignments', array('id'=>$role->id));
              mark_user_dirty($user->id);
              //Pech für Event und Hook
              //Dies ist aus der Mitteilung an Kurs-Kategorien, course/category.php
              $cache = \cache::make('core', 'coursecontacts');
              $cache->purge();
            }
          }
          if($rolle && !$schon) {
            $role = $DB->get_record('role', array('shortname' => $rolle));
            role_assign($role->id, $user->id, $systemcontext->id);
          }
        }
        if(isset($daten->createpassword) && $daten->createpassword) {
          require_once($CFG->libdir.'/moodlelib.php');
          setnew_password_and_mail($user);
          unset_user_preference('create_password', $user);
          set_user_preference('auth_forcepasswordchange', 1, $user);
          // wir holen nicht das temporäre Passwort
        } else if(isset($daten->mussaendern) && $daten->mussaendern) {
          set_user_preference('auth_forcepasswordchange', 1, $user);
        }
        return $user;
      }
      // Nutzer erstellen
      if(isset($daten->email) && !empty($daten->email)) {
        $user=$DB->get_record('user', array('email' => $daten->email));
        if($user) {
          throw new \moodle_exception('Email schon belegt: '.$daten->email.' Nutzername='.$user->username);
        }
      }
      self::validate_context($systemcontext);
      require_capability('moodle/user:create', $systemcontext);
      $data=(object)array(
        'mnethostid'=>1,
        'auth'=>'manual',
        'confirmed'=>1,
        'username'=>$daten->nutzername,
        //Hier haben wir nicht das Passwort als Klartext, also nicht setzen
        'firstname'=>$daten->vorname,
        'lastname'=>$daten->nachname,
        'email'=>$daten->email,
        'maildisplay'=>(isset($daten->maildisplay) ? $daten->maildisplay : 0),
        'emailstop'=>(isset($daten->emailstop) ? $daten->emailstop : 0),
        'idnumber'=>(isset($daten->idnumber) ? $daten->idnumber : '')
      );
      require_once($CFG->dirroot.'/user/lib.php');
      $newuserid=user_create_user($data,false,false); //Passwort nicht setzen, kein Event triggern
      $data->id=$newuserid;
      if(isset($daten->hash) && !empty($daten->hash)) {
        $DB->update_record('user',(object)array('id'=>$newuserid,'password'=>$daten->hash));
        $data->password=$daten->hash;
      } else if(isset($daten->createpassword) && $daten->createpassword) {
        require_once($CFG->libdir.'/moodlelib.php');
        $data = $DB->get_record('user', array('id' => $data->id));
        setnew_password_and_mail($data);
        unset_user_preference('create_password', $data);
        set_user_preference('auth_forcepasswordchange', 1, $data);
      }
      if(isset($daten->rolle)) {
        require_capability('moodle/role:assign', $systemcontext);
        $role = $DB->get_record('role', array('shortname' => ($daten->rolle=='verwalter' ? 'manager' : ($daten->rolle=='dozent' ? 'teacher' : 'student'))));
        role_assign($role->id, $newuserid, $systemcontext->id);
      }
      return $data;
    }
    
    /**
     * $daten: fullname
     * oder
     * $daten: login 
     * oder
     * $daten: (username,email)
     * oder
     * $daten: userid
     */
    static function nutzer_finden($daten) {
      global $DB;
      if(isset($daten->userid)) {
        $user=$DB->get_record('user', array('id' => $daten->userid));
        return $user;
      }
      if(isset($daten->fullname)) {
        $users=$DB->get_records_sql("select * from mdl_user where concat(concat(firstname,' '),lastname)=?",array($daten->fullname));
        if(count($users)==1) {
          foreach($users as $id=>$daten) {
            return $daten;
          }
        }
        return null;
      }
      if(isset($daten->login)) {
        $user=$DB->get_record('user', array('username' => $daten->login));
        if(!$user) {
          $user=$DB->get_record('user', array('email' => $daten->login));
        }
        return $user;
      }
      $users=array();
      $user=$DB->get_record('user', array('username' => $daten->nutzername));
      if($user) {
        $users[]=$user;
      }
      $user=$DB->get_record('user', array('email' => $daten->email));
      if($user) {
        $users[]=$user;
      }
      return $users;
    }
    
    /**
     * $daten: 
     *   rolename ('editingteacher' oder 'student'),
     *   courseid (moodleid)
     * oder 
     *   kursid (idnumber),
     *   userids (moodleids),
     *   ersetzen (true zum ersetzen, false um hinzuzufügen)
     */
    static function nutzer_zuweisen($daten) {
      global $DB,$CFG;
      require_once($CFG->libdir.'/accesslib.php');
      require_once($CFG->libdir.'/enrollib.php');
      if(!isset($daten->courseid) || $daten->courseid<=0) {
        $daten->courseid=$DB->get_field('course','id',array('idnumber'=>$daten->kursid));
        if(!$daten->courseid) {
          throw new \moodle_exception('Kurs nicht gefunden: idnumber='.$daten->idnumber);
        }
      }
      $role = $DB->get_record('role', array('shortname' => $daten->rolename));
      $context = \context_course::instance($daten->courseid);
      self::validate_context($context);
      require_capability('enrol/manual:enrol', $context);
      $plugin = enrol_get_plugin('manual');
      $enrol = $DB->get_record('enrol', array('courseid' => $daten->courseid, 'enrol' => 'manual'));
      if(!$enrol) {
        $course=(object)array('id'=>$daten->courseid);
        $instid = $plugin->add_default_instance($course);
        if ($instid === null) {
            $instid = $plugin->add_instance($course);
        }
        $enrol=$DB->get_record('enrol',array('id'=>$instid));
      }
      
      $users = get_role_users($role->id, $context);
      foreach($users as $u) {
        $u->todo=true;
      }
      foreach($daten->userids as $userid) {
        if($userid<=0) continue; // Dozent oder TN hat noch keinen Moodle-Account
        $todo=true;
        foreach($users as $u) {
          if($u->id==$userid) {
            $todo=false;
            $u->todo=false;
            break;
          }
        }
        if($todo) {
          $plugin->enrol_user($enrol, $userid, $role->id);
        }
      }
      if($daten->ersetzen) {
        foreach($users as $u) {
          if($u->todo) {
            $plugin->unenrol_user($enrol, $u->id, $role->id);
          }
        }
      }
      return 'ok';
    }
    
    /**
     * $daten: 
     *   rolename ('editingteacher' oder 'student'),
     *   courseid (moodleid)
     * oder 
     *   kursid (idnumber),
     *   userid (moodleid),
     *   anmelden (true zum anmelden, false zum abmelden)
     */
    static function nutzer_einschreibung($daten) {
      global $DB,$CFG;
      require_once($CFG->libdir.'/accesslib.php');
      require_once($CFG->libdir.'/enrollib.php');
      if(!isset($daten->courseid) || $daten->courseid<=0) {
        $daten->courseid=$DB->get_field('course','id',array('idnumber'=>$daten->kursid));
        if(!$daten->courseid) {
          throw new \moodle_exception('Kurs nicht gefunden: idnumber='.$daten->idnumber);
        }
      }
      
      $context = \context_course::instance($daten->courseid);
      self::validate_context($context);
      require_capability('enrol/manual:enrol', $context);

// zu unspezifisch, und kann anderweitig verschwinden/geändert werden (z.B. falls der User per Meta-Einschreibungin diesem Kurs ist)
//      $schon=is_enrolled($context,(object)array('id'=>$daten->userid));
//      if($schon && $daten->anmelden) return 'ok';
//      if(!$schon && !$daten->anmelden) return 'ok';
      
      $role = $DB->get_record('role', array('shortname' => $daten->rolename));
      $users = get_role_users($role->id, $context);
      $schon=null;
      foreach($users as $u) {
        if($u->id==$daten->userid) {
          $schon=$u;
          break;
        }
      }
      if($schon && $daten->anmelden) return 'ok';
      if(!$schon && !$daten->anmelden) return 'ok';
      
      $plugin = enrol_get_plugin('manual');
      $enrol = $DB->get_record('enrol', array('courseid' => $daten->courseid, 'enrol' => 'manual'));
      if(!$enrol) {
        $course=(object)array('id'=>$daten->courseid);
        $instid = $plugin->add_default_instance($course);
        if ($instid === null) {
            $instid = $plugin->add_instance($course);
        }
        $enrol=$DB->get_record('enrol',array('id'=>$instid));
      }
      if($daten->anmelden) {
        $plugin->enrol_user($enrol, $daten->userid, $role->id);
      } else {
        $plugin->unenrol_user($enrol, $daten->userid, $role->id);
      }
      return 'ok';
    }
    
    /**
     * $daten: 
     *   courseid (moodleid)
     * oder 
     *   kursid (idnumber),
     *   klassencourseids,
     *   ersetzen (true zum ersetzen, false um hinzuzufügen)
     */
    static function meta_einschreibung($daten) {
      global $DB,$CFG;
      require_once($CFG->libdir.'/accesslib.php');
      require_once($CFG->libdir.'/enrollib.php');
      if(!isset($daten->courseid) || $daten->courseid<=0) {
        $daten->courseid=$DB->get_field('course','id',array('idnumber'=>$daten->kursid));
        if(!$daten->courseid) {
          throw new \moodle_exception('Kurs nicht gefunden: idnumber='.$daten->idnumber);
        }
      }
      $context = \context_course::instance($daten->courseid);
      self::validate_context($context);
      $plugin = enrol_get_plugin('meta');
      
      // Abgaben in den Activities gehen nicht verloren, sondern sind nicht mehr erreichbar
      $enrols=$DB->get_records('enrol',array('courseid'=>$daten->courseid,'enrol'=>'meta'));
      $alteklassen=array();
      if($daten->ersetzen) {
        foreach($enrols as $e) {
          $plugin->delete_instance($e);
        }
      } else {
        foreach($enrols as $e) {
          $alteklassen[]=$e->customint1;
        }
      }

      // Falls eine Klasse wieder eingeschrieben wird, sieht jeder TN seine Abgaben wieder
      if(!empty($daten->klassencourseids)) {
        if(!empty($alteklassen)) {
          $daten->klassencourseids=array_diff($daten->klassencourseids,$alteklassen);
        }
        $data=array('courseid'=>$daten->courseid,'customint1'=>$daten->klassencourseids,'customint2'=>'0');
        $course=(object)array('id'=>$daten->courseid);
        $instid=$plugin->add_default_instance($course,$data);
        if ($instid===null) {
            $instid=$plugin->add_instance($course,$data);
        }
      }
      
      return 'ok';
    }
    
    /**
     * Auswertung: auszulesende Datenbankfelder je nach Activity-Art
     */
    static $actionByModname=array(
      "assign"=>'submitted'
      ,"feedback"=>'submitted'
      ,"questionnaire"=>'submitted'
      ,"assignment"=>'submitted'
      ,"survey"=>'submitted'
      ,"turnitintool"=>'submitted'
      ,"turnitintooltwo"=>'submitted'
      ,"workshop"=>'submitted'
      ,"data"=>'created'
      ,"wiki"=>'created'
      ,"book"=>'created'
      ,"glossary"=>'created'
      ,"advmindmap"=>'updated'
      ,"lightboxgallery"=>'updated'
      ,"choicegroup"=>'updated'
      ,"folder"=>'updated'
      ,"journal"=>'updated'
      ,"forum"=>'updated'
      ,"hsuforum"=>'updated'
      ,"chat"=>'sent'
      ,"choice"=>'answered'
      ,"dmelearn"=>'finished'
      ,"hotpot"=>'finished'
      ,"questionnaire"=>'finished'
      ,"lesson"=>'attempted'
      ,"hotpot"=>'attempted'
      ,"scorm"=>'attempted' //'launched'
      ,"lti"=>'graded'
      ,"subcourse"=>'graded'
      ,"checklist"=>'completed'
      ,"vpl"=>'marked'
      ,"quiz"=>'started'
    );
    /**
     * $daten: 
     *   courseid
     * @return Liste mit 1 Objekt pro IL-Aufgabe/Klausur/Nachklausur (== course module, deren Titel mit "IL", "Klausur " oder "Nachklausur " beginnt)
     *         In jedem Objekt:
     *         - cmid (modinfo-id)
     *         - cminstance (Moodle-ID der Aufgabe)
     *         - kapitelnummer (Nummer des Kapitels)
     *         - url (URL der Aufgabe)
     *         - modname (Plugin-Name der Aufgabe)
     *         - titel (Titel der Aufgabe)
     *         - sichtbar (0 oder 1)
     *         - inhalt_vorhanden (0 wenn nicht, 1 wenn vorhanden, -1 wenn unsicher)
     *         - abgaben (Map userid => abgegeben (0 oder 1))
     */
    static function auswerten($daten) {
      global $DB,$CFG;
      require_once($CFG->libdir.'/modinfolib.php');
      $modinfo=get_fast_modinfo($daten->courseid);
      $aufgaben=array();
      $aufgabenByCmid=array();
      $aufgabenByAssignid=array();
      $aufgabenByQuizid=array();
      $logwhere=array();
      foreach($modinfo->cms as $cm) {
        if($cm->indent>1) continue;
        if($cm->modname=='label') continue;
        $titel=strtolower($cm->name);
        $ok=substr($titel,0,2)=='il' || $titel=='klausur' || $titel=='nachklausur' || substr($titel,0,8)=='klausur ' || substr($titel,0,12)=='nachklausur ';
        if(!$ok) continue;
        if(!$cm->visible && substr($titel,0,2)=='il') continue; // Klausur und Nachklausur sollen gefunden werden, selbst wenn sie nicht sichtbar sind
        $kapitel=$modinfo->get_section_info($cm->sectionnum);
        if(isset($kapitel->visible) && !$kapitel->visible) continue;
        $inhalt_vorhanden=-1;
        if($cm->modname=='assign') {
          $inhalt_vorhanden=0;
          $instancedata=$DB->get_record($cm->modname,array('id'=>$cm->instance));
          if(!empty($instancedata->intro) || !empty($instancedata->activity)) {
            $inhalt_vorhanden=1;
          } else {
            $anzahldateien=$DB->get_record_sql("SELECT count(*) as anzahl FROM mdl_files f
              join mdl_context c on c.id=f.contextid
              where f.component='mod_assign' and f.filesize>0
              and c.contextlevel=70 and c.instanceid=".$cm->id);
            if($anzahldateien->anzahl>0) {
              $inhalt_vorhanden=1;
            }
          }
        } else {
          $inhalt_vorhanden=1; //Wenn der Dozent eine Klausur-Aufgabe selber erstellt hat, gilt sie als inhaltsvoll
        }
        $aufgabe=(object)array(
          'cmid'=>$cm->id
          ,'cminstance'=>$cm->instance
          ,'kapitelnummer'=>$cm->section
          ,'url'=>$cm->url->out()
          ,'modname'=>$cm->modname
          ,'titel'=>$cm->name
          ,'sichtbar'=>$cm->visible
          ,'inhalt_vorhanden'=>$inhalt_vorhanden
          ,'abgaben'=>array()
        );
        $aufgaben[]=$aufgabe;
        $aufgabenByCmid[$aufgabe->cmid]=$aufgabe;
        if($cm->modname=='assign') {
          $aufgabenByAssignid[$cm->instance]=$aufgabe;
        } else if($cm->modname=='quiz') {
          $aufgabenByQuizid[$cm->instance]=$aufgabe;
        }
        $logwhere[]="(".$aufgabe->cmid.",'".(isset(bearbeite_anfragen::$actionByModname[$cm->modname]) ? bearbeite_anfragen::$actionByModname[$cm->modname] : 'viewed')."')";
      }
      if(!empty($aufgabenByCmid)) {
        $completions=$DB->get_records_sql("select id,userid,coursemoduleid,completionstate from {course_modules_completion} where ".(isset($daten->userid) ? "userid=".$daten->userid : "userid>0")." and coursemoduleid in (".implode(',',array_keys($aufgabenByCmid)).")");
        foreach($completions as $comp) {
          $aufgabenByCmid[$comp->coursemoduleid]->abgaben[$comp->userid]=$comp->completionstate;
        }        
        $logs=$DB->get_records_sql("select id,userid,contextinstanceid from {logstore_standard_log} where ".(isset($daten->userid) ? "userid=".$daten->userid : "userid>0")." and courseid=".$daten->courseid." and (contextinstanceid,action) in (".implode(',',$logwhere).")");
        foreach($logs as $log) {
          $aufgabenByCmid[$log->contextinstanceid]->abgaben[$log->userid]=1;
        }
        if(!empty($aufgabenByAssignid)) {          
          $subs=$DB->get_records_sql("select s.userid,s.assignment
              ,f.numfiles,t.onlinetext
            from {assign_submission} s
            left outer join {assignsubmission_file} f on f.submission=s.id and f.assignment=s.assignment
            left outer join {assignsubmission_onlinetext} t on t.submission=s.id and t.assignment=s.assignment
            where s.status='submitted' and s.assignment in(".implode(',',array_keys($aufgabenByAssignid)).")".(isset($daten->userid) ? " and s.userid=".$daten->userid : ""));
          foreach($subs as $sub) {
            if($sub->numfiles || $sub->onlinetext) {
              $aufgabenByAssignid[$sub->assignment]->abgaben[$sub->userid]=1;
            }
          }
        }
        if(!empty($aufgabenByQuizid)) {          
          $atts=$DB->get_records_sql("select a.userid,a.quiz
            from {quiz_attempts} a
            where a.state='finished' and a.quiz in(".implode(',',array_keys($aufgabenByQuizid)).")".(isset($daten->userid) ? " and a.userid=".$daten->userid : ""));
          foreach($atts as $att) {
            $aufgabenByQuizid[$att->quiz]->abgaben[$att->userid]=1;
          }
        }
      }
      return $aufgaben;
    }
    
    /**
     * $daten: 
     *   courseids Liste von Kurs-Moodle-IDs
     * @return Liste mit 1 Objekt pro Nachklausur (== course module, deren Titel mit "Nachklausur " beginnt)
     *         In jedem Objekt:
     *         - courseid (Moodle-ID des Kurses)
     *         - cmid (modinfo-ID)
     *         - cminstance (Moodle-ID der Aufgabe)
     *         - kapitelnummer (Nummer des Kapitels)
     *         - url (URL der Aufgabe)
     *         - modname (Plugin-Name der Aufgabe)
     *         - titel (Titel der Aufgabe)
     *         - sichtbar (0 oder 1)
     *         - inhalt_vorhanden (0 wenn nicht, 1 wenn vorhanden, -1 wenn unsicher)
     *         - abgabe_von (wie eingestellt (ggf. 0) für quiz und assign, null für andere Aktivitäten)
     *         - abgabe_bis (wie eingestellt (ggf. 0) für quiz und assign, null für andere Aktivitäten)
     */
    static function nachklausuren_finden($daten) {
      global $DB,$CFG;
      require_once($CFG->libdir.'/modinfolib.php');
      $aufgaben=array();
      foreach($daten->courseids as $courseid) {
        $modinfo=get_fast_modinfo($courseid);
        foreach($modinfo->cms as $cm) {
          if($cm->indent>1) continue;
          if($cm->modname=='label') continue;
          $titel=strtolower($cm->name);
          $ok=$titel=='nachklausur' || substr($titel,0,12)=='nachklausur ';
          if(!$ok) continue;
          $kapitel=$modinfo->get_section_info($cm->sectionnum);
          if(isset($kapitel->visible) && !$kapitel->visible) continue;
          $inhalt_vorhanden=-1;
          if($cm->modname=='assign') {
            $inhalt_vorhanden=0;
            $instancedata=$DB->get_record($cm->modname,array('id'=>$cm->instance));
            if(!empty($instancedata->intro) || !empty($instancedata->activity)) {
              $inhalt_vorhanden=1;
            } else {
              $anzahldateien=$DB->get_record_sql("SELECT count(*) as anzahl FROM mdl_files f
                join mdl_context c on c.id=f.contextid
                where f.component='mod_assign' and f.filesize>0
                and c.contextlevel=70 and c.instanceid=".$cm->id);
              if($anzahldateien->anzahl>0) {
                $inhalt_vorhanden=1;
              }
            }
            $abgabe_von=empty($cm->customdata['allowsubmissionsfromdate']) ? 0 : $cm->customdata['allowsubmissionsfromdate'];
            $abgabe_bis=empty($cm->customdata['cutoffdate']) ? 0 : $cm->customdata['cutoffdate'];
          } else {
            $inhalt_vorhanden=1; //Wenn der Dozent eine Klausur-Aufgabe selber erstellt hat, gilt sie als inhaltsvoll
            if($cm->modname=='quiz') {
              $abgabe_von=empty($cm->customdata['timeopen']) ? 0 : $cm->customdata['timeopen'];
              $abgabe_bis=empty($cm->customdata['timeclose']) ? 0 : $cm->customdata['timeclose'];
            } else {
              $abgabe_von=null;
              $abgabe_bis=null;
            }
          }
          $aufgabe=(object)array(
            'courseid'=>$courseid
            ,'cmid'=>$cm->id
            ,'cminstance'=>$cm->instance
            ,'kapitelnummer'=>$cm->section
            ,'url'=>$cm->url->out()
            ,'modname'=>$cm->modname
            ,'titel'=>$cm->name
            ,'sichtbar'=>$cm->visible
            ,'inhalt_vorhanden'=>$inhalt_vorhanden
            ,'abgabe_von'=>$abgabe_von
            ,'abgabe_bis'=>$abgabe_bis
          );
          $aufgaben[]=$aufgabe;
        }
      }
      return $aufgaben;
    }
    
    /**
     * $daten: 
     *   category Name des Kursbereichs
     * @return Map Moodle-ID => Kurs-Objekt
     *         In jedem Kurs-Objekt:
     *         - id (Moodle-ID des Kurses)
     *         - shortname (Titel des Kurses)
     *         - quizzes (Liste mit 1 Objekt pro Test, d.h. Aktivität mit modname=='quiz')
     *         - iframes (Liste mit 1 Objekt pro Paragraph, d.h. Aktivität mit modname=='label', wo der Inhalt <iframe ...></iframe> enthält)
     *         In jedem Test-Objekt:
     *         - courseid (Moodle-ID des Kurses)
     *         - cmid (modinfo-ID)
     *         - cminstance (Moodle-ID des Tests)
     *         - kapitelnummer (Nummer des Kapitels)
     *         - kapiteltitel (Titel des Kapitels)
     *         - url (URL des Tests)
     *         - titel (Titel des Tests)
     *         - abgaben (Map Nutzer-Moodle-ID => Moodle-ID des letzten Versuchs (attempt))
     *         In jedem Paragraph-Objekt
     *         - courseid (Moodle-ID des Kurses)
     *         - cmid (modinfo-ID)
     *         - cminstance (Moodle-ID des Paragraphs)
     *         - kapitelnummer (Nummer des Kapitels)
     *         - kapiteltitel (Titel des Kapitels)
     *         - url (URL des Paragraphs)
     *         - titel (Titel des Paragraphs)
     *         - inhalt (Text des Paragraphs, wird <iframe ...></iframe> enthalten
     */
    static function tests_finden($daten) {
      global $DB,$CFG;
      require_once($CFG->libdir.'/modinfolib.php');
      $category=$DB->get_record('course_categories',array('name'=>$daten->category));
      $coursesById=empty($category) ? array() : $DB->get_records_sql("select id,shortname from {course} where category=".$category->id);
      $quizzesById=array();
      foreach($coursesById as $courseid=>$course) {
        $course->quizzes=array();
        $course->iframes=array();
        $modinfo=get_fast_modinfo($courseid);
        foreach($modinfo->cms as $cm) {
          if($cm->indent>1) continue;
          if(!$cm->visible) continue;
          if($cm->modname=='quiz') {
            $kapitel=$modinfo->get_section_info($cm->sectionnum);
            if(isset($kapitel->visible) && !$kapitel->visible) continue;
            $quiz=(object)array(
              'courseid'=>$courseid
              ,'cmid'=>$cm->id
              ,'cminstance'=>$cm->instance
              ,'kapitelnummer'=>$cm->section
              ,'kapiteltitel'=>$kapitel->name
              ,'url'=>$cm->url->out()
              ,'titel'=>$cm->name
              ,'abgaben'=>array()
            );
            $course->quizzes[]=$quiz;
            $quizzesById[$cm->instance]=$quiz;
          } else if($cm->modname=='label') {
            $kapitel=$modinfo->get_section_info($cm->sectionnum);
            if(isset($kapitel->visible) && !$kapitel->visible) continue;
            $instancedata=$DB->get_record($cm->modname,array('id'=>$cm->instance));
            if(strpos($instancedata->intro,'<iframe')===false || strpos($instancedata->intro,'</iframe>')===false) continue;
            $iframe=(object)array(
              'courseid'=>$courseid
              ,'cmid'=>$cm->id
              ,'cminstance'=>$cm->instance
              ,'kapitelnummer'=>$cm->section
              ,'kapiteltitel'=>$kapitel->name
              ,'url'=>(empty($cm->url) ? null : $cm->url->out())
              ,'titel'=>$cm->name
              ,'inhalt'=>$instancedata->intro
            );
            $course->iframes[]=$iframe;
          }
        }
      }
      if(!empty($quizzesById)) {
        $attempts=$DB->get_records_sql("select id,quiz,userid,attempt from {quiz_attempts} where state='finished' and quiz in(".implode(',',array_keys($quizzesById)).") order by attempt");
        foreach($attempts as $id=>$attempt) {
          $quiz=$quizzesById[$attempt->quiz];
          $quiz->abgaben[$attempt->userid]=$attempt->id;
        }
      }
      return $coursesById;
    }
    /**
     * $daten: 
     *   attemptid
     * @return Objekt
     *   - attempt
     *   - quiz
     *   - questions (Map id => question)
     *   - questionattempts (Map id => question_attempts)
     *   - steps (Map id => question_attempt_steps)
     */
    static function abgaben($daten) {
      global $DB,$CFG;
      $attemptid=(int)$daten->attemptid;
      $attempt=$DB->get_record_sql("select * from {quiz_attempts} where id=".$attemptid);
      if(empty($attempt)) return null;
      $quiz=$DB->get_record_sql("select * from {quiz} where id=".$attempt->quiz);
      $qattempts=$DB->get_records_sql("select qa.* from {question_attempts} qa where qa.questionusageid=".$attempt->uniqueid." order by slot");
      $questionids=array();
      foreach($qattempts as $id=>$qa) {
        $questionids[]=$qa->questionid;
      }
      $questions=empty($questionids) ? array() : $DB->get_records_sql("select q.* from {question} q where q.id in(".implode(',',$questionids).")");
      $steps=empty($qattempts) ? array() : $DB->get_records_sql("select * from {question_attempt_steps} where fraction is not null and questionattemptid in(".implode(',',array_keys($qattempts)).")");
      $ergebnis=(object)array(
        'attempt'=>$attempt,
        'quiz'=>$quiz,
        'questions'=>$questions,
        'questionattempts'=>$qattempts,
        'steps'=>$steps
      );
      return $ergebnis;
    }
    /**
     * $daten: 
     *   userids Liste von Nutzer-Moodle-IDs
     * @return 'ok'
     */
    static function tests_abgaben_loeschen($daten) {
      global $DB;
      $DB->delete_records_select('quiz_grades',"userid in(".implode(',',$daten->userids).")");
      $DB->delete_records_select('quiz_attempts',"userid in(".implode(',',$daten->userids).")");
      return 'ok';
    }
    
    /**
     * $daten: 
     *   instance Moodle-ID des Labels (Text- und Medienfeld)
     *   ODER
     *   courseid Moodle-ID des Kurses
     *   kapiteltitel Titel des zu erstellenden Kapitels
     *
     *   titel Label-Titel für den Seiten-Inhaltsverzeichnis (name)
     *   inhalt Label-inhalt (intro)
     * @return 'ok'
     */
    static function label_speichern($daten) {
      global $DB;
      if(isset($daten->instance) && $daten->instance>0) {
        $DB->update_record('label',(object)array(
          'id'=>$daten->instance,
          'name'=>$daten->titel,
          'intro'=>$daten->inhalt
        ));
        return 'ok';
      }
      $course=$DB->get_record('course', array('id' => $daten->courseid));
      $existingsections=$DB->get_records_sql("SELECT id,section,summary from {course_sections} WHERE course=? order by section",array($course->id));
      bearbeite_anfragen::do_kapitel_erstellen($course,count($existingsections),$daten->kapiteltitel,'',$daten->titel,$daten->inhalt);
      return 'ok';
    }
    
    /**
     * $daten: 
     *   courseid (Moodle-ID des Kurses)
     *   modname (Plugin-Name der Aufgabe: assign oder quiz)
     *   cmid (course_module-id)
     *   cminstance (Moodle-ID der Aufgabe)
     *   termin (Y-m-d h:i:s)
     *   dauer (in Minuten)
     * Es werden automatisch 5 Minuten davor und 5 Minten danach addiert.
     * @return 'ok'
     */
    static function aufgabe_freischalten($daten) {
      global $DB;
      $DB->update_record('course_modules',(object)array(
        'id'=>$daten->cmid
        ,'visible'=>1
        ,'visibleold'=>1
      ));
      if($daten->modname=='assign') {
        $t=strtotime($daten->termin);
        $DB->update_record('assign',(object)array(
          'id'=>$daten->cminstance
          ,'allowsubmissionsfromdate'=>$t-5*60
          ,'cutoffdate'=>$t+$daten->dauer*60+5*60
        ));
      } else if($daten->modname=='quiz') {
        $t=strtotime($daten->termin);
        $DB->update_record('quiz',(object)array(
          'id'=>$daten->cminstance
          ,'timeopen'=>$t-5*60
          ,'timeclose'=>$t+$daten->dauer*60+5*60
        ));
      } 
      rebuild_course_cache($daten->courseid,false);
      return 'ok';
    }
    
    /**
     * $daten: 
     *   courseid (Moodle-ID des Kurses)
     *   modname (Aufgaben-Typ: 'quiz' oder 'assign')
     *   cminstance (Moodle-ID der Aufgabe)
     *   userids (Moodle-IDs der TN)
     * @return 
     *   Liste mit 1 Objekt pro TN. In jedem Objekt:
     *   - id (aus der Tabelle mdl_grade_grades, damit Moodle nicht meckert, dass keine ID selectiert wurde)
     *   - userid (Moodle-ID des TNs)
     *   - finalgrade (Note)
     */
    static function noten_finden($daten) {
      global $DB;
      if(empty($daten->userids)) return array();
      $sql="select gg.id,gg.userid,gg.finalgrade
        from {grade_items} gi
          join {grade_grades} gg on gg.itemid=gi.id
        where gi.courseid=".$daten->courseid."
          and gi.itemtype='mod' and gi.itemmodule='".$daten->modname."' and gi.iteminstance=".$daten->cminstance."
          and gg.userid in(".implode(',',$daten->userids).")";
      $noten=$DB->get_records_sql($sql);
      return array_values($noten);
    }
      
    /**
     * Einmaliges setzen aller maildisplay auf 0
     */
    static function emails_verbergen($daten) {
      global $DB, $CFG;
      $systemcontext = \context_system::instance();
      require_capability('moodle/user:update', $systemcontext);
      $DB->execute("update {user} set maildisplay=0");
      return 'OK';
    }
}