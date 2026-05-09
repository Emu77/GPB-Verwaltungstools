<?php
require_once 'check_login.php';
require_once 'angebot_funktionen.php';
require_once 'spez_teilzeit_wochenue.php';

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

$interessent=isset($_SESSION['spez_interessent']) ? $_SESSION['spez_interessent'] : null;
$zeitmodell=isset($_SESSION['spez_zeitmodell']) && !empty($_SESSION['spez_zeitmodell']) ? $_SESSION['spez_zeitmodell'] : 'Vollzeit';
$wochenue=isset($_SESSION['spez_wochenue']) && !empty($_SESSION['spez_wochenue']) ? $_SESSION['spez_wochenue'] : 24;
$anfangsdatum=isset($_SESSION['spez_anfangsdatum']) && !empty($_SESSION['spez_anfangsdatum']) ? $_SESSION['spez_anfangsdatum'] : date('Y-m-d',strtotime('next monday'));
$ausbildungids=isset($_SESSION['spez_ausbildungids']) ? $_SESSION['spez_ausbildungids'] : array();
$modulids=isset($_SESSION['spez_modulids']) ? $_SESSION['spez_modulids'] : array();
$modulanfaenge=isset($_SESSION['spez_modulanfaenge']) ? $_SESSION['spez_modulanfaenge'] : array();

$ausbildungen=array();
$ausbildungenById=array();
$result=$db->query("select * from gpb_spezausbildung");
while($row=$result->fetch_object()) {
  $ausbildungen[]=$row;
  $ausbildungenById[$row->id]=$row;
}
$result->free();
$module=array();
$moduleById=array();
$result=$db->query("select m.* from gpb_spezmodul m order by m.titel");
while($row=$result->fetch_object()) {
  $module[]=$row;
  $moduleById[$row->id]=$row;
}
$result->free();

$preis=0.0;
$anzahlue=0;
foreach($ausbildungids as $ausid=>$modids) {
  $aus=$ausbildungenById[$ausid];
  $aus->preis=0.0;
  $aus->anzahlue=0;
  $aus->module=array();
  foreach($modids as $modid=>$dummy) {
    $mod=$moduleById[$modid];
    $aus->preis+=$mod->preis;
    $aus->anzahlue+=$mod->anzahlue;
    if(isset($modulanfaenge[$mod->id])) {
      $mod->anfang=$modulanfaenge[$mod->id];
      $mod->von=strtotime($mod->anfang);
    } else {
      $mod->anfang='';
      $mod->von=0;
    }
    $aus->module[]=$mod;
  }
  usort($aus->module,'module_vergleichen');
  $preis+=$aus->preis;
  $anzahlue+=$aus->anzahlue;
}
$einzelmodule=array();
foreach($modulids as $modid=>$dummy) {
  if($modid<=0) continue;
  $mod=$moduleById[$modid];
  $preis+=$mod->preis;
  $anzahlue+=$mod->anzahlue;
  if(isset($modulanfaenge[$mod->id])) {
    $mod->anfang=$modulanfaenge[$mod->id];
    $mod->von=strtotime($mod->anfang);
  } else {
    $mod->anfang='';
    $mod->von=0;
  }
  $einzelmodule[]=$mod;
}
usort($einzelmodule,'module_vergleichen');

if($zeitmodell=='Vollzeit') {
  $vollzeit_wochenue=0;
  foreach($ausbildungids as $ausid=>$modids) {
    $aus=$ausbildungenById[$ausid];
    if(!empty($aus->vollzeit_wochenue)) {
      if($vollzeit_wochenue==0) {
        $vollzeit_wochenue=$aus->vollzeit_wochenue;
      } else if($vollzeit_wochenue>0 && $vollzeit_wochenue!=$aus->vollzeit_wochenue) {
        $vollzeit_wochenue=-1;
      }
    }
  }
  foreach($einzelmodule as $mod) {
    if(empty($mod->vollzeit_wochenue) && $mod->zertifmit>0) {
      $aus=$ausbildungenById[$mod->zertifmit];
      $mod->vollzeit_wochenue=$aus->vollzeit_wochenue;
    }
    if(!empty($mod->vollzeit_wochenue)) {
      if($vollzeit_wochenue==0) {
        $vollzeit_wochenue=$mod->vollzeit_wochenue;
      } else if($vollzeit_wochenue>0 && $vollzeit_wochenue!=$mod->vollzeit_wochenue) {
        $vollzeit_wochenue=-1;
      }
    }
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
foreach($ausbildungids as $ausid=>$modids) {
  $aus=$ausbildungenById[$ausid];
  if($zeitmodell=='Vollzeit') {
    $woue=empty($ausbildung->vollzeit_wochenue) ? 38 : $ausbildung->vollzeit_wochenue;
  }
  foreach($aus->module as $mod) {
    if(!empty($mod->anfang)) {
      $tag=max($tag,skip_ferien(strtotime($mod->anfang)));
      $letztertag=$tag;
    }
    $mod->von=$tag;
    if(!isset($aus->von)) {
      $aus->von=$tag;
    }
    if(!isset($erstertag)) {
      $erstertag=$tag;
    }
    for($dauer=0;$dauer<$mod->anzahlue;$dauer+=$woue/5) {
      $kal[date('Y-m-d',$tag)]=$mod;
      $mod->bis=$tag;
      $letztertag=$tag;
      $tag=skip_ferien(strtotime('+1 day',$tag));
    }
  }
  $aus->bis=$letztertag;
}
foreach($einzelmodule as $mod) {
  if($zeitmodell=='Vollzeit') {
    $woue=empty($mod->vollzeit_wochenue) ? 38 : $mod->vollzeit_wochenue;
  }
  if(!empty($mod->anfang)) {
    $tag=max($tag,skip_ferien(strtotime($mod->anfang)));
    $letztertag=$tag;
  }
  $mod->von=$tag;
  if(!isset($erstertag)) {
    $erstertag=$tag;
  }
  for($dauer=0;$dauer<$mod->anzahlue;$dauer+=$woue/5) {
    $kal[date('Y-m-d',$tag)]=$mod;
    $mod->bis=$tag;
    $letztertag=$tag;
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

require_once 'BeratungSeite.php';
$seite=BeratungSeite::$menueByUrl['spezialisten.php'];
$seite->anfangGenerieren();
?>
<link rel="stylesheet" href="<?= $seite->baseDir ?>spez_styles.css" />
<div id="rumpf">
<div class="linke spalte">
  <div id="interessent_div">
    <b>Interessent</b>
    <form action="spezialisten.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <input type="text" name="interessentsuche" value="<?= htmlspecialchars($interessentsuche,ENT_QUOTES) ?>" style="width:200px;" /><input type="submit" value="Suchen" /><br />
    (% für "beliebige Buchstaben")
    </form>
<?php
foreach($gefunden as $gef) {
?>
    <a href="spez_interessent_merken.php?mitisid=<?= $gef->mitisid ?>"><?= $gef->vorname ?> <?= $gef->nachname ?></a> <button onclick="location.href='spez_interessent_merken.php?mitisid=<?= $gef->mitisid ?>';">Auswählen</button><br />
<?php
}
?>
    <br />
<?php
if(isset($_SESSION['spez_fehler'])) {
  echo $_SESSION['spez_fehler'];
  unset($_SESSION['spez_fehler']);
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
    <form id="zeitmodell_form" action="spez_zeitmodell_merken.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
      <input type="radio" name="zeitmodell" value="Vollzeit" <?= $zeitmodell=='Vollzeit' ? 'checked' : '' ?> onchange="document.getElementById('zeitmodell_form').submit()" /><b> Vollzeit &nbsp;</b>
      <input type="radio" name="zeitmodell" value="Teilzeit" <?= $zeitmodell=='Teilzeit' ? 'checked' : '' ?> onchange="document.getElementById('zeitmodell_form').submit()" /><b> Teilzeit</b>
    </form>
    <b>Lernzeit pro Woche:</b>
<?php
if($zeitmodell=='Vollzeit') {
  if($vollzeit_wochenue<=0) {
    echo "Siehe ausgewählte Ausbildung und Module<br />\n";
  } else {
    echo $vollzeit_wochenue.' UE ('.sprintf('%0.1f',0.75*$vollzeit_wochenue)." h)<br />\n";
  }
} else {
?>
    <?= $teilzeit_minwochenue ?> bis <?= $teilzeit_maxwochenue ?> UE (<?= 0.75*$teilzeit_minwochenue ?> bis <?= 0.75*$teilzeit_maxwochenue ?> h)<br />
    <form id="wochenue_form" action="spez_wochenue_merken.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8" style="display:inline;">
      <input type="number" name="wochenue" value="<?= $wochenue ?>" min="<?= $teilzeit_minwochenue ?>" max="<?= $teilzeit_maxwochenue ?>" style="width:50px;<?= $wochenue<$teilzeit_minwochenue || $wochenue>$teilzeit_maxwochenue ? 'background-color:red;' : '' ?>" onchange="document.getElementById('wochenue_form').submit()" /> UE
    </form>
    <form id="wochenh_form" action="spez_wochenh_merken.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8" style="display:inline;">
      (<input type="number" name="wochenh" value="<?= 0.75*$wochenue ?>" min="<?= 0.75*$teilzeit_minwochenue ?>" max="<?= 0.75*$teilzeit_maxwochenue ?>" step="0.25" style="width:50px;<?= $wochenue<$teilzeit_minwochenue || $wochenue>$teilzeit_maxwochenue ? 'background-color:red;' : '' ?>" onchange="document.getElementById('wochenh_form').submit()" /> h)
    </form><br />
<?php
}
?>
    <br />
    <form id="anfangsdatum_form" action="spez_anfangsdatum_merken.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
      <b>Anfangsdatum: </b><input type="date" name="anfangsdatum" value="<?= $anfangsdatum ?>" onchange="document.getElementById('anfangsdatum_form').submit()" /><br />
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
    <?= date('d.m.Y',strtotime($u->beginn)) ?><?= $u->ende==$u->beginn ? '' : ' - '.date('d.m.Y',strtotime($u->ende)) ?> <button type="button" onclick="location.href='spez_urlaub_entfernen.php?beginn=<?= $u->beginn ?>&ende=<?= $u->ende ?>'">Löschen</button><br />
<?php
    }
  }
?>
    <form action="spez_urlaub_merken.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
      <input type="date" name="beginn" value="" /> - <input type="date" name="ende" value="" /> <input type="submit" value="Merken" />
    </form>
<?php
}
?>
  </div> <!-- urlaub_div -->
</div> <!-- linke spalte -->

<div class="mittlere spalte">
  <div id="module_div">
    <a href="spez_ausbildungen.php" style="float:right;">Übersicht Module</a><br />
    <form id="ausbildungid_form" action="spez_ausbildung_merken.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
      <select name="ausbildungid">
        <option value="">-</option>
<?php
foreach($ausbildungen as $aus) {
?>
        <option value="<?= $aus->id ?>"><?= $aus->titel ?></option>
<?php
}
?>
      </select> <input type="submit" value="Übernehmen" />
    </form>
    
    <form id="modulid_form" action="spez_modul_merken.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
      <select name="modulid">
        <option value="">-</option>
<?php
foreach($module as $mod) {
?>
        <option value="<?= $mod->id ?>"><?= $mod->titel ?></option>
<?php
}
?>
      </select> <input type="submit" value="Hinzufügen" />
    </form>
    
    <br />
    <table id="module_table" border="1" cellspacing="0" style="border-collapse:collapse;">
      <tr>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
        <th>Preis</th>
        <th>Dauer (UE)</th>
<?php
  if($zeitmodell=='Vollzeit') {
?>
        <th>UE/Woche</th>
<?php
}
?>
        <th>Anfang</th>
        <th>Ende</th>
        <th>Dauer (Wochen)</th>
        <th></th>
      </tr>
<?php
foreach($ausbildungids as $ausid=>$modids) {
  $aus=$ausbildungenById[$ausid];
?>
      <tr>
        <td><button type="button" onclick="location.href='spez_ausbildung_entmerken.php?ausbildungid=<?= $aus->id ?>'">Entfernen</button></td>
        <th align="left"><?= $aus->titel ?></th>
        <th><?= format_preis($aus->preis) ?></th>
        <th><?= $aus->anzahlue ?> UE</th>
<?php
  if($zeitmodell=='Vollzeit') {
?>
        <th align="center"><?= $aus->vollzeit_wochenue ?> UE/Wo</th>
<?php
  }
?>
        <th><?= date('d.m.Y',$aus->von) ?></th>
        <th><?= date('d.m.Y',$aus->bis) ?></th>
        <th><?= ceil((strtotime('+1 day',$aus->bis)-$aus->von)/$wochensek/0.5)*0.5 ?> Wochen</th>
        <td><button type="button" onclick="location.href='spez_angebot_generieren.php?nureineausbildungid=<?= $ausid ?>';">Angebot erstellen</button></td>
      </tr>
<?php
  foreach($aus->module as $mod) {
?>
      <tr>
        <td><button type="button" onclick="location.href='spez_modul_entmerken.php?modulid=<?= $mod->id ?>'">Entfernen</button></td>
        <td style="text-align:right"><?= $mod->titel ?></td>
        <td><?= format_preis($mod->preis) ?></td>
        <td><?= $mod->anzahlue ?> UE</td>
<?php
  if($zeitmodell=='Vollzeit') {
?>
        <td><?= $mod->vollzeit_wochenue ?> UE/Wo</td>
<?php
  }
?>
        <td><input type="date" id="modulanfang_<?= $mod->id ?>" value="<?= date('Y-m-d',$mod->von) ?>" onchange="location.href='spez_modulanfang_merken.php?modulid=<?= $mod->id ?>&anfang='+this.value;" /></td>
        <td><?= date('d.m.Y',$mod->bis) ?></td>
        <td align="center"><?= ceil((strtotime('+1 day',$mod->bis)-$mod->von)/$wochensek/0.5)*0.5 ?> Wochen</td>
        <td><button type="button" onclick="location.href='spez_angebot_generieren.php?nureinmodulid=<?= $mod->id ?>&amp;nureinanfangsdatum='+document.getElementById('modulanfang_<?= $mod->id ?>').value;">Angebot erstellen</button></td>
      </tr>
<?php
  }
}
if(!empty($einzelmodule)) {
?>
      <tr>
        <td>&nbsp;</td>
        <th align="left"><?= empty($ausbildungids) ? 'Einzelne' : 'Zusätzliche' ?> Fachkräfte-Module</th>
      </tr>
<?php
  foreach($einzelmodule as $mod) {
?>
      <tr>
        <td><button type="button" onclick="location.href='spez_modul_entmerken.php?modulid=<?= $mod->id ?>'">Entfernen</button></td>
        <td style="text-align:right"><?= $mod->titel ?></td>
        <td><?= format_preis($mod->preis) ?></td>
        <td><?= $mod->anzahlue ?> UE</td>
<?php
  if($zeitmodell=='Vollzeit') {
?>
        <th align="center"><?= $mod->zertifmit>0 ? $ausbildungenById[$mod->zertifmit]->vollzeit_wochenue : $mod->vollzeit_wochenue ?> UE/Wo</th>
<?php
  }
?>
        <td><input type="date" id="modulanfang_<?= $mod->id ?>" value="<?= date('Y-m-d',$mod->von) ?>" onchange="location.href='modulanfang_merken.php?modulid=<?= $mod->id ?>&anfang='+this.value;" /></td>
        <td><?= date('d.m.Y',$mod->bis) ?></td>
        <td align="center"><?= ceil((strtotime('+1 day',$mod->bis)-$mod->von)/$wochensek/0.5)*0.5 ?> Wochen</td>
        <td><button type="button" onclick="location.href='spez_angebot_generieren.php?nureinmodulid=<?= $mod->id ?>&amp;nureinanfangsdatum='+document.getElementById('modulanfang_<?= $mod->id ?>').value';">Angebot erstellen</button></td>
      </tr>
<?php
  }
}
?>
      <tr>
        <th colspan="2" align="right">Gesamt</th>
        <th><?= format_preis($preis) ?></th>
        <th><?= $anzahlue ?> UE</th>
<?php
  if($zeitmodell=='Vollzeit') {
?>
        <th align="center">&nbsp;</th>
<?php
  }
?>
        <th><?= date('d.m.Y',$erstertag) ?></th>
        <th><?= date('d.m.Y',$letztertag) ?></th>
        <th><?= ceil((strtotime('+1 day',$letztertag)-$erstertag)/$wochensek/0.5)*0.5 ?> Wochen</th>
        <td>
<?php
if(!empty($interessent) && !empty($ausbildungids) || !empty($modulids)) {
?>
          <button type="button" onclick="location.href='spez_angebotsdaten_speichern.php'">Daten speichern<br />zwecks MITIS-Import</button><br />
<?php
}
if(!empty($ausbildungids) || !empty($modulids)) {
?>
          <button type="button" onclick="location.href='spez_angebot_generieren.php'">Angebot erstellen</button>
<?php
}
?>
        </td>
      </tr>
    </table>
  </div> <!-- module_div -->
</div> <!-- mittlere spalte -->
</div> <!-- rumpf -->

<div id="fuss">
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
        <td class="<?= $cn ?><?= $anfangok ? ' anfangok' : '' ?>" title="<?= $title ?>" <?= $anfangok ? 'onclick="location.href=\'spez_anfangsdatum_merken.php?anfangsdatum='.$date.'\'"' : '' ?>><?= $cn=='na' ? '' : date('d',$tag) ?></td>
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
<?php
$seite->endeGenerieren();
?>