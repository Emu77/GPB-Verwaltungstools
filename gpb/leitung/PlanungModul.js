import { PlanungObject } from './PlanungObject.js';

export class PlanungModul extends PlanungObject {
  static alle=[];
  static byId={};
  
  static async finde(suchtext,kurs) {
    const response = await fetch('module_finden.php?titel='+encodeURIComponent(suchtext)
      +'&berufids='+encodeURIComponent(JSON.stringify(kurs.klassen.map((k)=>k.berufid)))
      +'&klassenids='+encodeURIComponent(JSON.stringify(kurs.klassen.map((k)=>k.id)))
      +'&dozentenids='+encodeURIComponent(JSON.stringify(kurs.dozentenids)));
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    const json = await response.json();
    PlanungObject.ladeAlle(PlanungModul,json);
    let module=[];
    for(let obj of json) {
      module.push(PlanungModul.byId[obj.id]);
    }
    return module;
  }
  
  constructor(obj) {
    super(obj);
  }
  makeText() {
    return (this.kuerzel || this.titel)+' ('+(this.schon==null ? '' : this.schon+' Wo schon geplant von ')+this.dauer+' Wo)';
  }
}