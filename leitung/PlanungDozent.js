import { PlanungObject } from './PlanungObject.js';
import { Planung } from './Planung.js';
import { PlanungDozentVerfuegbarkeit } from './PlanungDozentVerfuegbarkeit.js';


export class PlanungDozent extends PlanungObject {
  
  static async finde(suchtext,modulid) {
    const response = await fetch('dozenten_finden.php?name='+encodeURIComponent(suchtext)+'&modulid='+modulid);
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    const json = await response.json();
    PlanungObject.ladeAlle(PlanungDozent,json);
    let dozenten=[];
    for(let obj of json) {
      dozenten.push(PlanungDozent.byId[obj.id]);
    }
    return dozenten;
  }
  
  constructor(obj) {
    super(obj);
    this.idrverfuegbar=this.idrverfuegbar!=0;
    if(typeof this.verfuegbarkeiten!='undefined') {
      this.verfuegbarkeitenByKw={};
      let verfs=[];
      for(let v of this.verfuegbarkeiten) {
        v=new PlanungDozentVerfuegbarkeit(this,v);
        verfs.push(v);
        for(let kw of v.kw) {
          this.verfuegbarkeitenByKw[kw]=v;
        }
      }
      this.verfuegbarkeiten=verfs;
    }
  }
  makeText() {
    return this.vorname+' '+this.nachname;
  }
  addVerfuegbarkeit(zeile) {
    this.zeile=zeile;
    let div=document.createElement('div');
    div.style.fontWeight='normal';
    div.style.whiteSpace='pre';
    div.innerText='IdR verfügbar';
    this.zeile.th.insertBefore(div,this.zeile.th.childNodes[0]);
    this.idrverfuegbarcb=document.createElement('input');
    this.idrverfuegbarcb.type='checkbox';
    this.idrverfuegbarcb.checked=this.idrverfuegbar;
    this.idrverfuegbarcb.onchange=()=>this.setIdrverfuegbar(this.idrverfuegbarcb.checked);
    div.appendChild(this.idrverfuegbarcb);
    for(let w of Planung.wochen) {
      let v=this.verfuegbarkeitenByKw[w.kw];
      if(!v) {
        v=new PlanungDozentVerfuegbarkeit(this,{
          'id':0,
          'dozentid':this.id,
          'verfuegbar':this.idrverfuegbar ? 'Verfügbar' : 'Nicht verfügbar',
          'beginn':w.montag,
          'ende':w.freitag,
          'kw':[w.kw],
          'notiz':''
        });
        this.verfuegbarkeitenByKw[w.kw]=v;
      }
      v.install();
    }
  }
  async setIdrverfuegbar(idrverfuegbar) {
    const response = await fetch('dozent_idrverfuegbar_speichern.php?dozentid='+this.id+'&idrverfuegbar='+(idrverfuegbar ? 'J' : 'N'));
    if(!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    let txt=await response.text();
    if(!txt.startsWith('OK')) {
      console.log(response);
      alert(txt);
      return;
    }
    this.idrverfuegbar=idrverfuegbar;
    for(let w of Planung.wochen) {
      this.verfuegbarkeitenByKw[w.kw].dozentIdrverfuegbarGeaendert(this.idrverfuegbar);
    }
  }
  verfuegbarkeitGeaendert(verfuegbarkeit,obj) {
    verfuegbarkeit.uninstall();
    let neu=new PlanungDozentVerfuegbarkeit(this,obj);
    for(let kw of neu.kw) {
      let v=this.verfuegbarkeitenByKw[kw];
      if(v) {
        v.uninstall();
      }
      this.verfuegbarkeitenByKw[kw]=neu;
    }
    for(let kw of verfuegbarkeit.kw) {
      let v=this.verfuegbarkeitenByKw[kw];
      if(v==verfuegbarkeit) {
        let w=Planung.wochenByKw[kw];
        v=new PlanungDozentVerfuegbarkeit(this,{
          'id':0,
          'dozentid':this.id,
          'verfuegbar':this.idrverfuegbar ? 'Verfügbar' : 'Nicht verfügbar',
          'beginn':w.montag,
          'ende':w.freitag,
          'kw':[w.kw],
          'notiz':''
        });
        this.verfuegbarkeitenByKw[w.kw]=v;
        v.install();
      }
    }
    neu.install();
  }
  verfuegbarkeitGeloescht(verfuegbarkeit) {
    verfuegbarkeit.uninstall();
    for(let kw of verfuegbarkeit.kw) {
      let v=this.verfuegbarkeitenByKw[kw];
      if(v==verfuegbarkeit) {
        let w=Planung.wochenByKw[kw];
        v=new PlanungDozentVerfuegbarkeit(this,{
          'id':0,
          'dozentid':this.id,
          'verfuegbar':this.idrverfuegbar ? 'Verfügbar' : 'Nicht verfügbar',
          'beginn':w.montag,
          'ende':w.freitag,
          'kw':[w.kw],
          'notiz':''
        });
        this.verfuegbarkeitenByKw[w.kw]=v;
        v.install();
      }
    }
  }
}