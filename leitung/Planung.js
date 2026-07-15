import { PlanungObject } from './PlanungObject.js';
import { PlanungKurs } from './PlanungKurs.js';
import { PlanungKlasse } from './PlanungKlasse.js';
import { PlanungDozent } from './PlanungDozent.js';
import { PlanungRaum } from './PlanungRaum.js';
import { PlanungZeile } from './PlanungZeile.js';
import { PlanungMeldung } from './PlanungMeldung.js';
import { PlanungFerien } from './PlanungFerien.js';

export class Planung {
  static async init(wochenJson,klassenJson,dozentenJson,raeumeJson,kurseJson,ferienJson,kompakt) {
    this.kompakt=false;
    
    this.wochen=wochenJson;
    this.wochenByKw={};
    for(let i=0;i<this.wochen.length;++i) {
      this.wochen[i].tdidx=i+1;
      this.wochenByKw[this.wochen[i].kw]=this.wochen[i];
    }
    
    PlanungObject.planung=this;
    
    this.table=document.getElementById('planungtable');
    this.klassentr=document.getElementById('klassentr');
    this.klassenkwtr=document.getElementById('klassenkwtr');
    this.dozententr=document.getElementById('dozententr');
    this.dozentenkwtr=document.getElementById('dozentenkwtr');
    this.raeumetr=document.getElementById('raeumetr');
    this.raeumekwtr=document.getElementById('raeumekwtr');
    this.raeumekwtr.remove();
    this.raeumetr.remove();
    this.dozentenkwtr.remove();
    this.dozententr.remove();
    PlanungObject.ladeAlle(PlanungKlasse,klassenJson);
    PlanungKlasse.inKonfig=[];
    for(let obj of PlanungKlasse.alle) {
      obj.istInKonfig=true;
      PlanungKlasse.inKonfig.push(obj);
      let z=new PlanungZeile('klasse',obj);
      z.makeTr();
      obj.addZeitraum(z);
    }
    this.table.appendChild(this.dozententr);
    this.table.appendChild(this.dozentenkwtr);
    PlanungObject.ladeAlle(PlanungDozent,dozentenJson);
    PlanungDozent.inKonfig=[];
    for(let obj of PlanungDozent.alle) {
      obj.istInKonfig=true;
      PlanungDozent.inKonfig.push(obj);
      let z=new PlanungZeile('dozent',obj);
      z.makeTr();
      obj.addVerfuegbarkeit(z);
    }
    this.table.appendChild(this.raeumetr);
    this.table.appendChild(this.raeumekwtr);
    PlanungObject.ladeAlle(PlanungRaum,raeumeJson);
    PlanungRaum.inKonfig=[];
    for(let obj of PlanungRaum.alle) {
      obj.istInKonfig=true;
      PlanungRaum.inKonfig.push(obj);
      let z=new PlanungZeile('raum',obj);
      z.makeTr();
    }
    
    this.cbKompakt=document.getElementById('cb_kompakt');
    this.cbKompakt.checked=kompakt;
    this.cbKompakt.onchange=this.kompaktGeaendert.bind(this);

    PlanungObject.ladeAlle(PlanungKurs,kurseJson);
    for(let k of PlanungKurs.alle) {
      k.install();
    }
    PlanungFerien.installAlle(this,ferienJson);
    PlanungMeldung.refresh();
    this.kompaktGeaendert();
  }
  
  static async kompaktGeaendert() {
    const kompakt=this.cbKompakt.checked;
    const req=new XMLHttpRequest();
    req.onreadystatechange = function() {
      if (req.readyState != 4) return;
      if (req.responseText.startsWith('OK')) {
        Planung.kompakt=kompakt;
        for(let z of PlanungZeile.alle) {
          z.setKompakt(kompakt);
        }
        for(let f of PlanungFerien.alle) {
          f.kompaktGeaendert();
        }
      } else {
        alert(req.responseText);
      }
    };
    req.open('GET','planung_kompakt_speichern.php?kompakt='+(kompakt ? 'J' : 'N'));
    req.send();
  }
}