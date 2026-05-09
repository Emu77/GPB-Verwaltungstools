<?php
require_once '../Suche.php';
@session_start();
if(!isset($_SESSION['dozent_ich'])) {
  if($_SERVER['REQUEST_METHOD']=='GET') {
    $_SESSION['redirect']=(empty($_SERVER['HTTPS']) ? 'http' : 'https') . '://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
  }
  header('Location:login.php');
  exit;
}
$ich=$_SESSION['dozent_ich'];
require_once '../db.php';

if(isset($_SESSION['todos'])) {
  $todos=$_SESSION['todos'];
} else {
  $todos=array();
  $result=$db->query("select k.id,k.titel,k.beginn,k.ende,k.kurzbericht_ok,k.tagesbericht_ok,k.notenstatus
    from gpb_kurs k
    where k.ende is not null and k.ende<>'0000-00-00' and k.ende<current_date()
      and k.id in(select kursid from gpb_kurs_dozent where dozentid=".$ich->id.")
      and (not k.kurzbericht_ok or not k.tagesbericht_ok or k.notenstatus='todo') order by k.beginn,k.ende,k.titel");
  while($row=$result->fetch_object()) {
    $row->von=strtotime($row->beginn);
    $row->bis=strtotime($row->ende);
    $todos[]=$row;
  }
  $result->free();
  $_SESSION['todos']=$todos;
}
?>