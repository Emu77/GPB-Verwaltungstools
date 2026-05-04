<?php
/**
 * Wird von MITIS aufgerufen
 * Entfernt die übergebenen Emailadressen aus Moodle
 */

// nomail.php
// by Axel Kloss, faktorWEB IT-Dienstleistungen, 2010-06-01
// Skript löscht per Parameter übergebene E-Mail-Adressen aus der Moodle-DB
// (wird durch MITIS aufgerufen)

//require_once("config.php");
//
//// Parameter übernehmen
//$adr=trim(urldecode($_GET["adr"]));
//$tok=trim($_GET["tok"]);
//
//// Berechtigung (Verschlüsselung) prüfen
//$salt="2x-Qf6w)jR!k+9As";
//$l_k = strlen($salt);
//$l_t = strlen($adr);
//$encoded = "";
//$k = 0;
//for($i=0; $i<$l_t; $i++) {
//    if($k >= $l_k) $k = 0; // Wenn ende des keys, dann wieder von vorne
//    $encoded .= chr(ord($adr[$i]) ^ ord($salt[$k])); // Verschlüsselung
//    $k++;
//}
//
//// weiter wenn gültig sonst Fehlermeldung
//if ($tok==strtoupper(md5($encoded)) or $tok==strtolower(md5($encoded))) {
//    // Datenbankaktion
//    $db = mysqli_connect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass);
//    if (!is_object($db)) die('failed to connect to database: '.$db->connect_error);
//    $db->select_db($CFG->dbname) or die ('failed:'.$db->error);
//    $db->query("UPDATE ".$CFG->prefix."user SET email=' ', emailstop=1 WHERE LOWER(email)='".strtolower($adr)."';") or die ('failed:'.$db->error);
//    $db->close();
//    die("ok");
//} else {
//    die("wrong parameter");
//}

die("ok");
?>