<?php
function module_vergleichen($mod0,$mod1) {
  if($mod0->von==$mod1->von) {
    return strcasecmp($mod0->titel,$mod1->titel);
  }
  if($mod0->von==0) return 1;
  if($mod1->von==0) return -1;
  if($mod0->von<$mod1->von) return -1;
  if($mod0->von>$mod1->von) return 1;
  return 0;
}
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
  $h=floor(0.75*$ue);
  $m=60*(0.75*$ue-$h);
  return empty($ue) ? '' : $ue.' UE ('.$h.($m>0 ? ':'.($m<10 ? '0' : '').$m : '').' h)';
}
?>