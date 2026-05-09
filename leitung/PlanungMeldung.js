import { PlanungZeile } from './PlanungZeile.js';
import { PlanungKurs } from './PlanungKurs.js';

export class PlanungMeldung {
  static alle=[];
  
  static async refresh() {
    const response = await fetch('planung_checks.php');
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let json=await response.json();
    if(!json) {
      console.log(response);
      alert(JSON.stringify(response));
      return;
    }
    for(let m of PlanungMeldung.alle) {
      m.uninstall();
    }
    PlanungMeldung.alle=[];
    for(let obj of json) {
      let m=new PlanungMeldung(obj);
      PlanungMeldung.alle.push(m);
      m.install();
    }
  }
  
  constructor(obj) {
    for(let k in obj){
      this[k]=obj[k];
    }
  }
  install() {
    this.anzeigedivs=[];
    if(this.kw) {
      let tr=document.getElementById('meldungen').insertRow();
      let td=tr.insertCell();
      if(this.kw.length<=1) {
        td.innerText='KW '+this.kw[0].substring(5);
      } else {
        td.innerText='KW '+this.kw[0].substring(5)+' bis '+this.kw[this.kw.length-1].substring(5);
      }
      td=tr.insertCell();
      td.innerText=this.meldung;
      this.anzeigedivs.push(tr);
    } else {
      this.makeAnzeigediv(document.getElementById('meldungenohnekw'));
    }
    
    if(this.klasseid) {
      let zeile=PlanungZeile.byId['klasse'][this.klasseid];
      if(zeile) {
        zeile.addMeldung(this);
      }
    }
    if(this.dozentid) {
      let zeile=PlanungZeile.byId['dozent'][this.dozentid];
      if(zeile) {
        zeile.addMeldung(this);
      }
    }
    if(this.raumid) {
      let zeile=PlanungZeile.byId['raum'][this.raumid];
      if(zeile) {
        zeile.addMeldung(this);
      }
    }
    if(this.kursid) {
      let kurs=PlanungKurs.byId[this.kursid];
      if(kurs) {
        kurs.addMeldung(this);
      }
    }
  }
  makeAnzeigediv(parentNode) {
    let anz=document.createElement('div');
    anz.classList.add('meldung');
    anz.innerText=this.meldung;
    if(parentNode) {
      parentNode.appendChild(anz);
    }
    this.anzeigedivs.push(anz);
    return anz;
  }
  uninstall() {
    if(!this.anzeigedivs) return;
    for(let anz of this.anzeigedivs) {
      anz.remove();
    }
    delete this.anzeigedivs;
  }
}