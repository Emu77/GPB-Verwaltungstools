<?php
require_once 'check_login.php';
$sortierung=isset($_GET['sortierung']) ? $_GET['sortierung'] : 'vorname';
$s=isset($ich->ihkprojektantrag_tnsortierung) ? $ich->ihkprojektantrag_tnsortierung : explode(',',$ich->tnsortierung);
$s=array_diff($s,array($sortierung));
$s=array_merge(array($sortierung),$s);
$ich->ihkprojektantrag_tnsortierung=$s;
echo 'OK';
echo json_encode($ich->ihkprojektantrag_tnsortierung);
exit;
?>