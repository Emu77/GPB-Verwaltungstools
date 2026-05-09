import { PlanungZeile } from './PlanungZeile.js';

export class PlanungFerien {
  
  static planung=null;
  static alle=[];
  static installAlle(planung,json) {
    PlanungFerien.planung=planung;
    for(let obj of json) {
      let ferien=new PlanungFerien(obj);
      PlanungFerien.alle.push(ferien);
      ferien.install(planung);
    }
  }
  
  static formatDate(d) {
    if(!d || d.length<10) return '?';
    return d.substr(8,2)+'.'+d.substr(5,2);
  }
  
  constructor(obj) {
    for(let k in obj) {
      this[k]=obj[k];
    }
  }
  makeDisplay(parentNode) {
    let div=document.createElement('div');
    div.classList.add('ferien');
    div.innerText=PlanungFerien.formatDate(this.beginn)+(this.ende==this.beginn ? '' : '-'+PlanungFerien.formatDate(this.ende))+' '+this.anlass;
    if(parentNode) parentNode.appendChild(div);
  }
  install(planung) {
    if(this.art=='Klassenferien') {
      for(let klasse of this.klassen) {
        let zeile=PlanungZeile.byId['klasse'][klasse.id];
        if(!zeile) continue;
        for(let kw of this.kw) {
          let woche=planung.wochenByKw[kw];
          if(!woche) continue;
//          if(this.beginn<=woche.montag && this.ende>=woche.freitag) {
//            zeile.tr.cells[woche.tdidx].classList.add('ferien');
//          }
          this.makeDisplay(zeile.tr.cells[woche.tdidx]);
        }
      }
    } else if(this.art=='Institutsferien') {
      for(let kw of this.kw) {
        let woche=planung.wochenByKw[kw];
        if(!woche) continue;
        let klasseangezeigt=false,raumangezeigt=false;
        for(let z of PlanungZeile.alle) {
          if(this.ort==z.wer.ort) {
//            if(this.beginn<=woche.montag && this.ende>=woche.freitag) {
//              z.cells[woche.tdidx].classList.add('ferien');
//            }
            if(z.was=='klasse') klasseangezeigt=true;
            else if(z.was=='raum') klasseangezeigt=true;
          }
        }
        if(klasseangezeigt) {
          this.makeDisplay(planung.klassenkwtr.cells[woche.tdidx]);
        }
        if(raumangezeigt) {
          this.makeDisplay(planung.raeumeenkwtr.cells[woche.tdidx]);
        }
      }
    } else {
      for(let kw of this.kw) {
        let woche=planung.wochenByKw[kw];
        if(!woche) continue;
        if(this.beginn<=woche.montag && this.beginn>=woche.freitag) {
          for(let i=0;i<planung.table.rows.length;++i) {
            let tr=planung.table.rows[i];
            if(tr==planung.klassentr || tr==planung.dozententr || tr==planung.raeumetr) continue;
//            tr.cells[woche.tdidx].classList.add('ferien');
          }
        }
        this.makeDisplay(planung.klassenkwtr.cells[woche.tdidx]);
        this.makeDisplay(planung.dozentenkwtr.cells[woche.tdidx]);
        this.makeDisplay(planung.raeumekwtr.cells[woche.tdidx]);
      }
    }
  }
}