import { PlanungObject } from './PlanungObject.js';
import { PlanungKurs } from './PlanungKurs.js';

export class PlanungKlasse extends PlanungObject {
  
  static async finde(suchtext) {
    if(!suchtext) return [];
    const response = await fetch('klassen_finden.php?bezeichnung='+encodeURIComponent(suchtext));
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    const json = await response.json();
    PlanungObject.ladeAlle(PlanungKlasse,json);
    let klassen=[];
    for(let obj of json) {
      klassen.push(PlanungKlasse.byId[obj.id]);
    }
    return klassen;
  }
  
  static formatDate(d) {
    if(!d || d.length<10) return '?';
    return d.substr(8,2)+'.'+d.substr(5,2)+'.'+d.substr(0,4);
  }
  
  constructor(obj) {
    super(obj);
  }
  makeThText() {
    return this.makeText()+'\n'+PlanungKlasse.formatDate(this.beginn)+' '+PlanungKlasse.formatDate(this.ende);
  }
  makeText() {
    return this.bezeichnung+' ('+this.anzahlTN+'/'+this.anzahlAnmeldungen+' TN)';
  }
  
  sollPlanVergleichOeffnen() {
    window.open('../verwaltung/klasse_sollplanvergleich.php?klasseid='+this.id,'sollplanvergleich');
  }
  async sollKurseErstellen(raumid) {
    if(this.berufid<=0) {
      alert('Klasse hat keinen Beruf :-(');
      return;
    }
    if(!this.beginn) {
      alert('Klasse hat kein Startdatum :-(');
      return;
    }
    const response = await fetch('klasse_sollkurse_erstellen.php?klasseid='+this.id+'&raumid='+raumid+'&redirect=N');
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    let json=JSON.parse(txt.substring(2));
    for(let obj of json) {
      let kurs=PlanungKurs.byId[obj.id];
      if(kurs) {
        kurs.uninstall();
        kurs.klassen=obj.klassen;
      } else {
        kurs=PlanungObject.ladeEines(PlanungKurs,obj);
      }
      kurs.install();
    }
  }
  
  async findePotentiellePartner() {
    const response = await fetch('klassen_finden.php?partnerid='+this.id+'&berufid='+this.berufid+'&partnerab='+this.beginn);
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    const json = await response.json();
    PlanungObject.ladeAlle(PlanungKlasse,json);
    let klassen=[];
    for(let obj of json) {
      klassen.push(PlanungKlasse.byId[obj.id]);
    }
    return klassen;
  }
  async inPartnerKurseAnmelden(partnerid) {
    const response = await fetch('klasse_partnerkurse_anmelden.php?klasseid='+this.id+'&partnerid='+partnerid+'&redirect=N');
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    let json=JSON.parse(txt.substring(2));
    for(let obj of json) {
      let kurs=PlanungKurs.byId[obj.id];
      if(kurs) {
        kurs.uninstall();
        kurs.klassen=obj.klassen;
      } else {
        kurs=PlanungObject.ladeEines(PlanungKurs,obj);
      }
      kurs.install();
    }
  }
  async ausPartnerKursenAbmelden(partnerid) {
    const response = await fetch('klasse_partnerkurse_abmelden.php?klasseid='+this.id+'&partnerid='+partnerid+'&redirect=N');
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    let json=JSON.parse(txt.substring(2));
    for(let obj of json) {
      let kurs=PlanungKurs.byId[obj.id];
      if(kurs) {
        kurs.uninstall();
        kurs.klassen=obj.klassen;
        kurs.install();
      }
    }
  }
  
  async findePotentielleModelle() {
    const response = await fetch('klassen_finden.php?partnerid='+this.id+'&berufid='+this.berufid);
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    const json = await response.json();
    PlanungObject.ladeAlle(PlanungKlasse,json);
    let klassen=[];
    for(let obj of json) {
      klassen.push(PlanungKlasse.byId[obj.id]);
    }
    return klassen;
  }
  async planungKopieren(partnerid) {
    const response = await fetch('klasse_planung_kopieren.php?klasseid='+this.id+'&partnerid='+partnerid+'&redirect=N');
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    let json=JSON.parse(txt.substring(2));
    for(let obj of json) {
      let kurs=PlanungKurs.byId[obj.id];
      if(kurs) {
        kurs.uninstall();
        kurs.klassen=obj.klassen;
      } else {
        kurs=PlanungObject.ladeEines(PlanungKurs,obj);
      }
      kurs.install();
    }
  }
}
