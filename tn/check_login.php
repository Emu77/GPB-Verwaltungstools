<?php
require_once '../Suche.php';
@session_start();
if(!isset($_SESSION['tn_ich'])) {
  if($_SERVER['REQUEST_METHOD']=='GET') {
    $_SESSION['redirect']=(empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
  }
  header('Location:login.php');
  exit;
}
$ich=$_SESSION['tn_ich'];
require_once '../db.php';

if(isset($SESSION['todos'])) {
  $todos=$_SESSION['todos'];
} else {
  $todos=array();
  $result=$db->query("select k.id,k.titel,k.beginn,k.ende
    from gpb_kurs k
    where k.ende is not null and k.ende<>'0000-00-00' and k.ende<current_date()
      and k.id in(select k.id
        from gpb_klasse_tn ktn 
        join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
        join gpb_kurs_view k on k.id=kk.kursid
        where ktn.tnid=".$ich->id."
        and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
        and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn))
      and k.id not in(select kursid from gpb_bewertung where tnid=".$ich->id.")
    order by k.beginn,k.ende,k.titel");
  while($row=$result->fetch_object()) {
    $row->von=strtotime($row->beginn);
    $row->bis=strtotime($row->ende);
    $todos[]=$row;
  }
  $result->free();
  $_SESSION['todos']=$todos;
}
?>