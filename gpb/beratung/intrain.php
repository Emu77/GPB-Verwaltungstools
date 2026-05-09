<?php
require_once 'check_login.php';

$bereiche=array();
$bereicheById=array();
$result=$db->query("select * from gpb_intrainbereich order by id");
while($row=$result->fetch_object()) {
  $row->paketids=array();
  $bereiche[]=$row;
  $bereicheById[$row->id]=$row;
}
$result->free();
$leerbereich=(object)array(
  'id'=>0,
  'bereichname'=>'Weitere Module',
  'ort'=>'',
  'farbe'=>'white',
  'paketids'=>array()
);
$bereiche[]=$leerbereich;
$bereicheById[$leerbereich->id]=$leerbereich;
$pakete=array();
$paketeById=array();
$result=$db->query("select * from gpb_intrainpaket order by bereichid,kurzbezeichnung");
while($row=$result->fetch_object()) {
  $row->modulids=array();
  $row->anzahlue=0;
  $row->preis=0.0;
  $pakete[]=$row;
  $paketeById[$row->id]=$row;
  $bereicheById[$row->bereichid]->paketids[]=$row->id;
}
$result->free();
$module=array();
$moduleById=array();
$result=$db->query("select m.*,z.titel as zertiftitel
  from gpb_intrainmodul m
  left outer join gpb_intrainzertif z on z.id=m.zertifid
  order by m.kurzbezeichnung");
while($row=$result->fetch_object()) {
  $row->zugeordnet=false;
  $row->ausgewaehlt=false;
  $row->anzahlue=(int)$row->anzahlue;
  $row->vollzeit_wochenue=(int)$row->vollzeit_wochenue;
  $row->preis=(float)$row->preis;
  $module[]=$row;
  $moduleById[$row->id]=$row;
}
$result->free();
$result=$db->query("select * from gpb_intrainpaket_modul");
while($row=$result->fetch_object()) {
  $paketeById[$row->paketid]->modulids[]=$row->modulid;
  $moduleById[$row->modulid]->zugeordnet=true;
}
$result->free();
$leerpaket=(object)array(
  'id'=>0,
  'mitisid'=>null,
  'bereichid'=>0,
  'kurzbezeichnung'=>'',
  'bezeichnung'=>'Weitere Module',
  'beschreibung'=>'',
  'voraussetzungen'=>'',
  'lernziele'=>'',
  'lerninhalte'=>'',
  'nutzen'=>'',
  'preisproue'=>0.0,
  'modulids'=>array(),
  'anzahlue'=>0,
  'preis'=>0.0
);
$pakete[]=$leerpaket;
$paketeById[$leerpaket->id]=$leerpaket;
$bereicheById[$leerpaket->bereichid]->paketids[]=$leerpaket->id;
foreach($module as $mod) {
  if($mod->zugeordnet) continue;
  $leerpaket->modulids[]=$mod->id;
}

require_once 'BeratungSeite.php';
$seite=BeratungSeite::$menueByUrl['intrain.php'];
$seite->anfangGenerieren();
?>
<link rel="stylesheet" href="<?= $seite->baseDir ?>intrain_styles.css" />
<div id="temp" style="display:none;">
<?php 
$keinheader=true;
require_once 'intrain_seite.php';
foreach($modulids as $modid) {
  if(!isset($moduleById[$modid])) continue;
  $moduleById[$modid]->ausgewaehlt=true;
}
?>
</div>
<div id="rumpf">
<div id="linke_spalte" class="linke spalte">
</div> <!-- linke spalte -->

<div class="mittlere spalte">
  <a href="intrain_module.php" style="float:right;">Übersicht Module</a>
  <div><b>Modulsuche:</b> <input type="text" id="modulfilter" onchange="module_filtern()" /><button type="button" onclick="module_filtern()">&#x1F50D;</button></div>
  <div id="bereich_auswahl">
    <div style="display:inline-block;"><input type="radio" name="rb_bereich" checked onchange="bereich_ausgewaehlt(null)">Alle&nbsp;</div>
  </div>
  <table id="table_alle_module" border="1" cellspacing="0" style="border-collapse:collapse;">
  </table>
</div> <!-- mittlere spalte -->

<div class="rechte spalte">
  <div id="details_kopf" style="font-weight:bold;">
    <div id="details_nummer" style="display:inline-block;font-size:16px;"></div>
    <div id="details_titel" style="display:inline-block;font-size:16px;"></div>
  </div>
  <div id="details_kragen" style="font-weight:bold;margin-bottom:1em;padding-top:1em;">
    <div id="details_dauer" style="font-size:16px;"></div>
    <div id="details_preis" style="font-size:16px;"></div>
    <a id="details_pdf" href="" style="display:none;margin-top:1em;">Beschreibung als PDF</a>
  </div>
  <div style="font-weight:bold;">Lernziele</div>
  <div id="details_lernziele"></div>
  <div style="font-weight:bold;">Lerninhalte</div>
  <div id="details_lerninhalte"></div>
  <div style="font-weight:bold;">Beruflicher Nutzen</div>
  <div id="details_nutzen"></div>
  <div style="font-weight:bold;">Voraussetzungen</div>
  <div id="details_voraussetzungen"></div>
  <div style="font-weight:bold;">Details</div>
  <div id="details_beschreibung"></div>
</div> <!-- rechte spalte -->
</div> <!-- rumpf -->

<div id="fuss">
</div> <!-- fuss -->

<script>
function seite_laden(url) {
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    document.getElementById('temp').innerHTML=req.responseText;
    inhalte_installieren();
  };
  req.open('GET',url);
  req.send();
}
function formular_senden(formid) {
  const temp=document.getElementById('temp');
  const frame=document.createElement('iframe');
  temp.appendChild(frame);
  frame.onload=function() {
    inhalte_installieren(frame.contentWindow);
  };
  let form=document.getElementById(formid);
  form.remove();
  form.onsubmit=null;
  frame.contentWindow.document.body.appendChild(form);
  form.submit();
}
function inhalte_installieren(win=window) {
  let alt=document.getElementById('linke_spalte');
  for(let i=alt.childNodes.length-1;i>=0;--i) {
    alt.childNodes[i].remove();
  }
  let neu=win.document.getElementById('linke_spalte_inhalt');
  while(neu.childNodes.length>0) {
    let n=neu.childNodes[0];
    n.remove();
    alt.appendChild(n);
  }
  neu.remove();
  alt=document.getElementById('fuss');
  for(let i=alt.childNodes.length-1;i>=0;--i) {
    alt.childNodes[i].remove();
  }
  neu=win.document.getElementById('fuss_inhalt');
  while(neu.childNodes.length>0) {
    let n=neu.childNodes[0];
    n.remove();
    alt.appendChild(n);
  }
  neu.remove();
  neu=win.document.getElementById('aktualisierungen');
  if(neu) {
    eval(neu.innerText);
    neu.remove();
  }
  document.getElementById('temp').innerHTML='';
  arbeitsdauer_rechnen(false);
  for(let mid in module) {
    let m=module[mid];
    if(m.ausgewaehlt && !basket_modulids[mid]) {
      m.ausgewaehlt=false;
      for(let pid in m.cbs) {
        m.cbs[pid].checked=false;
      }
    } else if(!m.ausgewaehlt && basket_modulids[mid]) {
      m.ausgewaehlt=true;
      for(let pid in m.cbs) {
        m.cbs[pid].checked=true;
      }
    }
  }
}
inhalte_installieren();
function formatDauer(ue) {
  let h=Math.floor(0.75*ue);
  let m=60*(0.75*ue-h);
  return ''+ue+' UE ('+h+(m>0 ? ':'+(m<10 ? '0' : '')+m : '')+' h)';
}
function formatPreis(p) {
  let res=p.toFixed(2).replace('.',',')+' €';
  if(p>=1000) {
    let i=p>100000 ? 3 : p>10000 ? 2 : 1;
    res=res.substring(0,i)+' '+res.substring(i);
  }
  return res;
}
var arbeitszeiten=<?= json_encode($arbeitszeiten) ?>;
var minwochenue=<?= $zeitmodell=='Vollzeit' ? $vollzeit_wochenue : $teilzeit_minwochenue ?>;
var maxwochenue=<?= $zeitmodell=='Vollzeit' ? $vollzeit_wochenue : $teilzeit_maxwochenue ?>;
var basket_modulids=<?= json_encode($modulids) ?>;
function arbeitszeiten_kopieren() {
  let orig=null;
  for(let tag in arbeitszeiten) {
    if(orig) {
      arbeitszeiten[tag].von=orig.von;
      document.getElementById(tag+'_von').value=orig.von;
      arbeitszeiten[tag].bis=orig.bis;
      document.getElementById(tag+'_bis').value=orig.bis;
    } else {
      orig=arbeitszeiten[tag];
    }
  }
  arbeitsdauer_rechnen(true);
}
function anzahlue_eingegeben(tag) {
  let ue=parseInt(document.getElementById(tag+'_anzahlue').value);
  if(isNaN(ue)) {
    arbeitsdauer_rechnen(false);
    return;
  }
  document.getElementById(tag+'_h').value=0.75*ue;
  let pause=ue*45>=6*60 ? 60 : 0;
  document.getElementById(tag+'_pause').innerText=pause>0 ? '+ 1 h Pause' : '';
  let min=ue*45+pause;
  let vonm=parseUhrzeit(tag+'_von','08:15');
  let bism=vonm+min;
  let h=Math.floor(bism/60),m=bism%60;
  document.getElementById(tag+'_bis').value=(h<10 ? '0' : '')+h+':'+(m<10 ? '0' : '')+m;
  arbeitsdauer_rechnen(true);
}
function h_eingegeben(tag) {
  let h=parseFloat(document.getElementById(tag+'_h').value);
  if(isNaN(h)) {
    arbeitsdauer_rechnen(false);
    return;
  }
  document.getElementById(tag+'_anzahlue').value=Math.round(h/0.75);
  anzahlue_eingegeben(tag);
}
function parseUhrzeit(inputid,def) {
  let v=document.getElementById(inputid).value.trim();
  if(!v) v=def;
  let i=v.indexOf(':');
  let h=parseInt(i<0 ? v.length==4 ? v.substring(0,2) : v : v.substring(0,i));
  let m=i<0 ? v.length==4 ? v.substring(2) : 0 : parseInt(v.substring(i+1));
  if(isNaN(h)) h=parseInt(def.substring(0,def.indexOf(':')));
  if(isNaN(m)) m=0;
  let res=Math.max(0,Math.min(60*24-1,60*h+m));
  h=Math.floor(res/60);
  m=res%60;
  document.getElementById(inputid).value=(h<10 ? '0' : '')+h+':'+(m<10 ? '0' : '')+m;
  return res;
}
function arbeitsdauer_rechnen(speichern=false) {
  let uetot=0;
  for(let tag in arbeitszeiten) {
    let vonm=parseUhrzeit(tag+'_von','08:15');
    arbeitszeiten[tag].von=document.getElementById(tag+'_von').value;
    let bism=parseUhrzeit(tag+'_bis','16:00');
    arbeitszeiten[tag].bis=document.getElementById(tag+'_bis').value;
    let min=Math.max(0,bism-vonm);
    let ue=Math.ceil(min/45);
    let pause=0;
    if(ue*45>=6*60) {
      pause=60;
      ue=Math.ceil((min-pause)/45);
    }
    if(ue!=(min-pause)/45) {
      min=ue*45;
      pause=min>=6*60 ? 60 : 0;
      min+=pause;
      bism=vonm+min;
      let h=Math.floor(bism/60),m=bism%60;
      document.getElementById(tag+'_bis').value=(h<10 ? '0' : '')+h+':'+(m<10 ? '0' : '')+m;
      arbeitszeiten[tag].bis=(h<10 ? '0' : '')+h+':'+(m<10 ? '0' : '')+m;
    }
    document.getElementById(tag+'_anzahlue').value=ue;
    document.getElementById(tag+'_h').value=0.75*ue;
    document.getElementById(tag+'_pause').innerText=pause>0 ? ' + 1 h Pause' : '';
    uetot+=ue;
  }
  document.getElementById('wochenue').value=uetot;
  document.getElementById('arbeitszeiten').value=JSON.stringify(arbeitszeiten);
  document.getElementById('zeit_summe').innerText=formatDauer(uetot);
  document.getElementById('zeit_summe').style.backgroundColor=uetot>=minwochenue && uetot<=maxwochenue ? 'rgb(192,255,192)' : 'rgb(255,192,192)';
  if(speichern) {
    formular_senden('arbeitszeiten_form');
  }
}
arbeitsdauer_rechnen(false);

var bereiche=<?= json_encode($bereicheById) ?>;
var pakete=<?= json_encode($paketeById) ?>;
var module=<?= json_encode($moduleById) ?>;
var filter=document.getElementById('modulfilter');
var ausgewaehlterBereich=null;
var table=document.getElementById('table_alle_module');
function init_alle_module() {
  for(let bid in bereiche) {
    let b=bereiche[bid];
    let div=document.createElement('div');
    document.getElementById('bereich_auswahl').appendChild(div);
    div.style.display='inline-block';
    div.style.backgroundColor=b.farbe;
    div.style.paddingRight='1ch';
    let radio=document.createElement('input');
    div.appendChild(radio);
    radio.type='radio';
    radio.name='rb_bereich';
    radio.onchange=()=>bereich_ausgewaehlt(b);
    div.appendChild(document.createTextNode(b.bereichname));
    for(let pid of b.paketids) {
      let p=pakete[pid];
      p.geoeffnet=false;
      p.tr=table.insertRow();
      p.tr.style.backgroundColor=b.farbe;
      p.tr.style.display='none';
      let th=document.createElement('th');
      p.tr.appendChild(th);
      let btn=document.createElement('button');
      th.appendChild(btn);
      btn.type='button';
      btn.innerHTML='&#x25B7;';
      btn.onclick=()=>paket_toggeln(p);
      p.oeffner=btn;
      th=document.createElement('th');
      p.tr.appendChild(th);
      th.innerText=p.kurzbezeichnung;
      th.style.cursor='pointer';
      th.onclick=()=>paket_details_anzeigen(b,p);
      th=document.createElement('th');
      p.tr.appendChild(th);
      th.innerText=p.bezeichnung;
      th.style.cursor='pointer';
      th.onclick=()=>paket_details_anzeigen(b,p);
      for(let mid of p.modulids) {
        let m=module[mid];
        p.anzahlue+=m.anzahlue;
        p.preis+=m.preis;
        m.gefunden=false;
        if(!m.trs) {
          m.trs={};
          m.cbs={};
        }
        let tr=table.insertRow();
        m.trs[pid]=tr;
        tr.style.display='none';
        let td=tr.insertCell();
        let cb=document.createElement('input');
        td.appendChild(cb);
        cb.type='checkbox';
        cb.checked=m.ausgewaehlt;
        cb.onchange=()=>modul_toggeln(m);
        m.cbs[pid]=cb;
        td=tr.insertCell();
        td.innerText=m.kurzbezeichnung;
        td.style.cursor='pointer';
        td.onclick=()=>modul_details_anzeigen(b,p,m);
        td=tr.insertCell();
        td.innerText=m.titel;
        td.style.cursor='pointer';
        td.onclick=()=>modul_details_anzeigen(b,p,m);
      }
    }
  }
}
init_alle_module();
function module_filtern() {
  let f=filter.value.trim();
  if(f) {
    f=f.toLowerCase();
    for(let mid in module) {
      let m=module[mid]
      m.gefunden=m.kurzbezeichnung.toLowerCase().indexOf(f)>=0 || m.katalognummer.toLowerCase().indexOf(f)>=0 || m.titel.toLowerCase().indexOf(f)>=0;
    }
  } else {
    for(let mid in module) {
      module[mid].gefunden=false;
    }
  }
  zeilenFiltern();
}
function bereich_ausgewaehlt(bereich) {
  ausgewaehlterBereich=bereich;
  zeilenFiltern();
}
function paket_toggeln(paket) {
  paket.geoeffnet=!paket.geoeffnet;
  paket.oeffner.innerHTML=paket.geoeffnet ? '&#x25BD;' : '&#x25B7;';
  zeilenFiltern();
}
function zeilenFiltern() {
  let f=filter.value;
  for(let bid in bereiche) {
    let b=bereiche[bid];
    if(b==ausgewaehlterBereich) {
      for(let pid of b.paketids) {
        let p=pakete[pid];
        p.tr.style.display='';
        for(let mid of p.modulids) {
          let m=module[mid];
          m.trs[pid].style.display=p.geoeffnet || (f && m.gefunden) ? '' : 'none';
        }
      }
    } else {
      for(let pid of b.paketids) {
        let p=pakete[pid];
        p.tr.style.display='none';
        for(let mid of p.modulids) {
          let m=module[mid];
          if(!ausgewaehlterBereich && m.gefunden) {
            m.trs[pid].style.display='';
            p.tr.style.display='';
          } else {
            m.trs[pid].style.display='none';
          }
        }
      }
    }
  }
}
function modul_toggeln(modul) {
  if(modul.ausgewaehlt) {
    modul.ausgewaehlt=false;
    for(let pid in modul.cbs) {
      modul.cbs[pid].checked=false;
    }
    seite_laden('intrain_modul_entmerken.php?modulid='+modul.id);
  } else {
    modul.ausgewaehlt=true;
    for(let pid in modul.cbs) {
      modul.cbs[pid].checked=true;
    }
    seite_laden('intrain_modul_merken.php?modulid='+modul.id);
  }
}
function paket_details_anzeigen(bereich,paket) {
  document.getElementById('details_kopf').style.backgroundColor=bereich.farbe;
  document.getElementById('details_kragen').style.backgroundColor=bereich.farbe;
  document.getElementById('details_nummer').innerText='Paket '+paket.kurzbezeichnung;
  document.getElementById('details_pdf').href='intrain_beschreibung_pdf.php?paketid='+paket.id;
  document.getElementById('details_pdf').style.display='block';
  document.getElementById('details_titel').innerText=paket.bezeichnung;
  details_anzeigen(paket);
}
function modul_details_anzeigen(bereich,paket,modul) {
  document.getElementById('details_kopf').style.backgroundColor='';
  document.getElementById('details_kragen').style.backgroundColor='';
  document.getElementById('details_nummer').innerText='Modul '+modul.kurzbezeichnung;
  document.getElementById('details_pdf').href='intrain_beschreibung_pdf.php?modulid='+modul.id;
  document.getElementById('details_pdf').style.display='block';
  document.getElementById('details_titel').innerText=modul.titel;
  details_anzeigen(modul);
}
function details_anzeigen(o) {
  document.getElementById('details_dauer').innerText=formatDauer(o.anzahlue);
  document.getElementById('details_preis').innerText=formatPreis(o.preis);
  document.getElementById('details_beschreibung').innerText=o.beschreibung;
  document.getElementById('details_voraussetzungen').innerText=o.voraussetzungen;
  document.getElementById('details_lernziele').innerText=o.lernziele;
  document.getElementById('details_lerninhalte').innerText=o.lerninhalte;
  document.getElementById('details_nutzen').innerText=o.nutzen;
}
</script>
<?php
$seite->endeGenerieren();
?>