import { PlanungObject } from './PlanungObject.js';

export class PlanungRaum extends PlanungObject {
  static alleGefunden=false;
  static async findeAlle(ort) {
    if(PlanungRaum.alleGefunden) return;
    const response = await fetch('raeume_finden.php?ort='+encodeURIComponent(ort));
    if (!response.ok) {
      throw new Error(`Response status: ${response.status}`);
    }
    const json = await response.json();
    PlanungObject.ladeAlle(PlanungRaum,json);
    PlanungRaum.alleGefunden=true;
  }
  
  constructor(obj) {
    super(obj);
  }
  makeText() {
    return this.tuer+' ('+this.anzahlPlaetze+' Plätze)';
  }
}