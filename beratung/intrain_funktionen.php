<?php
function get_ferien($tag) {
  global $ferien;
  $res=null;
  foreach($ferien as $f) {
    if($tag>=$f->von && $tag<=$f->bis) {
      if($f->art=='Berliner Schulferien') {
        $res=$f;
      } else {
        return $f;
      }
    }
  }
  return $res;
}
function skip_ferien($tag) {
  $res=$tag;
  while(true) {
    $d=date('D',$res);
    if($d=='Sat') $res=strtotime('+2 days',$res);
    else if($d=='Sun') $res=strtotime('+1 day',$res);
    $f=get_ferien($res);
    if(!$f || $f->art=='Berliner Schulferien') break;
    $res=strtotime('+1 day',$f->bis);
  }
  return $res;
}
function format_preis($p) {
  return number_format($p,2,',',' ').' €';
}
function format_dauer($ue) {
  return empty($ue) ? '' : $ue.' UE ('.(0.75*$ue).' h)';
}
?>