import { Planung } from './Planung.js';
import { PlanungGeplantes } from './PlanungGeplantes.js';
import { PlanungRaum } from './PlanungRaum.js';
import { PlanungKlasse } from './PlanungKlasse.js';
import { PlanungKurs } from './PlanungKurs.js';

export class PlanungZeile {
  static alle=[];
  static byId={'klasse':{},'dozent':{},'raum':{}};
  
  static createButton(callback,innerText,parentNode=null) {
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
  
  constructor(was,wer) {
    this.was=was;
    this.wer=wer;
    this.geplanteByKursid={};
    PlanungZeile.alle.push(this);
    PlanungZeile.byId[was][wer.id]=this;
  }
   
  makeTr() {
    this.tr=Planung.table.insertRow();
    this.tr.classList.add('planungzeile');
    this.th=document.createElement('th');
    this.th.classList.add('erstespalte');
    this.th.innerHTML='<a href="../verwaltung/'+this.was+'_sehen.php?'+this.was+'id='+this.wer.id+'">'+this.wer.makeThText()+'</a>';
    this.th.title=this.was+' id='+this.wer.id;
    PlanungZeile.createButton(()=>{ location.href='planungkonfigzeile_loeschen.php?was='+this.was+'&wasid='+this.wer.id; },'Entfernen',this.th);
    if(this.was=='klasse') {
      this.menueOeffnenBtn=PlanungZeile.createButton(()=>this.menueOeffnen(),'Langfristige\nPlanung',this.th);
    }
    this.tr.appendChild(this.th);
    this.tdsByKw={};
    for(let w of Planung.wochen) {
      let td=this.tr.insertCell();
      td.vAlign='top';
      td.woche=w;
      this.tdsByKw[w.kw]=td;
      td.appendChild(document.createElement('div'));
      let btn=PlanungGeplantes.createEditButton(()=>this.kursErstellungOeffnen(btn,w),td,'Neuen Kurs erstellen','+');
      btn.style.paddingLeft='2px';
      btn.style.fontWeight='bold';
    }
  }
  addKurs(kurs) {
    this.geplanteByKursid[kurs.id]=[];
    for(let kw of kurs.kw) {
      if(typeof this.tdsByKw[kw]!='undefined') {
        let geplant=new PlanungGeplantes(this,kw,kurs,this.tdsByKw[kw].childNodes[0]);
        kurs.geplante.push(geplant);
        this.geplanteByKursid[kurs.id].push(geplant);
      }
    }
  }
  removeKurs(kurs) {
    if(!this.geplanteByKursid[kurs.id]) return;
    for(let geplant of this.geplanteByKursid[kurs.id]) {
      geplant.entfernen();
      kurs.geplante.splice(kurs.geplante.indexOf(geplant),1);
    }
    delete this.geplanteByKursid[kurs.id];
  }
  
  kursErstellungOeffnen(btn,woche) {
    PlanungGeplantes.stopEdit();
    let ed=document.createElement('div');
    ed.style.padding='2px';
    ed.style.display='flex';
    ed.style.flexDirection='column';
    
    let div=document.createElement('div');
    let e=document.createElement('span');
    e.innerText='Beginn ';
    div.appendChild(e);
    const beginnInput=document.createElement('input');
    beginnInput.type='date';
    beginnInput.value=woche.montag;
    div.appendChild(beginnInput);
    ed.appendChild(div);
    
    div=document.createElement('div');
    e=document.createElement('span');
    e.innerText='Dauer ';
    div.appendChild(e);
    const dauerInput=document.createElement('input');
    dauerInput.type='number';
    dauerInput.min=1;
    dauerInput.value=1;
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
    
    PlanungGeplantes.createButton(()=>this.kursErstellen(beginnInput,dauerInput,einheitSelect),ed,'Kurs erstellen');
    PlanungGeplantes.createButton(()=>PlanungGeplantes.stopEdit(),ed,'Abbrechen');
    
    PlanungGeplantes.openEditor(ed,btn);
  }
  kursErstellen(beginnInput,dauerInput,einheitSelect) {
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
    PlanungKurs.erstellen(beginn,dauer,einheitSelect.value,this.was,this.wer);
  }
  
  addMeldung(meldung) {
    if(meldung.kw) {
      for(let kw of meldung.kw) {
        if(typeof this.tdsByKw[kw]!='undefined') {
          meldung.makeAnzeigediv(this.tdsByKw[kw]);
        }
      }
    } else {
      meldung.makeAnzeigediv(this.th);
    }
  }
  
  // Nur falls this.was=='klasse'
  menueOeffnen() {
    PlanungGeplantes.stopEdit();
    
    let m=document.createElement('div');
    m.classList.add('popupmenu');
    m.style.paddingTop='4px';
    let ok=true;
    if(!this.wer.berufid || this.wer.berufid<=0) {
      ok=false;
      let div=document.createElement('div');
      div.innerText='Klasse hat keinen Beruf :-(';
      m.appendChild(div);
    }
    if(!this.wer.beginn) {
      ok=false;
      let div=document.createElement('div');
      div.innerText='Klasse hat kein Startdatum :-(';
      m.appendChild(div);
    }
    if(ok) {
      PlanungZeile.createButton(()=>this.wer.sollPlanVergleichOeffnen(),'Soll-Plan-Vergleich',m);
      PlanungZeile.createButton(()=>this.sollKurseVorbereiten(m),'Soll-Kurse erstellen',m);
      PlanungZeile.createButton(()=>this.planungKopieVorbereiten(m),'Planung einer vergangenen Klasse kopieren',m);
      PlanungZeile.createButton(()=>this.partnerKurseVorbereiten(m),'In die Kurse einer gleichzeitigen Klasse einschreiben',m);
      PlanungZeile.createButton(()=>this.partnerKurseVorbereiten(m),'Aus den Kursen einer gleichzeitigen Klasse abmelden',m);
    }
    PlanungZeile.createButton(PlanungGeplantes.stopEdit,'Abbrechen',m);
    PlanungGeplantes.openEditor(m,this.menueOeffnenBtn);
  }
  
  // Nur falls this.was=='klasse'
  async sollKurseVorbereiten(menue) {
    for(let i=menue.childNodes.length-1;i>=0;--i) {
      menue.childNodes[i].remove();
    }
    let div=document.createElement('div');
    div.innerText='Klassen-Raum:';
    div.style.width='100%';
    div.textAlign='left';
    menue.appendChild(div);
    await PlanungRaum.findeAlle(this.wer.ort);
    const raumSel=document.createElement('select');
    let o=document.createElement('option');
    o.innerText='(keiner)';
    raumSel.appendChild(o);
    for(let r of PlanungRaum.alle) {
      r.makeOption(raumSel);
    }
    menue.appendChild(raumSel);
    PlanungZeile.createButton(()=>this.sollKurseErstellen(raumSel),'Soll-Kurse erstellen',menue);
    PlanungZeile.createButton(PlanungGeplantes.stopEdit,'Abbrechen',menue);
  }
  sollKurseErstellen(raumSel) {
    PlanungGeplantes.stopEdit();
    if(confirm('Soll-Kurse erstellen, sicher?')) {
      this.wer.sollKurseErstellen(raumSel.value);
    }
  }
  
  // Nur falls this.was=='klasse'
  async planungKopieVorbereiten(menue) {
    for(let i=menue.childNodes.length-1;i>=0;--i) {
      menue.childNodes[i].remove();
    }
    let div=document.createElement('div');
    div.innerText='Modell-Klasse:';
    div.style.width='100%';
    div.textAlign='left';
    menue.appendChild(div);
    let klassen=await this.wer.findePotentielleModelle();
    const partnerSel=document.createElement('select');
    for(let k of klassen) {
      k.makeOption(partnerSel,true);
    }
    menue.appendChild(partnerSel);
    PlanungZeile.createButton(()=>this.planungKopieren(partnerSel),'Planung kopieren',menue);
    PlanungZeile.createButton(PlanungGeplantes.stopEdit,'Abbrechen',menue);
  }
  planungKopieren(partnerSel) {
    let partnerid=partnerSel.value;
    if(!partnerid) {
      partnerSel.focus();
      return;
    }
    PlanungGeplantes.stopEdit();
    this.wer.planungKopieren(partnerid);
  }
  
  // Nur falls this.was=='klasse'
  async partnerKurseVorbereiten(menue) {
    for(let i=menue.childNodes.length-1;i>=0;--i) {
      menue.childNodes[i].remove();
    }
    let div=document.createElement('div');
    div.innerText='Partner-Klasse:';
    div.style.width='100%';
    div.style.textAlign='left';
    menue.appendChild(div);
    let klassen=await this.wer.findePotentiellePartner();
    const partnerSel=document.createElement('select');
    for(let k of klassen) {
      k.makeOption(partnerSel,true);
    }
    menue.appendChild(partnerSel);
    PlanungZeile.createButton(()=>this.inPartnerKurseAnmelden(partnerSel),'In die Kurse anmelden',menue);
    PlanungZeile.createButton(()=>this.ausPartnerKursenAbmelden(partnerSel),'Aus den Kursen abmelden',menue);
    PlanungZeile.createButton(PlanungGeplantes.stopEdit,'Abbrechen',menue);
  }
  inPartnerKurseAnmelden(partnerSel) {
    let partnerid=partnerSel.value;
    if(!partnerid) {
      partnerSel.focus();
      return;
    }
    PlanungGeplantes.stopEdit();
    this.wer.inPartnerKurseAnmelden(partnerid);
  }
  ausPartnerKursenAbmelden(partnerSel) {
    let partnerid=partnerSel.value;
    if(!partnerid) {
      partnerSel.focus();
      return;
    }
    PlanungGeplantes.stopEdit();
    this.wer.ausPartnerKursenAbmelden(partnerid);
  }
}