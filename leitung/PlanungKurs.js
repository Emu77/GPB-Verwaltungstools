import { PlanungObject } from './PlanungObject.js';
import { PlanungZeile } from './PlanungZeile.js';
import { PlanungModul } from './PlanungModul.js';
import { PlanungKlasse } from './PlanungKlasse.js';
import { PlanungDozent } from './PlanungDozent.js';
import { PlanungRaum } from './PlanungRaum.js';
import { PlanungMeldung } from './PlanungMeldung.js';

export class PlanungKurs extends PlanungObject {
  
  static async erstellen(beginn,dauer,einheit,was,wer) {
    const response = await fetch('kurs_erstellen.php?beginn='+encodeURIComponent(beginn)+'&dauer='+dauer+'&einheit='+encodeURIComponent(einheit)+'&was='+was+'&wasid='+wer.id);
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    let obj=JSON.parse(txt.substring(2));
    let kurs=PlanungObject.ladeEines(PlanungKurs,obj);
    kurs.install();
    
    PlanungMeldung.refresh();
  }
  
  constructor(obj) {
    super(obj);
    this.geplante=[];
  }
  
  install() {
    for(let k of this.klassen) {
      let zeile=PlanungZeile.byId['klasse'][k.id];
      if(zeile) {
        zeile.addKurs(this);
      }
    }
    for(let d of this.dozenten) {
      let zeile=PlanungZeile.byId['dozent'][d.id];
      if(zeile) {
        zeile.addKurs(this);
      }
    }
    if(this.raumid) {
      let zeile=PlanungZeile.byId['raum'][this.raumid];
      if(zeile) {
        zeile.addKurs(this);
      }
    }
  }
  uninstall() {
    for(let k of this.klassen) {
      let zeile=PlanungZeile.byId['klasse'][k.id];
      if(zeile) {
        zeile.removeKurs(this);
      }
    }
    for(let d of this.dozenten) {
      let zeile=PlanungZeile.byId['dozent'][d.id];
      if(zeile) {
        zeile.removeKurs(this);
      }
    }
    if(this.raumid) {
      let zeile=PlanungZeile.byId['raum'][this.raumid];
      if(zeile) {
        zeile.removeKurs(this);
      }
    }
  }
  
  addMeldung(meldung) {
    for(let geplant of this.geplante) {
      let woche=PlanungObject.planung.wochenByKw[geplant.kw];
      if(meldung.beginn<=woche.freitag && meldung.ende>=woche.montag) {
        meldung.makeAnzeigediv(geplant.div);
      }
    }
  }
  
  async loeschen() {
    const response = await fetch('kurs_loeschen.php?kursid='+this.id);
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(txt!='OK') {
      console.log(response);
      alert(txt);
      return;
    }
    this.uninstall();
    PlanungKurs.alle.splice(PlanungKurs.alle.indexOf(this),1);
    delete PlanungKurs.byId[this.id];
    
    PlanungMeldung.refresh();
  }
  
  async toggleSichtbar() {
    const response = await fetch('kurs_sichtbar_speichern.php?kursid='+this.id+'&sichtbar='+(this.sichtbar ? 'N' : 'J'));
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    if(txt.length>2) {
      console.log(txt.substr(2));
      alert(txt.substr(2));
    }
    this.sichtbar=!this.sichtbar;
    for(let geplant of this.geplante) {
      geplant.sichtbarGeaendert(this.sichtbar);
    }
  }
  
  async setTitel(titel) {
    if(titel==this.titel || !titel) return;
    const response = await fetch('kurs_titel_speichern.php?kursid='+this.id+'&kursmoodleid='+this.moodleid+'&titel='+encodeURIComponent(titel));
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    if(txt.length>2) {
      console.log(txt.substr(2));
      alert(txt.substr(2));
    }
    this.titel=titel;
    for(let geplant of this.geplante) {
      geplant.titelGeaendert(titel);
    }
  }
  titelGenerieren() {
     let t=this.beginn;
    //TODO kürzen
    for(let k of this.klassen) {
      t+=' '+k.bezeichnung;
    }
    if(this.modulid>0) {
      t+=' '+this.modultitel;
    }
    return t;
  }
  
  async setZeitraum(beginn,dauer,einheit) {
    const response = await fetch('kurs_zeitraum_speichern.php?kursid='+this.id+'&kursmoodleid='+this.moodleid+'&beginn='+encodeURIComponent(beginn)+'&dauer='+dauer+'&einheit='+encodeURIComponent(einheit));
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    this.uninstall();
    let obj=JSON.parse(txt.substring(2));
    if(obj.fehler) {
      console.log(obj.fehler);
      alert(JSON.stringify(obj.fehler));
    }
    let beginnGeaendert=this.beginn!=obj.beginn;
    this.beginn=obj.beginn;
    this.von=obj.von;
    this.ende=obj.ende;
    this.bis=obj.bis;
    this.kw=obj.kw;
    this.install();
    
    if(beginnGeaendert) {
      this.setTitel(this.titelGenerieren());
    }
    
    PlanungMeldung.refresh();
  }
  
  async setModul(modulid) {
    if(modulid==this.modulid) return;
    const response = await fetch('kurs_modul_speichern.php?kursid='+this.id+'&modulid='+modulid);
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(txt!='OK') {
      console.log(response);
      alert(txt);
      return;
    }
    this.modulid=modulid;
    let modul=null;
    if(modulid) {
      modul=PlanungModul.byId[modulid];
      this.modultitel=modul.titel;
      this.moduldauer=modul.dauer;
    } else {
      this.modultitel=null;
      this.moduldauer=null;
    }
    for(let geplant of this.geplante) {
      geplant.modulGeaendert(modul);
    }
    
    this.setTitel(this.titelGenerieren());
    
    //TODO einschalten, falls Soll-Plan-Vergleich auf der Planungsseite angezeigt werden soll
    //PlanungMeldung.refresh();
  }
  
  async setKlassenids(klassenids) {
    const response = await fetch('kurs_klassen_speichern.php?kursid='+this.id+'&kursmoodleid='+this.moodleid+'&klassenids='+encodeURIComponent(JSON.stringify(klassenids)));
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    if(txt.length>2) {
      console.log(txt.substring(2));
      alert(txt.substring(2));
    }
    let geaendert=false;
    for(let i=this.klassen.length-1;i>=0;--i) {
      let klasse=this.klassen[i];
      let j=klassenids.indexOf(klasse.id);
      if(j<0) {
        let zeile=PlanungZeile.byId['klasse'][klasse.id];
        if(zeile) {
          zeile.removeKurs(this);
        }
        this.klassen.splice(i,1);
        geaendert=true;
      }
    }
    for(let kid of klassenids) {
      let j=this.klassen.length-1;
      while(j>=0 && this.klassen[j].id!=kid) --j;
      if(j<0) {
        this.klassen.push(PlanungKlasse.byId[kid]);
        let zeile=PlanungZeile.byId['klasse'][kid];
        if(zeile) {
          zeile.addKurs(this);
        }
        geaendert=true;
      }
    }
    if(geaendert) {
      for(let geplant of this.geplante) {
        geplant.klassenGeaendert();
      }
      this.setTitel(this.titelGenerieren());
    }
    PlanungMeldung.refresh();
  }
  
  async setDozentenids(dozentenids) {
    const response = await fetch('kurs_dozenten_speichern.php?kursid='+this.id+'&kursmoodleid='+this.moodleid+'&dozentenids='+encodeURIComponent(JSON.stringify(dozentenids)));
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    if(txt.length>2) {
      console.log(txt.substring(2));
      alert(txt.substring(2));
    }
    let geaendert=false;
    for(let i=this.dozenten.length-1;i>=0;--i) {
      let dozent=this.dozenten[i];
      let j=dozentenids.indexOf(dozent.id);
      if(j<0) {
        let zeile=PlanungZeile.byId['dozent'][dozent.id];
        if(zeile) {
          zeile.removeKurs(this);
        }
        this.dozentenids.splice(i,1);
        this.dozenten.splice(i,1);
        geaendert=true;
      }
    }
    for(let did of dozentenids) {
      let j=this.dozentenids.indexOf(did);
      if(j<0) {
        this.dozentenids.push(did);
        this.dozenten.push(PlanungDozent.byId[did]);
        let zeile=PlanungZeile.byId['dozent'][did];
        if(zeile) {
          zeile.addKurs(this);
        }
        geaendert=true;
      }
    }
    if(geaendert) {
      for(let geplant of this.geplante) {
        geplant.dozentenGeaendert();
      }
    }
    PlanungMeldung.refresh();
  }
  
  async setRaum(raumid) {
    if(raumid==this.raumid) return;
    const response = await fetch('kurs_raum_speichern.php?kursid='+this.id+'&raumid='+raumid);
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(txt!='OK') {
      console.log(response);
      alert(txt);
      return;
    }
    let alteZeile=this.raumid ? PlanungZeile.byId['raum'][this.raumid] : null;
    if(alteZeile) {
      alteZeile.removeKurs(this);
    }
    this.raumid=raumid;
    let raum=null;
    if(raumid) {
      raum=PlanungRaum.byId[raumid];
      this.ort=raum.ort;
      this.raum=raum.tuer;
      let zeile=PlanungZeile.byId['raum'][this.raumid];
      if(zeile) {
        zeile.addKurs(this);
      }
    } else {
      this.ort=null;
      this.raum=null;
    }
    for(let geplant of this.geplante) {
      geplant.raumGeaendert(raum);
    }
    
    PlanungMeldung.refresh();
  }
  
  async setPlanungNotiz(notiz,farbe) {
    if(farbe==null || farbe.toLowerCase()=='#ffffff' || farbe=='#000000') farbe='';
    if(notiz==this.planungnotiz && farbe==this.planungfarbe) return;
    const response = await fetch('kurs_planungnotiz_speichern.php?kursid='+this.id+'&notiz='+encodeURIComponent(notiz)+'&farbe='+encodeURIComponent(farbe));
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(txt!='OK') {
      console.log(response);
      alert(txt);
      return;
    }
    this.planungnotiz=notiz;
    this.planungfarbe=farbe;
    for(let geplant of this.geplante) {
      geplant.notizGeaendert(notiz,farbe);
    }
  }
}