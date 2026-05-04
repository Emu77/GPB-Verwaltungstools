<?php
require_once '../db.php';

$aktion=isset($_POST['Aktion']) ? $_POST['Aktion'] : (isset($_GET['Aktion']) ? $_GET['Aktion'] : null);

$mitisguid=isset($_POST['MITIS_GUID']) ? $_POST['MITIS_GUID'] : (isset($_GET['MITIS_GUID']) ? $_GET['MITIS_GUID'] : null);
$gpbmitisguid='421DA1F8-A9D2-4362-A7A7-8E375CC34981';
if(empty($mitisguid) || $mitisguid!=$gpbmitisguid) {
  echo 'NOK';
  exit;
}

$mitisid=isset($_POST['StammdatenID']) ? (int)$_POST['StammdatenID'] : (isset($_GET['StammdatenID']) ? (int)$_GET['StammdatenID'] : 0);
if($mitisid>0) {
  $result=$db->query("select * from gpb_basket where mitisid=".$mitisid." limit 1");
  $basket=$result->fetch_object();
  $result->free();
} else {
  $basket=false;
}

if($aktion=='PushData') { //MITIS -> Verw.DB
  $anrede=isset($_POST['Anrede']) ? $_POST['Anrede'] : (isset($_GET['Anrede']) ? $_GET['Anrede'] : '');
  $vorname=isset($_POST['Vorname']) ? $_POST['Vorname'] : (isset($_GET['Vorname']) ? $_GET['Vorname'] : '');
  $nachname=isset($_POST['Nachname']) ? $_POST['Nachname'] : (isset($_GET['Nachname']) ? $_GET['Nachname'] : '');
  $strasse=isset($_POST['Strasse']) ? $_POST['Strasse'] : (isset($_GET['Strasse']) ? $_GET['Strasse'] : '');
  $plz=isset($_POST['PLZ']) ? $_POST['PLZ'] : (isset($_GET['PLZ']) ? $_GET['PLZ'] : '');
  $ort=isset($_POST['Ort']) ? $_POST['Ort'] : (isset($_GET['Ort']) ? $_GET['Ort'] : '');
  $interessenausmitis=isset($_POST['Interesse']) ? $_POST['Interesse'] : (isset($_GET['Interesse']) ? $_GET['Interesse'] : '');
  if(empty($interessenausmitis)) {
    $interessenausmitis=array();
  } else if(is_string($interessenausmitis)) {
    //$interessenausmitis=mb_convert_encoding($interessenausmitis,'UTF-8','ISO-8859-1');
    $interessenausmitis=json_decode($interessenausmitis,null,512,JSON_INVALID_UTF8_SUBSTITUTE);
  }
  $mint=json_encode($interessenausmitis);
  if($basket) {
    $stmt=$db->prepare("update gpb_basket set anrede=?,vorname=?,nachname=?,strasse=?,plz=?,ort=?,interessenausmitis=? where id=? limit 1");
    $stmt->bind_param('sssssssi',$anrede,$vorname,$nachname,$strasse,$plz,$ort,$mint,$basket->id);
    $stmt->execute();
  } else {
    $stmt=$db->prepare("insert into gpb_basket(mitisid,anrede,vorname,nachname,strasse,plz,ort,interessenausmitis) values(?,?,?,?,?,?,?,?)");
    $stmt->bind_param('isssssss',$mitisid,$anrede,$vorname,$nachname,$strasse,$plz,$ort,$mint);
    $stmt->execute();
    $basket=(object)array('id'=>$db->insert_id);
  }
  
  if(!empty($interessenausmitis)) {
    $aktuell=null;
    foreach($interessenausmitis as $dummy=>$interesse) {
      $interesse=(object)$interesse;
      if(((int)$interesse->aktiv)==0) continue;
      if(((int)$interesse->Hauptinteresse)==0) continue;
      $interessenbereich=null;
      $zeitmodell=null;
      $anfangsdatum=null;
      $ausbildungmitisids=array();
      $modulmitisids=array();
      $modulmitisanfaenge=array();
      $interessendatum=implode('-',array_reverse(explode('.',$interesse->Datum)));
      if(!empty($interesse->VzTz)) {
        $zeitmodell=$interesse->VzTz;
      }
      $module=(array)$interesse->Modul;
      foreach($module as $dum=>$mod) {
        $mod=(object)$mod;
        if(isset($mod->PaketID) && ((int)$mod->PaketID)!=192 && ((int)$mod->PaketID)!=0) {
          $ausbildungmitisids[$mod->PaketID]=true;
        }
        $modulmitisids[$mod->ModulID]=true;
        if(empty($mod->Beginn)) {
          $modulmitisanfaenge[$mod->ModulID]='';
        } else {
          $beginn=implode('-',array_reverse(explode('.',$mod->Beginn)));
          $modulanmitisfaenge[$mod->ModulID]=$beginn;
          if(empty($anfangsdatum) || $beginn<$anfangsdatum) {
            $anfangsdatum=$beginn;
          }
        }
      }
      if(empty($ausbildungmitisids) && empty($modulmitisids)) continue;
      $ausbildungids=array();
      if(!empty($ausbildungmitisids)) {
        $result=$db->query("select id,mitisid from gpb_spezausbildung where mitisid in(".implode(',',array_keys($ausbildungmitisids)).")");
        while($row=$result->fetch_object()) {
          $ausbildungmitisids[$row->mitisid]=$row->id;
          $ausbildungids[$row->id]=true;
          $interessenbereich='spez';
        }
        $result->free();
      }
      $modulids=array();
      $modulanfaenge=array();
      if(!empty($modulmitisids)) {
        $result=$db->query("select id,mitisid from gpb_spezmodul where mitisid in(".implode(',',array_keys($modulmitisids)).")");
        while($row=$result->fetch_object()) {
          $modulmitisids[$row->mitisid]=$row->id;
          $modulids[$row->id]=true;
          $modulanfaenge[$row->id]=$modulmitisanfaenge[$row->mitisid];
          $interessenbereich='spez';
        }
        $result->free();
        $result=$db->query("select id,mitisid from gpb_intrainmodul where mitisid in(".implode(',',array_keys($modulmitisids)).")");
        while($row=$result->fetch_object()) {
          $modulmitisids[$row->mitisid]=$row->id;
          $modulids[$row->id]=true;
          $modulanfaenge[$row->id]=$modulmitisanfaenge[$row->mitisid];
          $interessenbereich='intrain';
        }
        $result->free();
      }
      $interesse=(object)array(
        'interessenbereich'=>$interessenbereich,
        'datum'=>$interessendatum,
        'zeitmodell'=>$zeitmodell,
        'anfangsdatum'=>$anfangsdatum,
        'ausbildungids'=>$ausbildungids,
        'modulids'=>$modulids,
        'modulanfaenge'=>$modulanfaenge
      );
      if(empty($aktuell)) {
        $aktuell=$interesse;
      } else if($interesse->datum>$aktuell->datum) {
        $aktuell=$interesse;
      } else if($interesse->datum<$aktuell->datum) {
        //ignorieren
      } else if($aktuell->interessenbereich==$interesse->interessenbereich) {
        //selber Interessenbereich, selbes Datum: mergen
        if(empty($aktuell->zeitmodell)) $aktuell->zeitmodell=$interesse->zeitmodell;
        if(empty($aktuell->anfangsdatum) || (!empty($interesse->anfangsdatum) && $interesse->anfangsdatum>$aktuell->anfangsdatum)) $aktuell->anfangsdatum=$interesse->anfangsdatum;
		if(!empty($interesse->ausbildungids)) {
			//array_merge per Hand, weil IDs numerische Keys sind
			foreach($interesse->ausbildungids as $aid=>$mids) {
				$aktuell->ausbildungids[$aid]=$mids;
			}
		}
		if(!empty($interesse->modulids)) {
			//array_merge per Hand, weil IDs numerische Keys sind
			foreach($interesse->modulids as $mid=>$val) {
				$aktuell->modulids[$mid]=$val;
			}
		}
        if(!empty($interesse->modulanfaenge)) {
			//array_merge per Hand, weil IDs numerische Keys sind
			foreach($interesse->modulanfaenge as $mid=>$anf) {
				$aktuell->modulanfaenge[$mid]=$anf;
			}
		}
      }
    }
    if(empty($aktuell)) {
      $_SESSION['intrain_neuesinteresse']=true;
    } else {
      $_SESSION['intrain_neuesinteresse']=false;
      if($interessenbereich=='spez' && count($aktuell->ausbildungids)==1) {
        $modids=array();
        $allesGebucht=true;
        $ausbid=0;
        $result=$db->query("select * from gpb_spezausbildung_modul where ausbildungid in(".implode(',',array_keys($aktuell->ausbildungids)).")");
        while($row=$result->fetch_object()) {
          $ausbid=$row->ausbildungid;
          $modids[$row->modulid]=true;
          if(!isset($aktuell->modulids[$row->modulid])) $allesGebucht=false;
        }
        $result->free();
        if($allesGebucht) {
          //array_diff_key per Hand, weil IDs numerische Keys sind
          foreach($modids as $modid=>$dummy) {
            if(isset($aktuell->modulids[$modid])) {
              unset($aktuell->modulids[$modid])
            }
          }
          $aktuell->ausbildungids=array($ausbid=>$modids);
        } else {
          $aktuell->ausbildungids=array();
        }
      } else {
        $aktuell->ausbildungids=array();
      }
      $ausbildungids=json_encode($aktuell->ausbildungids);
      $modulids=json_encode($aktuell->modulids);
      $modulanfaenge=json_encode($aktuell->modulanfaenge);
      $stmt=$db->prepare("update gpb_basket set interessenbereich=?,zeitmodell=?,anfangsdatum=?,ausbildungids=?,modulids=?,modulanfaenge=? where id=? limit 1");
      $stmt->bind_param('ssssssi',$aktuell->interessenbereich,$aktuell->zeitmodell,$aktuell->anfangsdatum,$ausbildungids,$modulids,$modulanfaenge,$basket->id);
      $stmt->execute();
    }
  }
  echo 'https://verwaltung.gpbmedien.de/beratung/mitis_weiterleitung.php?mitisid='.$mitisid.'&interessenbereich='.$interessenbereich;
  exit;
} 
if($aktion=='GetData') { //Verw.DB -> MITIS
  if(empty($basket)) {
    header('Content-type: text/html; charset=utf-8');
    echo "Keine Daten für diesen Nutzer.";
    exit;
  }
  $notiz='';
  if(!empty($basket->urlaub)) {
    $urbaub=json_decode($basket->urlaub);
    foreach($urlaub as $u) {
      $notiz.=(empty($notiz) ? 'Urlaub: ' : ', ').date('d.m.Y',strtotime($u->beginn)).' - '.date('d.m.Y',strtotime($u->ende));
    }
  }
  $data=array(
    'StammdatenID'=>$mitisid,
    'Interessen'=>json_decode($basket->interessenfuermitis),
    'Notiz'=>$notiz
  );
  header('Content-type: text/html; charset=utf-8');
  echo json_encode($data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
echo 'NOK';
var_dump($_POST);
var_dump($_GET);
exit;
?>