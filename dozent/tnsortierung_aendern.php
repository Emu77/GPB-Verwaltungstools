<?php
require_once 'check_login.php';
$kriterium=array(isset($_GET['kriterium']) && !empty($_GET['kriterium']) ? $_GET['kriterium'] : 'nachname');
$tnsortierung=isset($ich->tnsortierung) ? explode(',',$ich->tnsortierung) : array('ktn.klasseid','nachname','vorname');
$tnsortierung=array_diff($tnsortierung,$kriterium);
$tnsortierung=array_merge($kriterium,$tnsortierung);
$tnsortierung=implode(',',$tnsortierung);

$db->query("update gpb_dozent set tnsortierung='".$tnsortierung."' where id=".$ich->id." limit 1");
$ich->tnsortierung=$tnsortierung;

$url=isset($_GET['url']) ? $_GET['url'] : null;
header('Location:'.(empty($url) ? 'index.php' : $url));
exit;
?>