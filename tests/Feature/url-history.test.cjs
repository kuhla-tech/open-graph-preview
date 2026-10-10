const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/js/url-history.js','utf8');
function setup(store = new Map(), blocked = false) {
    function element(){return {value:'',checked:true,disabled:false,children:[],handlers:{},addEventListener(name,fn){this.handlers[name]=fn;},replaceChildren(){this.children=[];},append(...items){this.children.push(...items);},setAttribute(){},dispatchEvent(){}};}
    const elements = {};
    for(const name of ['#history-enabled','#history-list','#recent-url-suggestions','#clear-url-history','#history-status','#url','#form','#submit']) elements[name]=element();
    let scans=0;
    elements['#form'].requestSubmit=()=>scans++;
    const handlers={};
    vm.runInNewContext(source,{URL,Set,JSON,Event:class{},document:{querySelector:name=>elements[name],createElement:element,addEventListener(name,fn){handlers[name]=fn;}},window:{addEventListener(){}},localStorage:{getItem(key){if(blocked)throw Error('denied');return store.get(key)??null;},setItem(key,value){if(blocked)throw Error('denied');store.set(key,value);},removeItem(key){if(blocked)throw Error('denied');store.delete(key);}}});
    return {elements,store,add:url=>handlers['og:check-started']({detail:{url}}),scans:()=>scans};
}
test('history survives reload, is bounded and keeps repeated URLs at the top',()=>{
    const app=setup();
    for(let i=0;i<25;i++)app.add('https://example.com/'+i);
    app.add('https://example.com/10');
    const values=JSON.parse(app.store.get('og-url-history'));
    assert.equal(values.length,20);assert.equal(values[0],'https://example.com/10');
    const reloaded=setup(app.store);
    assert.equal(reloaded.elements['#history-list'].children.length,20);
    assert.equal(reloaded.scans(),0);
    reloaded.elements['#history-list'].children[0].children[0].handlers.click();
    assert.equal(reloaded.elements['#url'].value,'https://example.com/10');assert.equal(reloaded.scans(),1);
});
test('individual deletion, clearing and disabling remove stored history',()=>{
    const app=setup();app.add('https://example.com/one');app.add('https://example.com/two');
    app.elements['#history-list'].children[0].children[1].handlers.click();
    assert.deepEqual(JSON.parse(app.store.get('og-url-history')),['https://example.com/one']);
    app.elements['#clear-url-history'].handlers.click();assert.equal(app.store.has('og-url-history'),false);
    app.add('https://example.com/three');app.elements['#history-enabled'].checked=false;app.elements['#history-enabled'].handlers.change();
    assert.equal(app.store.has('og-url-history'),false);app.add('https://example.com/four');assert.equal(app.store.has('og-url-history'),false);
    assert.equal(setup(app.store).elements['#history-enabled'].checked,false);
});
test('unsafe URLs and credentials are not persisted; denied storage does not break checks',()=>{
    const app=setup();for(const url of ['javascript:alert(1)','https://user:secret@example.com/','invalid'])app.add(url);
    assert.equal(app.store.has('og-url-history'),false);
    const blocked=setup(new Map(),true);blocked.add('https://example.com/');assert.equal(blocked.elements['#history-list'].children.length,1);
    assert.match(blocked.elements['#history-status'].textContent,/nicht verfügbar/);
});
