const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
class Element {
    constructor(id, classes = []) { this.id=id; this.attrs={}; this.listeners={}; this.textContent=''; this.hidden=false; this.classes=new Set(classes); this.classList={contains:x=>this.classes.has(x),toggle:(x,on)=>{const v=on??!this.classes.has(x);v?this.classes.add(x):this.classes.delete(x);return v;}}; }
    setAttribute(k,v){this.attrs[k]=v;} getAttribute(k){return this.attrs[k];} hasAttribute(k){return k in this.attrs;}
    addEventListener(k,f){this.listeners[k]=f;} contains(t){return t===this;} focus(){this.focused=true;}
}
const work=new Element('', ['is-selected']), stock=new Element(''), direct=new Element('');
const sections=[work,stock,direct];
sections.forEach((e,i)=>{e.textContent=['Work','Stock','Cashup'][i];e.attrs['aria-controls']='panel-'+i;if(i<2)e.attrs['data-has-submenu']='true';});
const panels=sections.map((_,i)=>new Element('panel-'+i));
const sidebar=new Element('portal-sidebar'), toggle=new Element('');
const listeners={}; let mobile=false;
const document={querySelectorAll:s=>s==='.portal-section'?sections:s==='.portal-panel'?panels:[],getElementById:()=>sidebar,querySelector:()=>toggle,addEventListener:(k,f)=>listeners[k]=f};
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../js/navigation.js'),'utf8'),{document,window:{matchMedia:()=>({matches:mobile})}});
assert.equal(panels[0].hidden,false);assert.equal(panels[1].hidden,true);
const click=(element,extra={})=>{const event={button:0,preventDefault(){this.prevented=true;},...extra};element.listeners.click(event);return event;};
assert.equal(click(stock).prevented,true);assert.equal(panels[1].hidden,false);assert.equal(panels[0].hidden,true);assert.equal(stock.attrs['aria-expanded'],'true');
assert.equal(click(work,{ctrlKey:true}).prevented,undefined);assert.equal(panels[1].hidden,false);
assert.equal(click(direct).prevented,undefined);
mobile=true;click(work);assert.equal(sidebar.classList.contains('is-open'),true);assert.equal(toggle.attrs['aria-expanded'],'true');
listeners.keydown({key:'Escape'});assert.equal(sidebar.classList.contains('is-open'),false);assert.equal(toggle.focused,true);
toggle.listeners.click();assert.equal(sidebar.classList.contains('is-open'),true);
console.log('PASS: initial selection, section switching, direct links, modifier clicks, mobile drawer, Escape and focus.');
