<?php
require_once 'check_login.php';
require_once 'angebot_funktionen.php';
require_once 'intrain_teilzeit_wochenue.php';

$interessentsuche=isset($_POST['interessentsuche']) ? $_POST['interessentsuche'] : false;
$gefunden=array();
if(!empty($interessentsuche)) {
  $suche='%'.$interessentsuche.'%';
  $stmt=$db->prepare("select mitisid,vorname,nachname from gpb_basket where concat(vorname,' ',nachname) like ? or mitisid=? order by vorname,nachname limit 10");
  $stmt->bind_param('ss',$suche,$interessentsuche);
  $stmt->execute();
  $result=$stmt->get_result();
  while($row=$result->fetch_object()) {
    $gefunden[]=$row;
  }
  $result->free();
}

$interessent=isset($_SESSION['intrain_interessent']) ? $_SESSION['intrain_interessent'] : null;
$zeitmodell=isset($_SESSION['intrain_zeitmodell']) && !empty($_SESSION['intrain_zeitmodell']) ? $_SESSION['intrain_zeitmodell'] : 'Vollzeit';
$wochenue=isset($_SESSION['intrain_wochenue']) && !empty($_SESSION['intrain_wochenue']) ? $_SESSION['intrain_wochenue'] : 30;
$arbeitszeiten=isset($_SESSION['intrain_arbeitszeiten']) && !empty($_SESSION['intrain_arbeitszeiten']) ? $_SESSION['intrain_arbeitszeiten'] 
  : array(
    'Mo'=>(object)array('von'=>'08:15','bis'=>($zeitmodell=='Vollzeit' ? '16:00' : '12:45')),
    'Di'=>(object)array('von'=>'08:15','bis'=>($zeitmodell=='Vollzeit' ? '16:00' : '12:45')),
    'Mi'=>(object)array('von'=>'08:15','bis'=>($zeitmodell=='Vollzeit' ? '16:00' : '12:45')),
    'Do'=>(object)array('von'=>'08:15','bis'=>($zeitmodell=='Vollzeit' ? '14:30' : '12:45')),
    'Fr'=>(object)array('von'=>'08:15','bis'=>($zeitmodell=='Vollzeit' ? '13:45' : '12:45'))
  );
$anfangsdatum=isset($_SESSION['intrain_anfangsdatum']) && !empty($_SESSION['intrain_anfangsdatum']) ? $_SESSION['intrain_anfangsdatum'] : date('Y-m-d',strtotime('next monday'));
$modulids=isset($_SESSION['intrain_modulids']) ? $_SESSION['intrain_modulids'] : array();
$modulanfaenge=isset($_SESSION['intrain_modulanfaenge']) ? $_SESSION['intrain_modulanfaenge'] : array();
$neuesinteresse=isset($_SESSION['intrain_neuesinteresse']) ? $_SESSION['intrain_neuesinteresse'] : false;

$basketmodule=array();
$basketModuleById=array();
$basketpreis=0.0;
$basketue=0;
if(!empty($modulids)) {
  $result=$db->query("select m.*,z.titel as zertiftitel
    from gpb_intrainmodul m 
    left outer join gpb_intrainzertif z on z.id=m.zertifid
    where m.id in(".implode(',',array_keys($modulids)).") order by m.zertifid,m.kurzbezeichnung,m.titel");
  while($row=$result->fetch_object()) {
    $row->inbasket=true;
    $row->anzahlue=(int)$row->anzahlue;
    $row->vollzeit_wochenue=(int)$row->vollzeit_wochenue;
    $row->preis=(float)$row->preis;
    if(isset($modulanfaenge[$row->id])) {
      $row->anfang=$modulanfaenge[$row->id];
      $row->von=strtotime($row->anfang);
    } else {
      $row->anfang='';
      $row->von=0;
    }
    $basketmodule[]=$row;
    $basketModuleById[$row->id]=$row;
    $basketpreis+=$row->preis;
    $basketue+=$row->anzahlue;
  }
  $result->free();
}
usort($basketmodule,'module_vergleichen');

if($zeitmodell=='Vollzeit') {
  $vollzeit_wochenue=0;
  foreach($basketmodule as $mod) {
    if(!empty($mod->vollzeit_wochenue)) {
      if($vollzeit_wochenue==0) {
        $vollzeit_wochenue=$mod->vollzeit_wochenue;
      } else if($vollzeit_wochenue>0 && $vollzeit_wochenue!=$mod->vollzeit_wochenue) {
        $vollzeit_wochenue=-1;
      }
    }
  }
  if($vollzeit_wochenue==0) {
    $vollzeit_wochenue=40;
  }
}
$tagnamen=array(
  'Mon'=>'Mo',
  'Tue'=>'Di',
  'Wed'=>'Mi',
  'Thu'=>'Do',
  'Fri'=>'Fr'
);
foreach($arbeitszeiten as $tagname=>$zeit) {
  $v=60*(int)substr($zeit->von,0,2)+(int)substr($zeit->von,3);
  $b=60*(int)substr($zeit->bis,0,2)+(int)substr($zeit->bis,3);
  $m=$b-$v;
  $zeit->anzahlue=ceil($m/45);
  $zeit->pause=0;
  if($zeit->anzahlue*45>=6*60) {
    $zeit->pause=60;
    $zeit->anzahlue=ceil(($m-60)/45);
  }
}

$ferien=array();
$result=$db->query("select * from gpb_ferien where art in('Feiertag','Berliner Schulferien') and ende>=current_date() order by beginn,ende");
while($row=$result->fetch_object()) {
  $row->von=strtotime($row->beginn);
  $row->bis=strtotime($row->ende);
  $ferien[]=$row;
}
$result->free();
if($interessent) {
  foreach($interessent->urlaub as $row) {
    $ferien[]=(object)array(
      'beginn'=>$row->beginn,
      'von'=>strtotime($row->beginn),
      'ende'=>$row->ende,
      'bis'=>strtotime($row->ende),
      'art'=>'Urlaub',
      'anlass'=>'Urlaub'
    );
  }
}

$woue=$wochenue;
$kalvon=strtotime(date('Y-m-01'));
$tag=skip_ferien(strtotime($anfangsdatum));
$letztertag=$tag;
$kal=array();
foreach($basketmodule as $mod) {
  if($zeitmodell=='Vollzeit') {
    $woue=empty($mod->vollzeit_wochenue) ? 40 : $mod->vollzeit_wochenue;
  }
  if(!empty($mod->anfang)) {
    $tag=max($tag,skip_ferien($mod->von));
    $letztertag=$tag;
  }
  $mod->von=$tag;
  if(!isset($erstertag)) {
    $erstertag=$tag;
  }
  for($dauer=0;$dauer<$mod->anzahlue;/*nichts*/) {
    $kal[date('Y-m-d',$tag)]=$mod;
    $mod->bis=$tag;
    $letztertag=$tag;
    $dauer+=$arbeitszeiten[$tagnamen[date('D',$tag)]]->anzahlue;
    $tag=skip_ferien(strtotime('+1 day',$tag));
  }
}
if(isset($erstertag)) {
  $kalbis=strtotime('next month',$tag);
} else {
  $erstertag=$tag;
  $kalbis=strtotime('+6 months',$tag);
}

$wochensek=strtotime('2025-01-08')-strtotime('2025-01-01');
$monatsnamen=array(
  '01'=>'Januar',
  '02'=>'Februar',
  '03'=>'März',
  '04'=>'April',
  '05'=>'Mai',
  '06'=>'Juni',
  '07'=>'Juli',
  '08'=>'August',
  '09'=>'September',
  '10'=>'Oktober',
  '11'=>'November',
  '12'=>'Dezember'
);
if(!isset($keinheader)) {
?>
<!DOCTYPE html>
<html>
<head>
  <title>inTrain Seite zum Inkludieren</title>
</head>
<body>
<?php
}
?>
<div id="linke_spalte_inhalt" class="linke spalte">
  <div id="interessent_div">
    <b>Interessent</b>
    <form id="such_form" action="intrain_interessent_suchen.php" onsubmit="formular_senden(this.id)" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="text" name="interessentsuche" value="<?= htmlspecialchars($interessentsuche,ENT_QUOTES) ?>" style="width:200px;" /><button type="button" onclick="formular_senden('such_form')">Suchen</button><br />
    (% für "beliebige Buchstaben")
    </form>
<?php
foreach($gefunden as $gef) {
?>
    <a href="javascript:seite_laden('intrain_interessent_merken.php?mitisid=<?= $gef->mitisid ?>')"><?= $gef->vorname ?> <?= $gef->nachname ?></a> <button onclick="seite_laden('intrain_interessent_merken.php?mitisid=<?= $gef->mitisid ?>')">Auswählen</button><br />
<?php
}
?>
    <br />
<?php
if(isset($_SESSION['intrain_fehler'])) {
  echo $_SESSION['intrain_fehler'];
  unset($_SESSION['intrain_fehler']);
}
if($interessent) {
?>
    <b><?= $interessent->anrede ?> <?= $interessent->vorname ?> <?= $interessent->nachname ?></b><br />
    <?= empty($interessent->strasse) ? '(Adresse unbekannt)' : $interessent->strasse ?><br />
    <?= $interessent->plz ?> <?= $interessent->ort ?><br />
<?php
} else {
?>
    <b>Bitte Interessent suchen und auswählen</b><br />
<?php
}
?>
  </div> <!-- interessent_div -->
  
  <div id="zeit_div">
    <form id="zeitmodell_form" action="intrain_zeitmodell_merken.php" onsubmit="formular_senden(this.id)" method="post" enctype="multipart/form-data" accept-charset="UTF-8" style="margin:0;">
      <input type="radio" name="zeitmodell" value="Vollzeit" <?= $zeitmodell=='Vollzeit' ? 'checked' : '' ?> onchange="formular_senden('zeitmodell_form')" /><b> Vollzeit &nbsp;</b>
      <input type="radio" name="zeitmodell" value="Teilzeit" <?= $zeitmodell=='Teilzeit' ? 'checked' : '' ?> onchange="formular_senden('zeitmodell_form')" /><b> Teilzeit</b>
    </form>
    <b>Lernzeit pro Woche:</b>
<?php
if($zeitmodell=='Vollzeit') {
  if($vollzeit_wochenue<=0) {
    echo "Siehe ausgewählte Ausbildung und Module<br />\n";
  } else {
    echo format_dauer($vollzeit_wochenue)."<br />\n";
  }
} else {
?>
    <?= $teilzeit_minwochenue ?> bis <?= $teilzeit_maxwochenue ?> UE (<?= 0.75*$teilzeit_minwochenue ?> bis <?= 0.75*$teilzeit_maxwochenue ?> h)<br />
<?php
}
?>
    <form id="arbeitszeiten_form" action="intrain_arbeitszeiten_merken.php" onsubmit="formular_senden(this.id)" method="post" enctype="multipart/form-data" accept-charset="UTF-8" style="margin:0;margin-bottom:1em;">
      <input type="hidden" name="wochenue" id="wochenue" value="" />
      <input type="hidden" name="arbeitszeiten" id="arbeitszeiten" value="" />
    <table border="0" cellspacing="0" style="border-collapse:collapse;">
      <tr>
        <th></th>
        <td colspan="4"><button type="button" onclick="arbeitszeiten_kopieren()">Alle wie Montag</button></td>
      </tr>
<?php
foreach($tagnamen as $en=>$de) {
?>
      <tr>
        <th><?= $de ?></th>
        <td><input type="text" style="width:6ch;" value="<?= $arbeitszeiten[$de]->von ?>" id="<?= $de ?>_von" onchange="arbeitsdauer_rechnen(true)" /></td>
        <td><input type="text" style="width:6ch;" value="<?= $arbeitszeiten[$de]->bis ?>" id="<?= $de ?>_bis" onchange="arbeitsdauer_rechnen(true)" /></td>
        <td>&nbsp;&nbsp;<input type="number" min="0" step="1" id="<?= $de ?>_anzahlue" value="<?= $arbeitszeiten[$de]->anzahlue ?>" style="width:40px;" onchange="anzahlue_eingegeben('<?= $de ?>')" />&nbsp;UE</td>
        <td> = <input type="number" min="0" step="0.25" id="<?= $de ?>_h" value="<?= 0.75*$arbeitszeiten[$de]->anzahlue ?>" style="width:50px;"  onchange="h_eingegeben('<?= $de ?>')" />&nbsp;h</td>
        <td id="<?= $de ?>_pause"></td>
      </tr>
<?php
}
?>
      <tr>
        <th colspan="3" align="right">Lernzeit pro Woche:</th>
        <td colspan="2">&nbsp;&nbsp;<span id="zeit_summe"></span></td>
        <td></td>
      </tr>
    </table>
    </form>
    
    <form id="anfangsdatum_form" action="intrain_anfangsdatum_merken.php" onsubmit="formular_senden(this.id)" method="post" enctype="multipart/form-data" accept-charset="UTF-8" style="margin:0;">
      <b>Anfangsdatum: </b><input type="date" name="anfangsdatum" value="<?= $anfangsdatum ?>" onchange="formular_senden('anfangsdatum_form')" /><br />
      <b>Enddatum: </b><?= date('d.m.Y',$letztertag) ?>
    </form>
  </div> <!-- zeit_div -->
  <div id="urlaub_div">
<?php
if($interessent) {
?>
    <b>Individueller Urlaub</b><br />
<?php
  if(!empty($interessent->urlaub)) {
    foreach($interessent->urlaub as $u) {
?>
    <?= date('d.m.Y',strtotime($u->beginn)) ?><?= $u->ende==$u->beginn ? '' : ' - '.date('d.m.Y',strtotime($u->ende)) ?> <button type="button" onclick="seite_laden('intrain_urlaub_entfernen.php?beginn=<?= $u->beginn ?>&ende=<?= $u->ende ?>')">Löschen</button><br />
<?php
    }
  }
?>
    <form id="urlaub_form" action="intrain_urlaub_merken.php" onsubmit="formular_senden(this.id)" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
      <input type="date" name="beginn" value="" /> - <input type="date" name="ende" value="" /> <button type="button" onclick="formular_senden('urlaub_form')">Merken</button>
    </form>
<?php
}
?>
  </div> <!-- urlaub_div -->

  <div id="basket_div">
    <b>Ausgewählte Module</b> &nbsp; &nbsp; <a href="intrain_beschreibung_pdf.php">Beschreibung als PDF</a>
    <table border="1" cellspacing="0" style="border-collapse:collapse;">
      <tr>
        <th></th>
        <th>Nummer</th>
        <th>Titel</th>
        <th>Preis</th>
        <th>Dauer</th>
<?php
  if($zeitmodell=='Vollzeit') {
?>
        <th>UE/Woche</th>
<?php
}
?>
        <th>Anfang</th>
        <th>Ende</th>
        <th>Dauer (Wo)</th>
        <th>Zertif.</th>
      </tr>
<?php
foreach($basketmodule as $mod) {
?>
      <tr>
        <td><button type="button" onclick="seite_laden('intrain_modul_entmerken.php?modulid=<?= $mod->id ?>')">Entfernen</button></td>
        <td><?= $mod->kurzbezeichnung ?></td>
        <td><?= $mod->titel ?></td>
        <td><?= format_preis($mod->preis) ?></td>
        <td><?= format_dauer($mod->anzahlue) ?></td>
<?php
  if($zeitmodell=='Vollzeit') {
?>
        <td><?= format_dauer($mod->vollzeit_wochenue) ?></td>
<?php
}
?>
        <td><input type="date" value="<?= date('Y-m-d',$mod->von) ?>" onchange="seite_laden('intrain_modulanfang_merken.php?modulid=<?= $mod->id ?>&anfang='+this.value)" /></td>
        <td><?= date('d.m.Y',$mod->bis) ?></td>
        <td align="center"><?= ceil((strtotime('+1 day',$mod->bis)-$mod->von)/$wochensek/0.5)*0.5 ?> Wo</td>
        <td><?= $mod->zertiftitel ?></td>
      </tr>
<?php
}
?>
      <tr>
        <th align="right" colspan="3">Gesamt</th>
        <th align="right"><?= format_preis($basketpreis) ?></th>
        <th align="right"><?= format_dauer($basketue) ?></th>
<?php
  if($zeitmodell=='Vollzeit') {
?>
        <th></th>
<?php
}
?>
        <th><?= date('d.m.Y',$erstertag) ?></th>
        <th><?= date('d.m.Y',$letztertag) ?></th>
        <th><?= ceil((strtotime('+1 day',$letztertag)-$erstertag)/$wochensek/0.5)*0.5 ?> Wo</th>
        <th></th>
      </tr>
    </table>
  </div> <!-- basket_div -->
  
  <div id="angebot_div">
<?php
if(!empty($interessent) && !empty($basketmodule)) {
?>
    <form id="angebotsdaten_speichern_form" action="intrain_angebotsdaten_speichern.php" onsubmit="formular_senden(this.id)" method="post" enctype="multipart/form-data" accept-charset="UTF-8" style="display:inline">
<?php
  if(!empty($interessent->interessenausmitis)) {
?>
      <div style="display:inline-block;text-align:right;">
        Interesse aus MITIS ändern <input type="radio" name="intrain_neuesinteresse" value="N" <?= $neuesinteresse ? '' : 'checked' ?> /><br />
        Neues Interesse anlegen <input type="radio" name="intrain_neuesinteresse" value="J" <?= $neuesinteresse ? 'checked' : '' ?> />
      </div>
<?php
  }
?>
      <button type="button" onclick="formular_senden('angebotsdaten_speichern_form')">Daten speichern<br />zwecks MITIS-Import</button>
    </form>    
<?php
}
if(!empty($basketmodule)) {
?>
    <button type="button" onclick="location.href='intrain_angebot_generieren.php'">Angebot erstellen</button>
<?php
}
?>
  </div> <!-- angebot_div -->
</div> <!-- linke spalte -->

<div id="fuss_inhalt">
<div id="kalender_div">
<?php
$heute=date('Y-m-d');
$modul=null;
for($monat=$kalvon,$naechsterMonat=strtotime('+1 month',$monat);$monat<$kalbis;$monat=$naechsterMonat,$naechsterMonat=strtotime('+1 month',$monat)) {
  $monatsnummer=date('m.Y',$monat);
?>
    <div class="monat">
    <?= $monatsnamen[date('m',$monat)] ?> <?= date('Y',$monat) ?>
    <table border="1" cellspacing="0" style="border-collapse:collapse;">
      <tr>
        <th>&nbsp;</th>
        <th>Mo</th>
        <th>Di</th>
        <th>Mi</th>
        <th>Do</th>
        <th>Fr</th>
        <th>Sa</th>
        <th>So</th>
      </tr>
<?php
  for($woche=strtotime('last monday',$monat);$woche<$naechsterMonat;$woche=strtotime('+1 week',$woche)) {
?>
      <tr>
        <th>KW <?= sprintf('%2d',(int)date('W',$woche)) ?></th>
<?php
    for($t=0,$tag=$woche;$t<7;++$t,$tag=strtotime('+1 day',$tag)) {
      $date=date('Y-m-d',$tag);
      $cn='';
      $title='';
      $anfangok=$date>$heute;
      if(date('m.Y',$tag)!=$monatsnummer) {
        $cn='na';
        $title='';
        $anfangok=false;
      } else if($t>=5) {
        $cn='ferien';
        $title='Wochenende';
        $anfangok=false;
      } else if(isset($kal[$date])) {
        $m=$kal[$date];
        if($m!=$modul) {
          $modul=$m;
          $cn='anfang';
        } else {
          $cn='arbeit';
        }
        $title=$m->titel;
      }
      if($cn!='na') {
        $f=get_ferien($tag);
        if($f) {
          $cn.=$f->art=='Berliner Schulferien' ? ' schulferien' : ' ferien';
          $title.=(empty($title) ? '' : "\n").$f->anlass;
        }
      }
?>
        <td class="<?= $cn ?><?= $anfangok ? ' anfangok' : '' ?>" title="<?= $title ?>" <?= $anfangok ? 'onclick="seite_laden(\'intrain_anfangsdatum_merken.php?anfangsdatum='.$date.'\')"' : '' ?>><?= $cn=='na' ? '' : date('d',$tag) ?></td>
<?php
    }
?>
      </tr>
<?php
  }
?>
    </table>
    </div>
<?php
}
?>
</div> <!-- kalender_div -->
</div> <!-- fuss -->
<div id="aktualisierungen" style="display:none;">
arbeitszeiten=<?= json_encode($arbeitszeiten) ?>;
minwochenue=<?= $zeitmodell=='Vollzeit' ? $vollzeit_wochenue : $teilzeit_minwochenue ?>;
maxwochenue=<?= $zeitmodell=='Vollzeit' ? $vollzeit_wochenue : $teilzeit_maxwochenue ?>;
basket_modulids=<?= json_encode($modulids) ?>;
</div>
<?php
if(!isset($keinheader)) {
?>
</body>
</html>
<?php
}
?>