export class PlanungObject {
  static planung=null;
  
  static ladeAlle(klasse,json) {
    if(!klasse.alle) {
      klasse.alle=[];
      klasse.byId=[];
    }
    for(let obj of json) {
      if(!klasse.byId[obj.id]) {
        let o=new klasse(obj);
        klasse.alle.push(o);
        klasse.byId[o.id]=o;
      }
    }
  }
  static ladeEines(klasse,obj) {
    if(klasse.byId[obj.id]) {
      return klasse.byId[obj.id];
    }
    let o=new klasse(obj);
    klasse.alle.push(o);
    klasse.byId[o.id]=o;
    return o;
  }
  
  constructor(obj) {
    for(let k in obj) {
      this[k]=obj[k];
    }
  }
  makeThText() {
    return this.makeText();
  }
  makeText() {
    throw 'abstract method call';
  }
  makeGeplantesDiv() {
    let res=document.createElement('div');
    res.innerText=this.makeText();
    return res;
  }
  makeOption(parentSelect,lang=false) {
    let o=document.createElement('option');
    o.value=this.id;
    o.innerText=lang ? this.makeThText().replace('\n',' ') : this.makeText();
    parentSelect.appendChild(o);
    return o;
  }
}