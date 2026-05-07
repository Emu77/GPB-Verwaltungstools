<?php
require_once 'check_login.php';
require_once 'spez_teilzeit_wochenue.php';

$scroll=isset($_GET['scroll']) ? (int)$_GET['scroll'] : 0;

function formatDate($d) {
  return empty($d) || $d=='0000-00-00' ? '' : date('d.m.Y',strtotime($d));
}
$ausbildungen=array();
$result=$db->query("select * from gpb_spezausbildung");
while($row=$result->fetch_object()) {
  $row->zertif_von=formatDate($row->zertif_beginn);
  $row->zertif_bis=formatDate($row->zertif_ende);
  $row->vollzeit_von=formatDate($row->vollzeit_beginn);
  $row->vollzeit_bis=formatDate($row->vollzeit_ende);
  $row->teilzeit_von=formatDate($row->teilzeit_beginn);
  $row->teilzeit_bis=formatDate($row->teilzeit_ende);
  $row->vollzeit_wochenh=0.75*$row->vollzeit_wochenue;
  $row->modulids=[];
  $row->preis=0.0;
  $row->anzahlue=0;
  $ausbildungen[]=$row;
}
$result->free();
$ausbildungen[]=(object)array(
  'id'=>'0',
  'titel'=>'Unabhängige Module',
  'vollzeit_wochenue'=>'0',
  'vollzeit_massnnr'=>'0',
  'vollzeit_beginn'=>null,
  'vollzeit_ende'=>null,
  'teilzeit_massnnr'=>'',
  'teilzeit_beginn'=>null,
  'teilzeit_ende'=>null,
  'zertif_beginn'=>null,
  'zertif_ende'=>null,
  'zertif_von'=>'',
  'zertif_bis'=>'',
  'vollzeit_von'=>'',
  'vollzeit_bis'=>'',
  'teilzeit_von'=>'',
  'teilzeit_bis'=>'',
  'vollzeit_wochenh'=>0,
  'modulids'=>[],
  'preis'=>0,
  'anzahlue'=>0,
  'mitisid'=>null
);
$module=array();
$verbindungen=array();
$result=$db->query("select * from gpb_spezmodul");
while($row=$result->fetch_object()) {
  $row->zertif_von=formatDate($row->zertif_beginn);
  $row->zertif_bis=formatDate($row->zertif_ende);
  $row->vollzeit_von=formatDate($row->vollzeit_beginn);
  $row->vollzeit_bis=formatDate($row->vollzeit_ende);
  $row->teilzeit_von=formatDate($row->teilzeit_beginn);
  $row->teilzeit_bis=formatDate($row->teilzeit_ende);
  //vollzeit_wochen wird für jede Ausbildung aus $ausbildung->vollzeit_wochenue gerechnet
  $row->teilzeit_minwochen=$row->anzahlue/$teilzeit_maxwochenue;
  $row->teilzeit_maxwochen=$row->anzahlue/$teilzeit_minwochenue;
  $module[]=$row;
  if($row->zertifmit<=0) {
    $verbindungen[]=(object)array('ausbildungid'=>'0','modulid'=>$row->id);
  }
}
$result->free();
$result=$db->query("select * from gpb_spezausbildung_modul");
while($row=$result->fetch_object()) {
  $verbindungen[]=$row;
}
$result->free();

require_once 'BeratungSeite.php';
$seite=new BeratungSeite('Spezialisten - Übersicht Module');
$seite->anfangGenerieren();
?>
<style>
  #rumpf {
    flex-direction:column;
  }
  table {
    background-color:rgb(238,238,238);
  }
  h2 {
    color:black;
    font-size:18px;
    margin-bottom:0;
  }
  .editbtn {
    cursor:pointer;
  }
</style>
<script type="module">
  var editors=[];
  function addEditor(ed) {
    editors.push(ed);
  }
  var hiddens=[];
  function hide(elem) {
    elem.style.display='none';
    hiddens.push(elem);
  }
  function stopEdit() {
    if(hiddens.length>0) {
      for(let elem of hiddens) {
        elem.style.display='';
      }
      hiddens=[];
    }
    if(editors.length>0) {
      for(let ed of editors) {
        ed.remove();
      }
      editors=[];
    }
  }
  function startEdit(ed,...tohide) {
    stopEdit();
    if(tohide.length>0) {
      tohide[0].parentNode.insertBefore(ed,tohide[0]);
      for(let elem of tohide) {
        hide(elem);
      }
    }
    addEditor(ed);
  }
  function formatPreis(p) {
    return (p===null ? '?' : p.toFixed(2).replace('.',','))+' €';
  }
  function formatUE(ue) {
    return (ue===null || ue<=0 ? '?' : Math.ceil(ue))+' UE';
  }
  function formatStunden(h) {
    return (h===null || isNaN(h) ? '?' : h.toFixed(1).replace('.',','))+' h';
  }
  function formatWochen(wo) {
    return wo===null || isNaN(wo) ? '?' : (Math.ceil(wo/0.2)*0.2).toFixed(1).replace('.',',');
  }
  function makeTh(tr,text,align=null) {
    let th=document.createElement('th');
    if(align) {
      th.align=align;
    }
    th.innerText=text;
    tr.appendChild(th);
    return th;
  }
  function makeTd(tr,text,align=null) {
    let td=tr.insertCell();
    if(align) {
      td.style.textAlign=align;
    }
    td.innerText=text;
    return td;
  }
  function makeButton(text,onclick,parentNode=null) {
    let btn=document.createElement('button');
    btn.type='button';
    btn.innerText=text;
    btn.onclick=onclick;
    if(parentNode) {
      parentNode.appendChild(btn);
    }
    return btn;
  }
  function makeEditor(obj,spalte,titel,parentNode) {
    let btn=document.createElement('span');
    btn.innerText='✎';
    btn.classList.add('editbtn');
    btn.onclick=()=>edit(obj,spalte,titel);
    if(parentNode) {
      parentNode.insertBefore(btn,parentNode.childNodes[0]);
    }
  }
  function edit(obj,spalte,titel) {
    let val=prompt(titel,obj[spalte]);
    if(val===null) return;
    obj.speichern(spalte,val);
  }
  const teilzeit_minwochenue=<?= $teilzeit_minwochenue ?>;
  const teilzeit_maxwochenue=<?= $teilzeit_maxwochenue ?>;
  class Ausbildung {
    static alle=[];
    static byId={};
    static initAlle(json) {
      for(let obj of json) {
        new Ausbildung(obj);
      }
    }
    constructor(obj) {
      for(let k in obj) {
        this[k]=obj[k];
        this.module=[];
        this.preis=0.0;
        this.anzahlue=0.0;
        this.vollzeit_wochen=0;
        this.teilzeit_minwochen=0;
        this.teilzeit_maxwochen=0;
        this.tds=[];
      }
      Ausbildung.alle.push(this);
      Ausbildung.byId[this.id]=this;
    }
    addModul(mod) {
      this.module.push(mod);
      this.preis+=mod.preis;
      this.anzahlue+=mod.anzahlue;
      this.vollzeit_wochen=this.anzahlue/this.vollzeit_wochenue;
      this.teilzeit_minwochen=this.anzahlue/teilzeit_maxwochenue;
      this.teilzeit_maxwochen=this.anzahlue/teilzeit_minwochenue;
    }
    makeTrs(table) {
      if(table.rows.length>0) {
        let tr=table.insertRow();
        let th=makeTh(tr,'');
        th.innerHTML='&nbsp;';
        th.style.backgroundColor='rgb(234,183,14)';
        th.style.borderLeft='1px solid rgb(234,183,14)';
        th.style.borderRight='1px solid rgb(234,183,14)';
        th.colSpan=19;
      }
      
      let tr=table.insertRow();
      this.tds.titel=makeTh(tr,this.titel,'left');
      this.tds.titel.colSpan=4;
      if(this.id>0) makeEditor(this,'titel','Neuer Titel',this.tds.titel);
      let th=makeTh(tr,' ');
      th.style.width='4ch';
      let a=document.createElement('a');
      a.name='ausbildung_'+this.id;
      th.appendChild(a);
      makeTh(tr,'Vollzeit').colSpan=5;
      makeTh(tr,' ').style.width='4ch';
      makeTh(tr,'Teilzeit').colSpan=4;
      makeTh(tr,' ').style.width='4ch';
      makeTh(tr,'Zertif. von');
      makeTh(tr,'Zertif. bis');
      makeTh(tr,'MITIS-ID');
      
      tr=table.insertRow();
      this.kopftr=tr;
      makeTh(tr,'');
      makeTh(tr,'Preis');
      makeTh(tr,'Preis/UE');
      makeTh(tr,'UE');
      makeTh(tr,' ').style.width='4ch';
      makeTh(tr,'MaßnahmeNr.\nVollzeit');
      if(this.id>0) {
        this.tds.vollzeit_wochenue=makeTh(tr,formatUE(this.vollzeit_wochenue)+'/Wo');
        makeEditor(this,'vollzeit_wochenue','UE/Wo Vollzeit',this.tds.vollzeit_wochenue);
      } else {
        makeTh(tr,'');
      }
      makeTh(tr,'Zeit/Tag');
      makeTh(tr,'Maßnahmedauer für BG').colSpan=2;
      makeTh(tr,' ').style.width='4ch';
      makeTh(tr,'MaßnahmeNr.\nTeilzeit');
      makeTh(tr,''+teilzeit_minwochenue+' bis '+teilzeit_maxwochenue+' UE/Wo');
      makeTh(tr,'Maßnahmedauer für BG').colSpan=2;
      makeTh(tr,' ').style.width='4ch';
      this.tds.zertif_von=makeTh(tr,this.zertif_von);
      if(this.id>0) makeEditor(this,'zertif_von','Zertif. von',this.tds.zertif_von);
      this.tds.zertif_bis=makeTh(tr,this.zertif_bis);
      if(this.id>0) makeEditor(this,'zertif_bis','Zertif. bis',this.tds.zertif_bis);
      this.tds.mitisid=makeTh(tr,this.mitisid,'left');
      if(this.id>0) makeEditor(this,'mitisid','Neue MITIS-ID',this.tds.mitisid);
      
      for(let mod of this.module) {
        mod.makeTr(table,this);
      }
      
      tr=table.insertRow();
      this.fusstr=tr;
      th=makeTh(tr,'','center');
      if(this.id>0) this.modulHinzufuegenBtn=makeButton('Modul hinzufügen',()=>this.modulHinzufuegenVorbereiten(),th);
      this.tds.preis=makeTh(tr,formatPreis(this.preis));
      makeTh(tr,' ');
      this.tds.anzahlue=makeTh(tr,formatUE(this.anzahlue));
      makeTh(tr,' ').style.width='4ch';
      this.tds.vollzeit_massnnr=makeTh(tr,this.vollzeit_massnnr);
      if(this.id>0) makeEditor(this,'vollzeit_massnnr','Maßnahmenummer ganze Ausbildung Vollzeit',this.tds.vollzeit_massnnr);
      if(this.id>0) {
        this.tds.vollzeit_wochen=makeTh(tr,formatWochen(this.vollzeit_wochen)+' Wo');
      } else {
        makeTh(tr,'');
      }
      this.tds.vollzeit_wochenh=makeTh(tr,formatStunden(this.vollzeit_wochenh)+'Wo');
      this.tds.vollzeit_von=makeTd(tr,this.vollzeit_von);
      if(this.id>0) makeEditor(this,'vollzeit_von','Maßnahmedauer ganze Ausbildung Vollzeit von',this.tds.vollzeit_von);
      this.tds.vollzeit_bis=makeTd(tr,this.vollzeit_bis);
      if(this.id>0) makeEditor(this,'vollzeit_bis','Maßnahmedauer ganze Ausbildung Vollzeit bis',this.tds.vollzeit_bis);
      makeTh(tr,' ').style.width='4ch';
      this.tds.teilzeit_massnnr=makeTh(tr,this.teilzeit_massnnr);
      if(this.id>0) makeEditor(this,'teilzeit_massnnr','Maßnahmenummer ganze Ausbildung Teilzeit',this.tds.teilzeit_massnnr);
      this.tds.teilzeit_wochen=makeTd(tr,formatWochen(this.teilzeit_minwochen)+' bis '+formatWochen(this.teilzeit_maxwochen)+' Wo');
      this.tds.teilzeit_von=makeTd(tr,this.teilzeit_von);
      if(this.id>0) makeEditor(this,'teilzeit_von','Maßnahmedauer ganze Ausbildung Teilzeit von',this.tds.teilzeit_von);
      this.tds.teilzeit_bis=makeTd(tr,this.teilzeit_bis);
      if(this.id>0) makeEditor(this,'teilzeit_bis','Maßnahmedauer ganze Ausbildung Teilzeit bis',this.tds.teilzeit_bis);
      makeTh(tr,' ').style.width='4ch';
      makeTh(tr,' ');
      makeTh(tr,' ');
      th=makeTh(tr,'','left');
    }
    modulHinzufuegenVorbereiten() {
      if(this.id<=0) return;
      let div=document.createElement('div');
      const sel=document.createElement('select');
      for(let mod of Modul.alle) {
          if(this.module.indexOf(mod)>=0) continue;
          let opt=document.createElement('option');
          opt.value=mod.id;
          opt.innerText=mod.titel;
          sel.appendChild(opt);
      }
      div.appendChild(sel);
      makeButton('Hinzufügen',()=>this.modulHinzufuegen(sel.value),div);
      makeButton('Abbrechen',stopEdit,div);
      startEdit(div,this.modulHinzufuegenBtn);
    }
    modulHinzufuegen(modulid) {
      location.href='spez_modul_hinzufuegen.php?ausbildungid='+this.id+'&modulid='+modulid;
    }
    speichern(spalte,neu) {
      location.href='spez_daten_speichern.php?scroll='+window.scrollY+'&tabelle=gpb_spezausbildung&spalte='+spalte+'&id='+this.id+'&neu='+encodeURIComponent(neu);
    }
  }
  Ausbildung.initAlle(<?= json_encode($ausbildungen) ?>);
  class Modul {
    static alle=[];
    static byId={};
    static initAlle(json) {
      for(let obj of json) {
        new Modul(obj);
      }
      this.teilvon=Ausbildung.byId[this.zertifmit];
    }
    constructor(obj) {
      for(let k in obj) {
        this[k]=obj[k];
      }
      this.preis=this.preis===null || this.preis=='' ? null : parseFloat(this.preis);
      this.anzahlue=this.anzahlue===null || this.anzahlue=='' ? null : parseInt(this.anzahlue);
      this.ausbildungen=[];
      this.calcs={};
      Modul.alle.push(this);
      Modul.byId[this.id]=this;
    }
    addAusbildung(aus) {
      this.ausbildungen.push(aus);
      let wo=this.anzahlue/(aus.id>0 ? aus.vollzeit_wochenue : this.vollzeit_wochenue);
      this.calcs[aus.id]={
        'vollzeit_wochen':wo,
        'vollzeit_tague':this.anzahlue/(5*wo),
        'vollzeit_tagh':0.75*this.anzahlue/(5*wo),
        'vollzeit_wochenue':this.anzahlue/wo,
        'vollzeit_wochenh':0.75*this.anzahlue/wo,
        'tds':{}
      };
    }
    makeTr(table,aus) {
      let calc=this.calcs[aus.id];
      let tr=table.insertRow();
      calc.tr=tr;
      calc.tds.titel=makeTd(tr,this.titel,'right');
      calc.tds.preis=makeTd(tr,formatPreis(this.preis));
      calc.tds.uepreis=makeTd(tr,formatPreis(this.preis/this.anzahlue)+'UE');
      calc.tds.anzahlue=makeTd(tr,formatUE(this.anzahlue));
      makeTd(tr,' ').style.width='4ch';
      calc.tds.vollzeit_massnnr=makeTd(tr,this.vollzeit_massnnr);
      calc.tds.vollzeit_wochen=makeTd(tr,(this.zertifmit==0 && aus.id==0 ? formatUE(this.vollzeit_wochenue)+'\n' : '')+formatWochen(calc.vollzeit_wochen)+' Wo');
      calc.tds.vollzeit_tague=makeTd(tr,formatUE(calc.vollzeit_tague)+'/Tag ('+formatStunden(calc.vollzeit_wochenh)+'/Wo)');
      calc.tds.vollzeit_tague.title=formatStunden(calc.vollzeit_tagh)+'/Tag ('+formatUE(calc.vollzeit_wochenue)+'/Wo)';
      calc.tds.vollzeit_von=makeTd(tr,this.vollzeit_von);
      calc.tds.vollzeit_bis=makeTd(tr,this.vollzeit_bis);
      makeTd(tr,' ').style.width='4ch';
      calc.tds.teilzeit_massnnr=makeTd(tr,this.teilzeit_massnnr);
      calc.tds.teilzeit_wochen=makeTd(tr,formatWochen(this.teilzeit_minwochen)+' bis '+formatWochen(this.teilzeit_maxwochen)+' Wo');
      calc.tds.teilzeit_von=makeTd(tr,this.teilzeit_von);
      calc.tds.teilzeit_bis=makeTd(tr,this.teilzeit_bis);
      makeTd(tr,' ').style.width='4ch';
      calc.tds.zertif_von=makeTd(tr,this.zertif_von);
      calc.tds.zertif_bis=makeTd(tr,this.zertif_bis);
      calc.tds.mitisid=makeTd(tr,this.mitisid,'left');

      makeEditor(this,'titel','Titel',calc.tds.titel);
      makeEditor(this,'preis','Preis',calc.tds.preis);
      makeEditor(this,'anzahlue','Modul-Dauer (UE)',calc.tds.anzahlue);
      makeEditor(this,'vollzeit_massnnr','Maßnahmenummer Modul Vollzeit',calc.tds.vollzeit_massnnr);
      if(this.zertifmit==0 && aus.id==0 ) {
        makeEditor(this,'vollzeit_wochenue','UE pro Wochen Vollzeit',calc.tds.vollzeit_wochen);
      }
      makeEditor(this,'vollzeit_von','Maßnahmedauer Modul Vollzeit von',calc.tds.vollzeit_von);
      makeEditor(this,'vollzeit_bis','Maßnahmedauer Modul Vollzeit bis',calc.tds.vollzeit_bis);
      makeEditor(this,'teilzeit_massnnr','Maßnahmenummer Modul Teilzeit',calc.tds.teilzeit_massnnr);
      makeEditor(this,'teilzeit_von','Maßnahmedauer Modul Teilzeit von',calc.tds.teilzeit_von);
      makeEditor(this,'teilzeit_bis','Maßnahmedauer Modul Teilzeit bis',calc.tds.teilzeit_bis);
      makeEditor(this,'zertif_von','Zertif. von',calc.tds.zertif_von);
      makeEditor(this,'zertif_bis','Zertif. bis',calc.tds.zertif_bis);
      makeEditor(this,'mitisid','MITIS-ID',calc.tds.mitisid);
      if(aus.id==this.zertifmit) {
        makeButton('Löschen',()=>this.loeschen(),calc.tds.titel);
      } else {
        makeButton('Entfernen',()=>this.entfernen(aus),calc.tds.titel);
      }
    }
    loeschen() {
      if(confirm(this.titel+' aus allen Ausbildungen löschen, sicher?')) {
        location.href='spez_modul_loeschen.php?modulid='+this.id+'&ausbildungid='+this.zertifmit;
      }
    }
    entfernen(aus) {
      if(confirm(this.titel+' aus '+aus.titel+' entfernen, sicher?')) {
        location.href='spez_modul_entfernen.php?modulid='+this.id+'&ausbildungid='+aus.id;
      }
    }
    speichern(spalte,neu) {
      location.href='spez_daten_speichern.php?scroll='+window.scrollY+'&tabelle=gpb_spezmodul&spalte='+spalte+'&id='+this.id+'&neu='+encodeURIComponent(neu);
    }
  }
  Modul.initAlle(<?= json_encode($module) ?>);
  for(let am of <?= json_encode($verbindungen) ?>) {
    let aus=Ausbildung.byId[am.ausbildungid];
    let mod=Modul.byId[am.modulid];
    aus.addModul(mod);
    mod.addAusbildung(aus);
  }
  const table=document.getElementById('ausbildungen_table');
  for(let aus of Ausbildung.alle) {
    aus.makeTrs(table);
  }
<?php
if(!empty($scroll)) {
?>
  window.scroll({'top':<?= $scroll ?>});
<?php
}
?>
</script>
<div id="rumpf">
  <table id="ausbildungen_table" border="1" cellspacing="0" style="border-collapse:collapse;">
  </table>
  
  <div id="neue_ausbildung_div">
  <h2>Neue Ausbildung</h2>
    <form action="spez_ausbildung_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <table border="1" cellspacing="0" style="border-collapse:collapse;">
      <tr>
        <th>Titel</th>
        <td><input type="text" name="titel" value="" style="width:300px;" /></td>
      </tr>
      <tr>
        <th>UE pro Woche Vollzeit</th>
        <td><input type="number" name="vollzeit_wochenue" value="38" style="width:50px;" /></td>
      </tr>
      <tr>
        <th>Vollzeit-MaßnahmeNr.</th>
        <td><input type="text" name="vollzeit_massnnr" value="" style="width:150px;" /></td>
      </tr>
      <tr>
        <th>Vollzeit Zeitraum für BG</th>
        <td><input type="date" name="vollzeit_beginn" value="" /> - <input type="date" name="vollzeit_ende" value="" /></td>
      </tr>
      <tr>
        <th>Teilzeit-MaßnahmeNr.</th>
        <td><input type="text" name="teilzeit_massnnr" value="" style="width:150px;" /></td>
      </tr>
      <tr>
        <th>Teilzeit Zeitraum für BG</th>
        <td><input type="date" name="teilzeit_beginn" value="" /> - <input type="date" name="teilzeit_ende" value="" /></td>
      </tr>
      <tr>
        <th>Zertif. von</th>
        <td><input type="date" name="zertif_beginn" value="" /></td>
      </tr>
      <tr>
        <th>Zertif. bis</th>
        <td><input type="date" name="zertif_ende" value="" /></td>
      </tr>
      <tr>
        <th>&nbsp;</th>
        <td><input type="submit" value="Hinzufügen" /></td>
      </tr>
    </table>
    </form>
  </div>
  
  <div id="neues_modul_div">
    <h2>Neues Modul</h2>
    <form action="spez_modul_speichern.php" method="post" enctype="multipart/form-data" accept-charset="UTF-8">
    <table border="1" cellspacing="0" style="border-collapse:collapse;">
      <tr>
        <th>Titel</th>
        <td><input type="text" name="titel" value="" style="width:300px;" /></td>
      </tr>
      <tr>
        <th>Modul-Preis</th>
        <td><input type="number" name="preis" value="" style="width:75px;" step="0.01" /> €</td>
      </tr>
      <tr>
        <th>Dauer (UE)</th>
        <td><input type="number" name="anzahlue" value="" style="width:50px;" /> UE</td>
      </tr>
      <tr>
        <th>Vollzeit-MaßnahmeNr.</th>
        <td><input type="text" name="vollzeit_massnnr" value="" style="width:150px;" /></td>
      </tr>
      <tr>
        <th>Vollzeit Zeitraum für BG</th>
        <td><input type="date" name="vollzeit_beginn" value="" /> - <input type="date" name="vollzeit_ende" value="" /></td>
      </tr>
      <tr>
        <th>Teilzeit-MaßnahmeNr.</th>
        <td><input type="text" name="teilzeit_massnnr" value="" style="width:150px;" /></td>
      </tr>
      <tr>
        <th>Teilzeit Zeitraum für BG</th>
        <td><input type="date" name="teilzeit_beginn" value="" /> - <input type="date" name="teilzeit_ende" value="" /></td>
      </tr>
      <tr>
        <th>Zertifiziert im Rahmen von<br />
          oder Zertif. Zeitraum und UE pro Woche</th>
        <td>
          <select name="zertifmit">
<?php
foreach($ausbildungen as $aus) {
?>
            <option value="<?= $aus->id ?>"><?= $aus->titel ?></option>
<?php
}
?>
          </select><br />
          von <input type="date" name="zertif_beginn" value="" /> bis <input type="date" name="zertif_ende" value="" />
          <input type="number" name="vollzeit_wochenue" value="38" style="width:50px;" /> UE/Wo
        </td>
      </tr>
      <tr>
        <th valign="top">Teil von</th>
        <td>
<?php
foreach($ausbildungen as $aus) {
  if($aus->id<=0) continue;
?>
          <input type="checkbox" name="ausbildungids[]" value="<?= $aus->id ?>" />&nbsp;<?= $aus->titel ?><br />
<?php
}
?>
        </td>
      </tr>
      
      <tr>
        <th>&nbsp;</th>
        <td><input type="submit" value="Speichern" /></td>
      </tr>
    </table>
    </form>
  </div>
</div>
</body>
</html>