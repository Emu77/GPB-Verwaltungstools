<?php
require_once 'check_login.php';

$zertifs=array();
$zertifsById=array();
$result=$db->query("select * from gpb_intrainzertif");
while($row=$result->fetch_object()) {
  $row->vollzeit_von=strtotime($row->vollzeit_beginn);
  $row->vollzeit_bis=strtotime($row->vollzeit_ende);
  $row->teilzeit_von=strtotime($row->teilzeit_beginn);
  $row->teilzeit_bis=strtotime($row->teilzeit_ende);
  $row->zertif_von=strtotime($row->zertif_beginn);
  $row->zertif_bis=strtotime($row->zertif_ende);
  $zertifs[]=$row;
  $zertifsById[$row->id]=$row;
}
$result->free();

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
$result=$db->query("select m.* from gpb_intrainmodul m order by m.kurzbezeichnung");
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
$seite=new BeratungSeite('inTrain Module');
$seite->anfangGenerieren();
?>
<link rel="stylesheet" href="<?= $seite->baseDir ?>intrain_styles.css" />
<div id="kopf" style="margin-bottom:1em;">
<div style="background-color:rgb(238,238,238);">
  <a href="intrain.php" style="float:right;">Zurück zur Angebotserstellung</a>
  <b>Zertifizierungen</b>
  <table border="1" cellspacing="0" cellpadding="2" style="border-collapse:collapse;">
    <tr>
      <th rowspan="2">Kurzname</th>
      <th colspan="3">Vollzeit</th>
      <th colspan="3">Teilzeit</th>
      <th colspan="2">Zertif. gültig</th>
      <th rowspan="2"><button type="button" onclick="zertif_ausgewaehlt(0)">Module ohne Zertif.anzeigen</button></th>
    </tr>
    <tr>
      <th>Maßnahme-Nummer</th>
      <th>Beginn</th>
      <th>Ende</th>
      <th>Maßnahme-Nummer</th>
      <th>Beginn</th>
      <th>Ende</th>
      <th>von</th>
      <th>bis</th>
    </tr>
<?php
foreach($zertifs as $z) {
?>
    <tr>
      <td><span class="editbtn" onclick="zertif_text_bearbeiten(<?= $z->id ?>,'Kurzname','titel')">✎</span> <span id="zertif_<?= $z->id ?>_titel"><?= $z->titel ?></span></td>
      <td><span class="editbtn" onclick="zertif_text_bearbeiten(<?= $z->id ?>,'Maßnahmenummer Vollzeit','vollzeit_massnnr')">✎</span> <span id="zertif_<?= $z->id ?>_vollzeit_massnnr"><?= $z->vollzeit_massnnr ?></span></td>
      <td><span class="editbtn" onclick="zertif_datum_bearbeiten(<?= $z->id ?>,'Beginn Vollzeit','vollzeit_beginn')">✎</span> <span id="zertif_<?= $z->id ?>_vollzeit_beginn"><?= empty($z->vollzeit_von) ? '' : date('d.m.Y',$z->vollzeit_von) ?></span></td>
      <td><span class="editbtn" onclick="zertif_datum_bearbeiten(<?= $z->id ?>,'Ende Vollzeit','vollzeit_ende')">✎</span> <span id="zertif_<?= $z->id ?>_vollzeit_ende"><?= empty($z->vollzeit_bis) ? '' : date('d.m.Y',$z->vollzeit_bis) ?></span></td>
      <td><span class="editbtn" onclick="zertif_text_bearbeiten(<?= $z->id ?>,'Maßnahmenummer Teilzeit','teilzeit_massnnr')">✎</span> <span id="zertif_<?= $z->id ?>_teilzeit_massnnr"><?= $z->teilzeit_massnnr ?></span></td>
      <td><span class="editbtn" onclick="zertif_datum_bearbeiten(<?= $z->id ?>,'Beginn Teilzeit','teilzeit_beginn')">✎</span> <span id="zertif_<?= $z->id ?>_teilzeit_beginn"><?= empty($z->teilzeit_von) ? '' : date('d.m.Y',$z->teilzeit_von) ?></span></td>
      <td><span class="editbtn" onclick="zertif_datum_bearbeiten(<?= $z->id ?>,'Ende Teilzeit','teilzeit_ende')">✎</span> <span id="zertif_<?= $z->id ?>_teilzeit_ende"><?= empty($z->teilzeit_bis) ? '' : date('d.m.Y',$z->teilzeit_bis) ?></span></td>
      <td><span class="editbtn" onclick="zertif_datum_bearbeiten(<?= $z->id ?>,'Zertif. gültig von','zertif_beginn')">✎</span> <span id="zertif_<?= $z->id ?>_zertif_beginn"><?= empty($z->zertif_von) ? '' : date('d.m.Y',$z->zertif_von) ?></span></td>
      <td><span class="editbtn" onclick="zertif_datum_bearbeiten(<?= $z->id ?>,'Zertif. gültig bis','zertif_ende')">✎</span> <span id="zertif_<?= $z->id ?>_zertif_ende"><?= empty($z->zertif_bis) ? '' : date('d.m.Y',$z->zertif_bis) ?></span></td>
      <td>
        <button type="button" onclick="zertif_ausgewaehlt(<?= $z->id ?>)">Module anzeigen</button>
        <button type="button" onclick="zertif_uebernehmen(<?= $z->id ?>)">Angekreuzte übernehmen</button>
      </td>
    </tr>
<?php
}
?>
  </table>
</div> <!-- linke spalte -->
</div> <!-- kopf -->

<div id="rumpf">
<div class="mittlere spalte">
  <div>
    <b>Modulsuche:</b> <input type="text" id="modulfilter" onchange="module_filtern()" /><button type="button" onclick="module_filtern()">&#x1F50D;</button>
    <input type="checkbox" id="angezeigteauswaehlencb" onchange="angezeigte_auswaehlen()" /> Angezeigte ankreuzen
  </div>
  <div id="bereich_auswahl">
    <div style="display:inline-block;"><input type="radio" name="rb_bereich" id="rb_bereich_alle" checked onchange="bereich_ausgewaehlt(null)">Alle&nbsp;</div>
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

<script>
function formatDauer(ue) {
  return ''+ue+' UE ('+0.75*ue+' h)';
}
function formatPreis(p) {
  let res=p.toFixed(2).replace('.',',')+' €';
  if(p>=1000) {
    let i=p>100000 ? 3 : p>10000 ? 2 : 1;
    res=res.substring(0,i)+' '+res.substring(i);
  }
  return res;
}

var zertifs=<?= json_encode($zertifsById) ?>;
function zertif_text_bearbeiten(zertifid,ueberschrift,spalte) {
  let z=zertifs[zertifid];
  const neu=prompt(ueberschrift,z[spalte]);
  if(neu===null) return;
  let url='intrain_zertif_text_speichern.php?zertif='+zertifid+'&spalte='+spalte+'&neu='+encodeURIComponent(neu);
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if(req.responseText=='OK') {
      zertif_text_gespeichert(zertifid,spalte,neu);
    } else {
      console.log(url);
      console.log(req.responseText);
      alert(req.responseText);
    }
  };
  req.open('GET',url);
  req.send();
}
function zertif_text_gespeichert(zertifid,spalte,neu) {
  zertifs[zertifid][spalte]=neu;
  document.getElementById('zertif_'+zertifid+'_'+spalte).innerText=neu;
  if(spalte=='titel') {
    for(let mid in module) {
      let m=module[mid];
      if(m.zertifid==zertifid && m.zertifdivs) {
        for(let pid in m.zertifdivs) {
          m.zertifdivs[pid].innerText=neu;
        }
      }
    }
  }
}
function zertif_datum_bearbeiten(zertifid,ueberschrift,spalte) {
  let z=zertifs[zertifid];
  const neu=prompt(ueberschrift,document.getElementById('zertif_'+zertifid+'_'+spalte).innerText);
  if(neu===null) return;
  let url='intrain_zertif_datum_speichern.php?zertif='+zertifid+'&spalte='+spalte+'&neu='+encodeURIComponent(neu);
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if(req.responseText.startsWith('OK')) {
      zertif_datum_gespeichert(zertifid,spalte,req.responseText.substring(2));
    } else {
      console.log(url);
      console.log(req.responseText);
      alert(req.responseText);
    }
  };
  req.open('GET',url);
  req.send();
}
function zertif_datum_gespeichert(zertifid,spalte,json) {
  let data=JSON.parse(json);
  for(let k in data) {
    if(k!='anzeige') {
      zertifs[zertifid][k]=data[k];
    }
  }
  document.getElementById('zertif_'+zertifid+'_'+spalte).innerText=data.anzeige;
}

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
      th=document.createElement('th');
      p.tr.appendChild(th);
      for(let mid of p.modulids) {
        let m=module[mid];
        let z=m.zertifid ? zertifs[m.zertifid] : null;
        p.anzahlue+=m.anzahlue;
        p.preis+=m.preis;
        m.gefunden=false;
        if(!m.trs) {
          m.trs={};
          m.cbs={};
          m.zertifdivs={};
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
        td=tr.insertCell();     
        const btn=document.createElement('span');
        btn.innerText='✎';
        btn.classList.add('editbtn');
        btn.onclick=()=>modul_zertif_bearbeiten(m,btn);
        td.appendChild(btn);
        m.zertifdivs[pid]=document.createElement('span');
        m.zertifdivs[pid].innerText=z ? z.titel : '';
        td.appendChild(m.zertifdivs[pid]);
      }
    }
  }
}
init_alle_module();
let editor=null;
let hiddens=[];
function editor_schliessen() {
  if(editor) {
    editor.remove();
    editor=null;
  }
  for(let h of hiddens) {
    h.style.display='';
  }
  hiddens=[];
}
function modul_zertif_bearbeiten(modul,btn) {
  editor_schliessen();
  editor=document.createElement('select');
  for(let zid in zertifs) {
    let o=document.createElement('option');
    o.value=zid;
    o.innerText=zertifs[zid].titel;
    editor.appendChild(o);
  }
  editor.value=modul.zertifid;
  editor.onchange=()=>modul_zertif_speichern(modul);
  editor.onkeyup=(event)=>{
    if(event.code=='Escape') editor_schliessen();
  };
  btn.parentNode.insertBefore(editor,btn);
  for(let i=0;i<btn.parentNode.childNodes.length;++i) {
    if(btn.parentNode.childNodes[i]!=editor) {
      btn.parentNode.childNodes[i].style.display='none';
      hiddens.push(btn.parentNode.childNodes[i]);
    }
  }
}
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
function zertif_ausgewaehlt(zertifid) {
  ausgewaehlterBereich=null;
  document.getElementById('rb_bereich_alle').checked=true;
  for(let mid in module) {
    module[mid].gefunden=module[mid].zertifid==zertifid;
  }
  zeilenFiltern();
}
function zertif_uebernehmen(zertifid) {
  editor_schliessen();
  let modids=[];
  for(let mid in module) {
    if(module[mid].ausgewaehlt) {
      modids.push(mid);
    }
  }
  if(modids.length>0) {
    let url='intrain_modulezertifspeichern.php?zertif='+zertifid+'&modids='+encodeURIComponent(modids.join(','))+'&redirect=J';
    const req=new XMLHttpRequest();
    req.onreadystatechange = function() {
      if (req.readyState != 4) return;
      if(req.responseText=='OK') {
        zertif_uebernommen(zertifid,modids);
      } else {
        console.log(url);
        console.log(req.responseText);
        alert(req.responseText);
      }
    };
    req.open('GET',url);
    req.send();
  }
}
function modul_zertif_speichern(modul) {
  let modids=[modul.id];
  let zertifid=editor.value;
  let url='intrain_modulezertifspeichern.php?zertif='+zertifid+'&modids='+encodeURIComponent(modids.join(','))+'&redirect=J';
  const req=new XMLHttpRequest();
  req.onreadystatechange = function() {
    if (req.readyState != 4) return;
    if(req.responseText=='OK') {
      editor_schliessen();
      zertif_uebernommen(zertifid,modids);
    } else {
      console.log(url);
      console.log(req.responseText);
      alert(req.responseText);
    }
  };
  req.open('GET',url);
  req.send();
}
function zertif_uebernommen(zertifid,modids) {
  let zertif=zertifs[zertifid];
  for(let mid of modids) {
    let m=module[mid];
    m.zertifid=zertifid;
    if(m.zertifdivs) {
      for(let pid in m.zertifdivs) {
        m.zertifdivs[pid].innerText=zertif.titel;
      }
    }
  }
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
  editor_schliessen();
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
  editor_schliessen();
  if(modul.ausgewaehlt) {
    modul.ausgewaehlt=false;
    for(let pid in modul.cbs) {
      modul.cbs[pid].checked=false;
    }
  } else {
    modul.ausgewaehlt=true;
    for(let pid in modul.cbs) {
      modul.cbs[pid].checked=true;
    }
  }
}
function angezeigte_auswaehlen() {
  editor_schliessen();
  let ausgewaehlt=document.getElementById('angezeigteauswaehlencb').checked;
  for(let mid in module) {
    let m=module[mid];
    if(!m.trs) {
      m.ausgewaehlt=false;
      continue;
    }
    let angezeigt=false;
    for(let pid in m.trs) {
      if(m.trs[pid].style.display=='') {
        angezeigt=true;
        break;
      }
    }
    if(m.ausgewaehlt!=(angezeigt ? ausgewaehlt : false)) {
      modul_toggeln(m);
    }
  }
}
function paket_details_anzeigen(bereich,paket) {
  document.getElementById('details_kopf').style.backgroundColor=bereich.farbe;
  document.getElementById('details_kragen').style.backgroundColor=bereich.farbe;
  document.getElementById('details_nummer').innerText='Paket '+paket.kurzbezeichnung;
  document.getElementById('details_titel').innerText=paket.bezeichnung;
  details_anzeigen(paket);
}
function modul_details_anzeigen(bereich,paket,modul) {
  document.getElementById('details_kopf').style.backgroundColor='';
  document.getElementById('details_kragen').style.backgroundColor='';
  document.getElementById('details_nummer').innerText='Modul '+modul.kurzbezeichnung;
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