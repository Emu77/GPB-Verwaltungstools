<?php
require_once 'check_login.php';
require_once '../Liste.php';
require_once '../Tn.php';
require_once 'TnKurs.php';
require_once 'TnFerien.php';
require_once 'anwesenheitsArten.php';

$tn=Tn::einenLaden($ich->id,'Tn');
if(empty($tn)) {
  header('Location:kurse.php');
  exit;
}
$tn->ferienLaden('TnFerien');
$ferienByTag=array();
foreach($tn->ferien as $fer) {
  for($d=max($fer->von,$tn->von);$d<=min($fer->bis,$tn->bis);$d=strtotime('+1 day',$d)) {
    $ferienByTag[date('Y-m-d',$d)]=$fer;
  }
}

$kurse=new Liste('TnKurs',$db->prepare("
  select distinct k.*
    from gpb_klasse_tn ktn 
    join gpb_kurs_klasse kk on kk.klasseid=ktn.klasseid
    join gpb_kurs k on k.id=kk.kursid
    where ktn.tnid=".$tn->id."
    and (ktn.einstieg<>'0000-00-00' and ktn.einstieg is not null and ktn.einstieg<=k.ende)
    and (ktn.ausstieg='0000-00-00' or ktn.ausstieg is null or ktn.ausstieg>=k.beginn)
    order by beginn,ende,titel"));
Kurs::refsLaden($kurse);

$kurseByKW=array();
$heute=strtotime('today');
$von=strtotime('+10 years');
$bis=0;
foreach($kurse->alle as $k) {
  if(!empty($k->von) && !empty($k->bis)) {
    for($wann=$k->von;$wann<=$k->bis;$wann=strtotime('+1 week',$wann)) {
      $kw=date('Y-W',$wann);
      if(isset($kurseByKW[$kw])) {
        $kurseByKW[$kw][]=$k;
      } else {
        $kurseByKW[$kw]=array($k);
      }
    }
  }
  if(!empty($k->von) && $k->von<$von) {
    $von=$k->von;
  }
  if(!empty($k->bis) && $k->bis>$bis) {
    $bis=$k->bis;
  }
}
if(date('D',$von)!='Mon') {
  $von=strtotime('last monday',$von);
}
$bis=min($heute,$bis==0 ? strtotime('+4 days',$von) : $bis);

$tagnamen=array('Mon'=>'Mo','Tue'=>'Di','Wed'=>'Mi','Thu'=>'Do','Fri'=>'Fr','Sat'=>'Sa','Sun'=>'So');
$tage=array();
for($wann=$von;$wann<=$bis;$wann=strtotime('+1 day',$wann)) {
  $t=$tagnamen[date('D',$wann)];
  if($t!='Sa' && $t!='So') {
    $tage[date('Y-m-d',$wann)]=$t.' '.date('d.m.Y',$wann);
  }
}

$anwesenheiten=array();
$result=$db->query("select * from gpb_anwesenheit where tag>='".date('Y-m-d',$von)."' and tag<='".date('Y-m-d',$bis)."' and tnid=".$tn->id);
while($row=$result->fetch_object()) {
  $row->anfangTitel=$anwesenheitsArten[$row->anfang];
  $row->endeTitel=$anwesenheitsArten[$row->ende];
  $row->kguTitel=$anwesenheitsArten[$row->kgu];
  if(empty($row->mitis)) {
    if($row->anfang=='A' || $row->ende=='A') {
      $row->mitis='A';
    } else if($row->anfang=='O' || $row->ende=='O') {
      $row->mitis='O';
    } else if($row->anfang=='F' || $row->ende=='F') {
      $row->mitis='X';
    }
  }
  $row->mitisTitel=$anwesenheitsArtenMitis[$row->mitis];
  $anwesenheiten[$row->tag]=$row;
}
$result->free();
$leereAnwesenheit=(object)array('anfang'=>'&nbsp;','ende'=>'&nbsp;','kgu'=>'&nbsp;','mitis'=>'&nbsp;',
                                'anfangTitel'=>'','endeTitel'=>'','kguTitel'=>'','mitisTitel'=>'');

require_once 'TnSeite.php';
$seite=Seite::$menueByUrl['anwesenheit.php'];
$seite->anfangGenerieren();
if(empty($kurse->alle)) {
  echo 'Keine Kurse.';
} else if($von>$bis) {
  echo 'Kurse nur in der Zukunft.';
}
?>
<table id="anw_table" border="1" cellspacing="0" style="border-collapse:collapse;">
  <tr>
    <th rowspan="2">KW</th>
    <th rowspan="2">Kurse</th>
    <th rowspan="2" class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">Moodle</th>
    <th colspan="4">Mo</th>
    <th colspan="4">Di</th>
    <th colspan="4">Mi</th>
    <th colspan="4">Do</th>
    <th colspan="3">Fr</th>
  </tr>
  <tr>
    <th>Anfang</th>
    <th>Ende</th>
    <th>KGU</th>
    <th>Tag</th>
    <th>Anfang</th>
    <th>Ende</th>
    <th>KGU</th>
    <th>Tag</th>
    <th>Anfang</th>
    <th>Ende</th>
    <th>KGU</th>
    <th>Tag</th>
    <th>Anfang</th>
    <th>Ende</th>
    <th>KGU</th>
    <th>Tag</th>
    <th>Anfang</th>
    <th>Ende</th>
    <th>Tag</th>
  </tr>
<?php
for($montag=$von;$montag<=$bis;$montag=strtotime('+1 week',$montag)) {
  $kw=date('Y-W',$montag);
?>
  <tr>
    <th rowspan="2">KW <?= substr($kw,5) ?></th>
    <td rowspan="2">
<?php
  if(isset($kurseByKW[$kw])) {
    foreach($kurseByKW[$kw] as $k) {
?>
      <a href="kurs_sehen.php?kursid=<?= $k->id ?>"><?= $k->titel ?></a><br />
<?php
    }
  }
?>
    </td>
    <td rowspan="2" class="<?= $moodleisttest ? 'testmoodle' : 'moodle' ?>">
<?php
  if(isset($kurseByKW[$kw])) {
    foreach($kurseByKW[$kw] as $k) {
      if($k->moodleid>0) {
?>
      <a href="<?= $moodleurl ?>course/view.php?id=<?= $k->id ?>">Moodle-ID=<?= $k->moodleid ?></a><br />
<?php
      } else {
?>
      <br />
<?php
      }
    }
  }
?>
    </td>
<?php
  for($d=0;$d<5;++$d) {
    $datum=strtotime('+'.$d.' days',$montag);
?>
    <td colspan="<?= $d==4 ? 3 : 4 ?>" align="center"><?= date('d.m.Y',$datum) ?></td>
<?php
  }
?>
  </tr>
  <tr>
<?php
  for($d=0;$d<5;++$d) {
    $wann=strtotime('+'.$d.' days',$montag);
    $datum=date('Y-m-d',$wann);
    if(isset($ferienByTag[$datum])) {
      $fer=$ferienByTag[$datum];
?>
    <td colspan="<?= $d==4 ? 3 : 4 ?>" class="ferien" align="center"><a href="ferien_sehen.php?ferienid=<?= $fer->id ?>"><?= $fer->anlass ?></a></td>
<?php
    } else {
      $anw=isset($anwesenheiten[$datum]) ? $anwesenheiten[$datum] : $leereAnwesenheit;
?>
    <td class="anwesenheit" id="anw_<?= $tn->id ?>_<?= $datum ?>_anfang" title="<?= htmlspecialchars($anw->anfangTitel,ENT_QUOTES) ?>"><?= $anw->anfang ?></td>
    <td class="anwesenheit" id="anw_<?= $tn->id ?>_<?= $datum ?>_ende" title="<?= htmlspecialchars($anw->endeTitel,ENT_QUOTES) ?>"><?= $anw->ende ?></td>
<?php
      if($d!=4) {
?>
    <td class="anwesenheit" id="anw_<?= $tn->id ?>_<?= $datum ?>_kgu" title="<?= htmlspecialchars($anw->kguTitel,ENT_QUOTES) ?>"><?= $anw->kgu ?></td>
<?php
      }
?>
    <td class="anwesenheit mitis <?= $anw->mitis=='X' || $anw->mitis=='?' ? 'ungeklaert' : '' ?>" id="anw_<?= $tn->id ?>_<?= $datum ?>_mitis" title="<?= htmlspecialchars($anw->mitisTitel,ENT_QUOTES) ?>"><?= $anw->mitis ?></td>
<?php
    }
  }
?>
  </tr>
<?php
}
?>
</table>
<?php
$seite->endeGenerieren();
?>