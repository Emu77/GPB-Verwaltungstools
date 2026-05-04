<?php
require_once 'check_login.php';
$suche=new Suche();
$suche->inputString('dozentname');
$suche->inputString('nutzername');
if($ich->istleiter) {
  $suche->inputBoolean('istintrain','istintrain');
  $suche->nurverfuegbare=isset($_POST['nurverfuegbare']) ? $_POST['nurverfuegbare'] : '';
  if($suche->nurverfuegbare=='Ja') {
    $suche->addKriterium("idrverfuegbar",false,false);
  } else if($suche->nurverfuegbare=='Nein') {
    $suche->addKriterium("not idrverfuegbar",false,false);
  }
  $suche->iststamm=isset($_POST['iststamm']) && $_POST['iststamm']!='N';
  if($suche->iststamm) {
    $suche->addKriterium("(select count(*) 
      from gpb_kurs_dozent kd
      join gpb_kurs k on k.id=kd.kursid
      where kd.dozentid=doz.id
      and k.ende>=date_sub(current_date,interval 1 year))>=10",false,false);
  }
}
$suche->ort=isset($_POST['ort']) ? $_POST['ort'] : '';
if(!empty($suche->ort)) {
  $suche->addKriterium("id in(select kd.dozentid 
    from gpb_kurs_dozent kd 
    join gpb_kurs k on k.id=kd.kursid 
    join gpb_raum r on r.id=k.raumid 
    where k.ende>=date_sub(current_date,interval 1 year) and r.ort=?)",$suche->ort,'s');
}
$_SESSION['dozenten_suche']=$suche;
header('Location:dozenten.php');
exit;
?>