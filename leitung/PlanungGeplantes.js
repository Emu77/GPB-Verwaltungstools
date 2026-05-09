import { PlanungObject } from './PlanungObject.js';
import { PlanungModul } from './PlanungModul.js';
import { PlanungKlasse } from './PlanungKlasse.js';
import { PlanungDozent } from './PlanungDozent.js'
import { PlanungRaum } from './PlanungRaum.js'


export class PlanungGeplantes {
  static editors=[];
  static addEditor(ed) {
    PlanungGeplantes.editors.push(ed);
  }
  static hiddens=[];
  static hide(elem) {
    elem.style.display='none';
    PlanungGeplantes.hiddens.push(elem);
  }
  static openEditor(ed,...toHide) {
    PlanungGeplantes.addEditor(ed);
    if(toHide && toHide.length>0) {
      toHide[0].parentNode.insertBefore(ed,toHide[0]);
      for(let elem of toHide) {
        if(elem) {
          PlanungGeplantes.hide(elem);
        }
      }
    }
  }
  static stopEdit() {
    if(PlanungGeplantes.editors.length>0) {
      for(let ed of PlanungGeplantes.editors) {
        ed.remove();
      }
      PlanungGeplantes.editors=[];
    }
    if(PlanungGeplantes.hiddens.length>0) {
      for(let elem of PlanungGeplantes.hiddens) {
        elem.style.display='';
      }
      PlanungGeplantes.hiddens=[];
    }
  }
  
  static createButton(callback,parentNode,innerText) {
    let btn=document.createElement('button');
    btn.type='button';
    btn.innerText=innerText;
    btn.title='';
    btn.onclick=callback;
    if(parentNode) {
      parentNode.appendChild(btn);
    }
    return btn;
  }
  static createEditButton(callback,parentNode,title=null,innerText='✎') {
    let btn=document.createElement('span');
    btn.classList.add('btn');
    btn.innerText=innerText;
    if(title) {
      btn.title=title;
    }
    btn.onclick=callback;
    parentNode.appendChild(btn);
    return btn;
  }
  static createMenuItem(callback,parentNode,innerText,title=null) {
    let btn=document.createElement('div');
    btn.classList.add('menuitem');
    btn.innerText=innerText;
    if(title) {
      btn.title=title;
    }
    btn.onclick=callback;
    parentNode.appendChild(btn);
    return btn;
  }
  
  constructor(zeile,kw,kurs,td) {
    this.zeile=zeile;
    this.kw=kw;
    this.kurs=kurs;
    this.div=document.createElement('div');
    this.div.classList.add('geplantes');
    if(kurs.moodleid>0) {
      this.div.classList.add('moodle');
    } else if(kurs.sichtbar) {
      this.div.classList.add('sichtbar');
    }
    if(this.kw!=this.kurs.kw[0]) {
      this.div.style.marginLeft='-1px';
      this.div.style.borderLeft='1px dashed white';
    }
    if(this.kw!=this.kurs.kw[this.kurs.kw.length-1]) {
      this.div.style.marginRight='-1px';
      this.div.style.borderRight='1px dashed white';
    }
    
    this.modulDiv=document.createElement('div');
    this.modulBearbeitenBtn=kurs.moodleid>0 ? null : PlanungGeplantes.createEditButton(()=>this.modulBearbeiten(),this.modulDiv,'Modul auswählen');
    this.modulSpan=document.createElement('div');
    this.modulSpan.classList.add('modul');
    if(kurs.modulid>0) {
      let m=PlanungModul.byId[kurs.modulid];
      if(!m) {
        m=PlanungObject.ladeEines(PlanungModul,{'id':kurs.modulid,'kuerzel':kurs.modulkuerzel,'titel':kurs.modultitel,'dauer':kurs.moduldauer});
      }
      this.modulSpan.appendChild(m.makeGeplantesDiv());
    }
    this.modulDiv.appendChild(this.modulSpan);
    this.div.appendChild(this.modulDiv);
    
    this.klassenDiv=document.createElement('div');
    this.klassenBearbeitenBtn=kurs.moodleid>0 ? null : PlanungGeplantes.createEditButton(()=>this.klassenBearbeiten(),this.klassenDiv,'Klassen bearbeiten');
    this.klassenSpan=document.createElement('div');
    this.klassenSpan.classList.add('klassen');
    for(let obj of kurs.klassen) {
      let k=PlanungObject.ladeEines(PlanungKlasse,obj);
      this.klassenSpan.appendChild(k.makeGeplantesDiv());
    }
    this.klassenDiv.appendChild(this.klassenSpan);
    this.div.appendChild(this.klassenDiv);
    
    this.dozentenDiv=document.createElement('div');
    this.dozentenBearbeitenBtn=kurs.moodleid>0 ? null : PlanungGeplantes.createEditButton(()=>this.dozentenBearbeiten(),this.dozentenDiv,'Dozenten bearbeiten');
    this.dozentenSpan=document.createElement('div');
    this.dozentenSpan.classList.add('dozenten');
    for(let obj of kurs.dozenten) {
      let d=PlanungObject.ladeEines(PlanungDozent,obj);
      this.dozentenSpan.appendChild(d.makeGeplantesDiv());
    }
    this.dozentenDiv.appendChild(this.dozentenSpan);
    this.div.appendChild(this.dozentenDiv);
    
    this.raumDiv=document.createElement('div');
    this.raumDiv.classList.add('raum');
    this.raumBearbeitenBtn=PlanungGeplantes.createEditButton(()=>this.raumBearbeiten(),this.raumDiv,'Raum auswählen');
    this.raumSpan=document.createElement('div');
    this.raumSpan.classList.add('raum');
    if(kurs.raumid>0) {
      let r=PlanungRaum.byId[kurs.raumid];
      if(!r) {
        r=PlanungObject.ladeEines(PlanungRaum,{'id':kurs.raumid,'tuer':kurs.raum,'anzahlPlaetze':kurs.anzahlPlaetze});
      }
      this.raumSpan.appendChild(r.makeGeplantesDiv());
    }
    this.raumDiv.appendChild(this.raumSpan);
    this.div.appendChild(this.raumDiv);
    
    this.notizDiv=document.createElement('div');
    this.notizDiv.classList.add('notiz');
    if(this.kurs.planungfarbe) {
      this.notizDiv.style.backgroundColor=this.kurs.planungfarbe;
    }
    this.notizBearbeitenBtn=PlanungGeplantes.createEditButton(()=>this.notizBearbeiten(),this.notizDiv,'Notiz bearbeiten');
    this.notizBearbeitenBtn.style.flexGrow=0;
    this.notizSpan=document.createElement('span');
    this.notizSpan.innerText=this.kurs.planungnotiz;
    this.notizSpan.style.flexGrow=1;
    this.notizDiv.appendChild(this.notizSpan);
    this.menueOeffnenBtn=PlanungGeplantes.createEditButton(()=>this.menueOeffnen(),this.notizDiv,'Infos und Aktionen','☰');
    this.menueOeffnenBtn.style.padding='2px';
    this.menueOeffnenBtn.style.paddingTop='0';
    this.menueOeffnenBtn.style.backgroundColor='white';
    this.menueOeffnenBtn.style.flexGrow=0;
    this.div.appendChild(this.notizDiv);
    
    td.appendChild(this.div);
  }
  entfernen() {
    this.div.remove();
  }
  
  async modulBearbeiten() {
    PlanungGeplantes.stopEdit();
    let ed=document.createElement('div');
    ed.style.display='flex';
    ed.style.flexDirection='column';
    
    const sucheInput=document.createElement('input');
    sucheInput.type='text';
    const gefundeneModuleSelect=document.createElement('select');
    gefundeneModuleSelect.multiple=true;
    gefundeneModuleSelect.style.height='10em';
    let o=document.createElement('option');
    o.value=0;
    o.innerText='(keines)';
    gefundeneModuleSelect.appendChild(o);
    if(this.kurs.modulid) {
      PlanungModul.byId[this.kurs.modulid].makeOption(gefundeneModuleSelect);
    }
    
    sucheInput.onkeyup=(event)=>{
      if(event.key=='Enter') {
        this.moduleFinden(sucheInput.value,gefundeneModuleSelect);
      } else if(event.key=='Escape') {
        PlanungGeplantes.stopEdit();
      }
    }
    sucheInput.onchange=()=>this.moduleFinden(sucheInput.value,gefundeneModuleSelect);
    ed.appendChild(sucheInput);
    gefundeneModuleSelect.ondblclick=()=>this.modulAusgewaehlt(gefundeneModuleSelect);
    ed.appendChild(gefundeneModuleSelect);
    PlanungGeplantes.createButton(()=>this.modulAusgewaehlt(gefundeneModuleSelect),ed,'Markiertes übernehmen');
    PlanungGeplantes.createButton(()=>PlanungGeplantes.stopEdit(),ed,'Abbrechen');
    
    PlanungGeplantes.openEditor(ed,this.modulBearbeitenBtn,this.modulSpan);
    sucheInput.focus();
    this.moduleFinden('',gefundeneModuleSelect);
  }
  async moduleFinden(suchtext,gefundeneModuleSelect) {
    for(let i=gefundeneModuleSelect.options.length-1;i>=(this.kurs.modulid ? 2 : 1);--i){ //0 ist "kein Modul", 1 ist das aktuelle Modul vom Kurs (falls vorhanden)
      gefundeneModuleSelect.options[i].remove();
    }
    let module=await PlanungModul.finde(suchtext,this.kurs);
    for(let m of module) {
      m.makeOption(gefundeneModuleSelect);
    }
  }
  async modulAusgewaehlt(gefundeneModuleSelect) {
    if(gefundeneModuleSelect.selectedOptions.length!=1) return;
    PlanungGeplantes.stopEdit();
    this.kurs.setModul(parseInt(gefundeneModuleSelect.value));
  }
  modulGeaendert(neuesModul) {
    for(let i=this.modulSpan.childNodes.length-1;i>=0;--i){
      this.modulSpan.childNodes[i].remove();
    }
    if(neuesModul) {
      this.modulSpan.appendChild(neuesModul.makeGeplantesDiv());
    }
  }
  
  klassenBearbeiten() {
    PlanungGeplantes.stopEdit();
    let ed=document.createElement('div');
    ed.style.display='flex';
    ed.style.flexDirection='row';
    
    const kursKlassenSelect=document.createElement('select');
    kursKlassenSelect.multiple=true;
    for(let obj of this.kurs.klassen) {
      let k=PlanungObject.ladeEines(PlanungKlasse,obj);
      k.makeOption(kursKlassenSelect);
    }
    const sucheInput=document.createElement('input');
    sucheInput.type='text';
    const gefundeneKlassenSelect=document.createElement('select');
    gefundeneKlassenSelect.multiple=true;
    
    let div=document.createElement('div');
    div.style.display='flex';
    div.style.flexDirection='column';
    div.style.justifyContent='center';
    let span=document.createElement('span');
    span.innerText='Klassen';
    div.appendChild(span);
    kursKlassenSelect.ondblclick=()=>this.removeKlasse(kursKlassenSelect,gefundeneKlassenSelect);
    div.appendChild(kursKlassenSelect);
    PlanungGeplantes.createButton(()=>this.removeKlasse(kursKlassenSelect,gefundeneKlassenSelect),div,'Markierte entfernen');
    ed.appendChild(div);
    
    div=document.createElement('div');
    div.style.display='flex';
    div.style.flexDirection='column';
    div.style.justifyContent='center';
    PlanungGeplantes.createButton(()=>this.klassenSpeichern(kursKlassenSelect),div,'Speichern');
    PlanungGeplantes.createButton(()=>PlanungGeplantes.stopEdit(),div,'Abbrechen');
    ed.appendChild(div);
    
    div=document.createElement('div');
    div.style.display='flex';
    div.style.flexDirection='column';
    div.style.justifyContent='center';
    sucheInput.onkeyup=(event)=>{
      if(event.key=='Enter') {
        this.klassenFinden(kursKlassenSelect,sucheInput.value,gefundeneKlassenSelect);
      }
    }
    sucheInput.onchange=()=>this.klassenFinden(kursKlassenSelect,sucheInput.value,gefundeneKlassenSelect);
    div.appendChild(sucheInput);
    gefundeneKlassenSelect.ondblclick=()=>this.addKlasse(kursKlassenSelect,gefundeneKlassenSelect);
    div.appendChild(gefundeneKlassenSelect);
    PlanungGeplantes.createButton(()=>this.addKlasse(kursKlassenSelect,gefundeneKlassenSelect),div,'Markierte hinzufügen');
    ed.appendChild(div);
    
    PlanungGeplantes.openEditor(ed,this.klassenBearbeitenBtn,this.klassenSpan);
    this.klassenFinden(kursKlassenSelect,sucheInput.value,gefundeneKlassenSelect);
  }
  removeKlasse(kursKlassenSelect,gefundeneKlassenSelect) {
    for(let i=kursKlassenSelect.selectedOptions.length-1;i>=0;--i){
      let o=kursKlassenSelect.selectedOptions[i];
      o.remove();
      gefundeneKlassenSelect.insertBefore(o,gefundeneKlassenSelect.options[0]);
    }
  }
  async klassenFinden(kursKlassenSelect,suchtext,gefundeneKlassenSelect) {
    for(let i=gefundeneKlassenSelect.options.length-1;i>=0;--i){
      gefundeneKlassenSelect.options[i].remove();
    }
    for(let k of PlanungKlasse.inKonfig) {
      let ok=true;
      for(let i=kursKlassenSelect.options.length-1;i>=0;--i){
        if(kursKlassenSelect.options[i].value==k.id) {
          ok=false;
          break;
        }
      }
      if(ok) {
        k.makeOption(gefundeneKlassenSelect);
      }
    }
    if(suchtext) {
      let klassen=await PlanungKlasse.finde(suchtext);
      for(let k of klassen) {
        if(!k.istInKonfig) {
          let ok=true;
          for(let i=kursKlassenSelect.options.length-1;i>=0;--i){
            if(kursKlassenSelect.options[i].value==k.id) {
              ok=false;
              break;
            }
          }
          if(ok) {
            k.makeOption(gefundeneKlassenSelect);
          }
        }
      }
    }
  }
  addKlasse(kursKlassenSelect,gefundeneKlassenSelect) {
    for(let i=gefundeneKlassenSelect.selectedOptions.length-1;i>=0;--i) {
      let o=gefundeneKlassenSelect.selectedOptions[i];
      o.remove();
      kursKlassenSelect.appendChild(o);
    }
  }
  async klassenSpeichern(kursKlassenSelect) {
    PlanungGeplantes.stopEdit();
    let klassenids=[];
    for(let i=kursKlassenSelect.options.length-1;i>=0;--i){
      klassenids.push(kursKlassenSelect.options[i].value);
    }
    this.kurs.setKlassenids(klassenids);
  }
  klassenGeaendert() {
    for(let i=this.klassenSpan.childNodes.length-1;i>=0;--i){
      this.klassenSpan.childNodes[i].remove();
    }
    for(let obj of this.kurs.klassen) {
      let k=PlanungObject.ladeEines(PlanungKlasse,obj);
      this.klassenSpan.appendChild(k.makeGeplantesDiv());
    }
  }
  
  dozentenBearbeiten() {
    PlanungGeplantes.stopEdit();
    let ed=document.createElement('div');
    ed.style.display='flex';
    ed.style.flexDirection='row';
    
    const kursDozentenSelect=document.createElement('select');
    kursDozentenSelect.multiple=true;
    for(let obj of this.kurs.dozenten) {
      let k=PlanungObject.ladeEines(PlanungDozent,obj);
      k.makeOption(kursDozentenSelect);
    }
    const sucheInput=document.createElement('input');
    sucheInput.type='text';
    const gefundeneDozentenSelect=document.createElement('select');
    gefundeneDozentenSelect.multiple=true;
    
    let div=document.createElement('div');
    div.style.display='flex';
    div.style.flexDirection='column';
    div.style.justifyContent='center';
    let span=document.createElement('span');
    span.innerText='Dozenten';
    div.appendChild(span);
    kursDozentenSelect.ondblclick=()=>this.removeDozent(kursDozentenSelect,gefundeneDozentenSelect);
    div.appendChild(kursDozentenSelect);
    PlanungGeplantes.createButton(()=>this.removeDozent(kursDozentenSelect,gefundeneDozentenSelect),div,'Markierte entfernen');
    ed.appendChild(div);
    
    div=document.createElement('div');
    div.style.display='flex';
    div.style.flexDirection='column';
    div.style.justifyContent='center';
    PlanungGeplantes.createButton(()=>this.dozentenSpeichern(kursDozentenSelect),div,'Speichern');
    PlanungGeplantes.createButton(()=>PlanungGeplantes.stopEdit(),div,'Abbrechen');
    ed.appendChild(div);
    
    div=document.createElement('div');
    div.style.display='flex';
    div.style.flexDirection='column';
    div.style.justifyContent='center';
    sucheInput.onkeyup=(event)=>{
      if(event.key=='Enter') {
        this.dozentenFinden(kursDozentenSelect,sucheInput.value,gefundeneDozentenSelect);
      }
    }
    sucheInput.onchange=()=>this.dozentenFinden(kursDozentenSelect,sucheInput.value,gefundeneDozentenSelect);
    div.appendChild(sucheInput);
    gefundeneDozentenSelect.ondblclick=()=>this.addDozent(kursDozentenSelect,gefundeneDozentenSelect);
    div.appendChild(gefundeneDozentenSelect);
    PlanungGeplantes.createButton(()=>this.addDozent(kursDozentenSelect,gefundeneDozentenSelect),div,'Markierte hinzufügen');
    ed.appendChild(div);
    
    PlanungGeplantes.openEditor(ed,this.dozentenBearbeitenBtn,this.dozentenSpan);
    this.dozentenFinden(kursDozentenSelect,sucheInput.value,gefundeneDozentenSelect);
  }
  removeDozent(kursDozentenSelect,gefundeneDozentenSelect) {
    for(let i=kursDozentenSelect.selectedOptions.length-1;i>=0;--i) {
      let o=kursDozentenSelect.selectedOptions[i];
      o.remove();
      gefundeneDozentenSelect.insertBefore(o,gefundeneDozentenSelect.options[0]);
    }
  }
  async dozentenFinden(kursDozentenSelect,suchtext,gefundeneDozentenSelect) {
    for(let i=gefundeneDozentenSelect.options.length-1;i>=0;--i){
      gefundeneDozentenSelect.options[i].remove();
    }
    let dozenten=await PlanungDozent.finde(suchtext,this.kurs.modulid);
    for(let k of dozenten) {
      let ok=true;
      for(let i=kursDozentenSelect.options.length-1;i>=0;--i){
        if(kursDozentenSelect.options[i].value==k.id) {
          ok=false;
          break;
        }
      }
      if(ok) {
        k.makeOption(gefundeneDozentenSelect);
      }
    }
  }
  addDozent(kursDozentenSelect,gefundeneDozentenSelect) {
    for(let i=gefundeneDozentenSelect.selectedOptions.length-1;i>=0;--i) {
      let o=gefundeneDozentenSelect.selectedOptions[i];
      o.remove();
      kursDozentenSelect.appendChild(o);
    }
  }
  async dozentenSpeichern(kursDozentenSelect) {
    PlanungGeplantes.stopEdit();
    let dozentenids=[];
    for(let i=kursDozentenSelect.options.length-1;i>=0;--i){
      dozentenids.push(kursDozentenSelect.options[i].value);
    }
    this.kurs.setDozentenids(dozentenids);
  }
  dozentenGeaendert() {
    for(let i=this.dozentenSpan.childNodes.length-1;i>=0;--i){
      this.dozentenSpan.childNodes[i].remove();
    }
    for(let obj of this.kurs.dozenten) {
      let k=PlanungObject.ladeEines(PlanungDozent,obj);
      this.dozentenSpan.appendChild(k.makeGeplantesDiv());
    }
  }
  
  async raumBearbeiten() {
    await PlanungRaum.findeAlle(this.kurs.ort);
    PlanungGeplantes.stopEdit();
    let sel=document.createElement('select');
    for(let r of PlanungRaum.inKonfig) {
      r.makeOption(sel);
    }
    let o=document.createElement('option');
    o.innerText='(keiner)';
    sel.appendChild(o);
    for(let r of PlanungRaum.alle) {
      if(r.ort==this.kurs.ort) {
        r.makeOption(sel);
      }
    }
    sel.value=this.kurs.raumid;
    sel.onchange=()=>this.raumAusgewaehlt(sel.value);
    sel.onkeyup=(event)=>{
      if(event.key=='Escape') {
        PlanungGeplantes.stopEdit();
      }
    }
    PlanungGeplantes.openEditor(sel,this.raumBearbeitenBtn,this.raumSpan);
    sel.focus();
  }
  async raumAusgewaehlt(raumid) {
    PlanungGeplantes.stopEdit();
    this.kurs.setRaum(raumid);
  }
  raumGeaendert(neuerRaum) {
    for(let i=this.raumSpan.childNodes.length-1;i>=0;--i){
      this.raumSpan.childNodes[i].remove();
    }
    if(neuerRaum) {
      this.raumSpan.appendChild(neuerRaum.makeGeplantesDiv());
    }
  }
  
  notizBearbeiten() {
    PlanungGeplantes.stopEdit();
    let ed=document.createElement('span');
    const farbinp=document.createElement('input');
    farbinp.type='color';
    farbinp.value=this.kurs.planungfarbe || '#FFFFFF';
    farbinp.setAttribute('list','planungfarben');
    ed.appendChild(farbinp);
    const inp=document.createElement('input');
    inp.type='text';
    inp.value=this.kurs.planungnotiz;
    farbinp.onchange=()=>{
      this.notizBearbeitet(inp.value,farbinp.value);
    };
    inp.onkeyup=(event)=>{
      if(event.key=='Enter') {
        this.notizBearbeitet(inp.value,farbinp.value);
      } else if(event.key=='Escape') {
        PlanungGeplantes.stopEdit();
      }
    }
    ed.appendChild(inp);
    PlanungGeplantes.openEditor(ed,this.notizBearbeitenBtn,this.notizSpan);
    inp.focus();
  }
  notizBearbeitet(neueNotiz,neueFarbe) {
    PlanungGeplantes.stopEdit();
    this.kurs.setPlanungNotiz(neueNotiz,neueFarbe);
  }
  notizGeaendert(neueNotiz,neueFarbe) {
    this.notizSpan.innerText=neueNotiz;
    this.notizDiv.style.backgroundColor=neueFarbe;
  }
  
  menueOeffnen() {
    if(this.menue && this.menue.parentNode) {
      PlanungGeplantes.stopEdit();
      return;
    }
    PlanungGeplantes.stopEdit();
    let m=document.createElement('div');
    m.classList.add('popupmenue');
    if(this.kurs.moodleid<=0) {
      PlanungGeplantes.createMenuItem(()=>this.toggleSichtbar(),m,this.kurs.sichtbar ? 'Doz. und TN verbergen' : 'Doz. und TN anzeigen',this.kurs.titel);
    }
    if(this.kurs.moodleid<=0) {
      PlanungGeplantes.createMenuItem(()=>this.titelBearbeiten(),m,'Kurs-Titel ändern',this.kurs.titel);
      PlanungGeplantes.createMenuItem(()=>this.zeitraumBearbeiten(),m,'Kurs-Zeitraum ändern',null);
    }
    PlanungGeplantes.createMenuItem(()=>this.verwalten(),m,'Kurs bearbeiten',null);
    if(this.kurs.moodleid<=0) {
      PlanungGeplantes.createMenuItem(()=>this.kursLoeschen(),m,'Kurs Löschen',null);
    }
    this.div.appendChild(m);
    PlanungGeplantes.addEditor(m);
    this.menue=m;
  }
  
  toggleSichtbar() {
    PlanungGeplantes.stopEdit();
    this.kurs.toggleSichtbar();
  }
  sichtbarGeaendert(neuesSichtbar) {
    if(this.kurs.moodleid<=0) {
      if(neuesSichtbar) {
        this.div.classList.add('sichtbar');
      } else {
        this.div.classList.remove('sichtbar');
      }
    }
  }
  
  titelBearbeiten() {
    PlanungGeplantes.stopEdit();
    let t=prompt('Neuer Kurs-Titel',this.kurs.titel);
    if(t) {
      this.kurs.setTitel(t);
    }
  }
  titelGeaendert(neuerTitel) {
    //nichts zu tun
  }
  
  zeitraumBearbeiten() {
    PlanungGeplantes.stopEdit();
    let ed=document.createElement('div');
    ed.style.display='flex';
    ed.style.flexDirection='column';
    
    let div=document.createElement('div');
    let e=document.createElement('span');
    e.innerText='Beginn ';
    div.appendChild(e);
    const beginnInput=document.createElement('input');
    beginnInput.type='date';
    beginnInput.value=this.kurs.beginn;
    div.appendChild(beginnInput);
    ed.appendChild(div);
    
    div=document.createElement('div');
    e=document.createElement('span');
    e.innerText='Dauer ';
    div.appendChild(e);
    const dauerInput=document.createElement('input');
    dauerInput.type='number';
    dauerInput.min=1;
    dauerInput.value=this.kurs.kw.length;
    dauerInput.style.width='50px';
    div.appendChild(dauerInput);
    const einheitSelect=document.createElement('select');
    let o=document.createElement('option');
    o.value='Wochen';
    o.innerText='Wochen';
    einheitSelect.appendChild(o);
    o=document.createElement('option');
    o.value='Tage';
    o.innerText='Tage';
    einheitSelect.appendChild(o);
    div.appendChild(einheitSelect);
    ed.appendChild(div);
    
    PlanungGeplantes.createButton(()=>this.zeitraumBearbeitet(beginnInput,dauerInput,einheitSelect),ed,'Speichern');
    PlanungGeplantes.createButton(()=>PlanungGeplantes.stopEdit(),ed,'Abbrechen');
    this.div.appendChild(ed);
    PlanungGeplantes.addEditor(ed);
  }
  zeitraumBearbeitet(beginnInput,dauerInput,einheitSelect) {
    let beginn=beginnInput.value;
    if(!beginn) {
      beginnInput.focus();
      return;
    }
    let dauer=dauerInput.value;
    if(!dauer) {
      dauerInput.focus();
      return;
    }
    PlanungGeplantes.stopEdit();
    this.kurs.setZeitraum(beginn,dauer,einheitSelect.value);
  }
  
  verwalten() {
    window.open('../verwaltung/kurs_bearbeiten.php?kursid='+this.kurs.id,'verwaltung');
  }
  
  kursLoeschen() {
    PlanungGeplantes.stopEdit();
    if(confirm('Kurs löschen, sicher?')) {
      this.kurs.loeschen();
    }
  }
}