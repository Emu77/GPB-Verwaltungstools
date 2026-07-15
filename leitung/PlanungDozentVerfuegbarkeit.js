import { PlanungGeplantes } from './PlanungGeplantes.js';
import { PlanungMeldung } from './PlanungMeldung.js';


export class PlanungDozentVerfuegbarkeit {
  
  //PHP muss PlanungDozentVerfuegbarkeit.modelleByWert setzen
  
  constructor(dozent,obj) {
    this.dozent=dozent;
    for(let k in obj) {
      this[k]=obj[k];
    }
    this.modell=PlanungDozentVerfuegbarkeit.modelleByWert[this.verfuegbar];
    this.divsByKw=null;
  }
  install() {
    if(this.divsByKw!=null) return;
    this.divsByKw={};
    for(let kw of this.kw) {
      if(!this.dozent.zeile.tdsByKw[kw]) continue; // KW außerhalb des angezeigten Zeitraums
      
      let div=document.createElement('div');
      div.classList.add('verfuegbarkeit');
      div.style.backgroundColor=this.modell.farbe;
      
      div.bearbeitenBtn=PlanungGeplantes.createEditButton(()=>this.bearbeiten(kw),div);
      
      div.span=document.createElement('span');
      div.appendChild(div.span);
      if(this.id>0){
        div.span.innerText=this.verfuegbar+(this.notiz.length>0 ? ' ('+this.notiz+')' : '');
      } else {
        div.span.innerText=' ';
      }
      
      this.divsByKw[kw]=div;
      this.dozent.zeile.tdsByKw[kw].childNodes[0].insertBefore(div,this.dozent.zeile.tdsByKw[kw].childNodes[0].childNodes[0]);
    }
  }
  uninstall() {
    if(this.divsByKw==null) return;
    for(let kw of this.kw) {
      if(this.divsByKw[kw]) this.divsByKw[kw].remove();
    }
    this.divsByKw=null;
  }
  
  dozentIdrverfuegbarGeaendert(neu) {
    if(this.id>0) return;
    this.verfuegbar=neu ? 'Verfügbar' : 'Nicht verfügbar';
    this.modell=PlanungDozentVerfuegbarkeit.modelleByWert[this.verfuegbar];
    for(let kw of this.kw) {
      if(this.divsByKw[kw]) this.divsByKw[kw].style.backgroundColor=this.modell.farbe;
    }
  }
  createEditorTr(thText,input,table) {
    let tr=table.insertRow();
    let th=document.createElement('th');
    th.align='right';
    if(typeof thText=='string') {
      th.innerText=thText;
    } else if(thText) {
      th.appendChild(thText);
    }
    tr.appendChild(th);
    let td=tr.insertCell();
    if(input) {
      td.appendChild(input);
    }
    return td;
  }
  bearbeiten(kw) {
    if(!this.divsByKw[kw]) return;
    
    PlanungGeplantes.stopEdit();
    let ed=document.createElement('table');
    ed.border=1;
    ed.cellspacing=0;
    ed.style.borderCollapse='collapse';
    
    let verfuegbarInput=document.createElement('select');
    for(let wert in PlanungDozentVerfuegbarkeit.modelleByWert) {
      let o=document.createElement('option');
      o.value=wert;
      o.innerText=wert;
      o.style.backgroundColor=PlanungDozentVerfuegbarkeit.modelleByWert[wert].farbe;
      verfuegbarInput.appendChild(o);
    }
    verfuegbarInput.value=this.id>0 ? this.verfuegbar : this.dozent.idrverfuegbar ? 'Nicht verfügbar' : 'Verfügbar';    
    this.createEditorTr('',verfuegbarInput,ed);
    
    let beginnInput=document.createElement('input');
    beginnInput.type='date';
    beginnInput.value=this.beginn;
    beginnInput.onchange=()=>{
      if(!beginnInput.value) {
        beginnInput.value=this.beginn;
      }
    };
    this.createEditorTr('von',beginnInput,ed);
    
    let endeInput=document.createElement('input');
    endeInput.type='date';
    endeInput.value=this.ende;
    endeInput.onchange=()=>{
      if(!endeInput.value) {
        endeInput.value=this.ende;
      }
    };
    this.createEditorTr('bis',endeInput,ed);
    
    let notizInput=document.createElement('input');
    notizInput.type='text';
    notizInput.value=this.notiz;
    this.createEditorTr('Notiz',notizInput,ed);
    
    verfuegbarInput.onkeyup=beginnInput.onkeyup=endeInput.onkeyup=notizInput.onkeyup=(event)=>{
      if(event.key=='Escape') {
        PlanungGeplantes.stopEdit();
      } else if(event.key=='Enter') {
        this.speichern(verfuegbarInput,beginnInput,endeInput,notizInput);
      }
    };
    
    let td=this.createEditorTr('',PlanungGeplantes.createButton(()=>this.speichern(verfuegbarInput,beginnInput,endeInput,notizInput),null,'Speichern'),ed);
    if(this.id>0) {
      PlanungGeplantes.createButton(()=>this.loeschen(),td,'Löschen')
    }
    PlanungGeplantes.createButton(PlanungGeplantes.stopEdit,td,'Abbrechen')
    
    let div=this.divsByKw[kw];
    PlanungGeplantes.openEditor(ed,div.bearbeitenBtn,div.span);
  }
  
  async speichern(verfuegbarInput,beginnInput,endeInput,notizInput) {
    let verfuegbar=verfuegbarInput.value;
    if(!verfuegbar) {
      verfuegbarInput.focus();
      return;
    }
    let beginn=beginnInput.value;
    if(!beginn) {
      beginn=this.beginn;
    }
    let ende=endeInput.value;
    if(!ende) {
      ende=this.ende;
    }
    let notiz=notizInput.value;
    const response = await fetch('dozent_verfuegbarkeit_speichern.php?id='+this.id+'&dozentid='+this.dozent.id+'&verfuegbar='+encodeURIComponent(verfuegbar)+'&beginn='+beginn+'&ende='+ende+'&notiz='+encodeURIComponent(notiz));
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    PlanungGeplantes.stopEdit();
    let obj=JSON.parse(txt.substring(2));
    if(obj.beginn==this.beginn && obj.ende==this.ende) {
      for(let k in obj) {
        this[k]=obj[k];
      }
      this.modell=PlanungDozentVerfuegbarkeit.modelleByWert[this.verfuegbar];
      for(let kw of this.kw) {
        if(!this.divsByKw[kw]) continue;
        this.divsByKw[kw].style.backgroundColor=this.modell.farbe;
        if(this.id>0){
          this.divsByKw[kw].span.innerText=this.verfuegbar+(this.notiz.length>0 ? ' ('+this.notiz+')' : '');
        } else {
          this.divsByKw[kw].span.innerText=' ';
        }
      }
    } else {
      this.dozent.verfuegbarkeitGeaendert(this,obj);
    }
    PlanungMeldung.refresh();
  }
  async loeschen() {
    const response = await fetch('dozent_verfuegbarkeit_loeschen.php?id='+this.id+'&dozentid='+this.dozent.id);
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    PlanungGeplantes.stopEdit();
    this.dozent.verfuegbarkeitGeloescht(this);
    PlanungMeldung.refresh();
  }
}