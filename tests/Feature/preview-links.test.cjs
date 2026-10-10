const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/js/preview-links.js', 'utf8');
function setup(hash = '', clipboardFails = false) {
    const elements = {};
    const listeners = {};
    let scans = 0;
    let copied = null;
    for (const selector of ['#form', '#url', '#submit', '.link-tools', '#copy-check-link', '#share-status', '#share-link-fallback', '#preview-bookmarklet']) {
        elements[selector] = {value:'', disabled:false, hidden:true, dataset:{checkBase:'https://opengraph.works/en/'}, handlers:{}, addEventListener(name, callback){this.handlers[name] = callback;}, focus(){}, select(){this.selected = true;}};
    }
    elements['#form'].requestSubmit = () => {scans++;};
    const context = {URL, URLSearchParams, JSON, location:{hash}, document:{querySelector:selector => elements[selector]}, navigator:{clipboard:{async writeText(value){if (clipboardFails) throw Error('denied'); copied = value;}}}, window:{addEventListener(name, callback){listeners[name] = callback;}}};
    vm.runInNewContext(source, context);
    return {elements, listeners, context, scans:() => scans, copied:() => copied};
}
test('shared links preserve nested query strings and fragments and request a check once', () => {
    const target = 'https://example.com/path?q=a+b&other=two#section';
    const app = setup('#url=' + encodeURIComponent(target));
    assert.equal(app.elements['#url'].value, target);
    assert.equal(app.scans(), 1);
    assert.equal(app.elements['#copy-check-link'].disabled, false);
});
test('absent, unsafe, malformed and oversized targets do not trigger scans', () => {
    for (const hash of ['', '#url=javascript%3Aalert(1)', '#url=%ZZ', '#url=' + encodeURIComponent('https://example.com/' + 'a'.repeat(2048))]) {
        assert.equal(setup(hash).scans(), 0);
    }
});
test('copy uses a fragment and preserves the selected language', async () => {
    const app = setup('#url=https%3A%2F%2Fexample.com%2F');
    await app.elements['#copy-check-link'].handlers.click();
    const link = new URL(app.copied());
    assert.equal(link.search, '');
    assert.equal(link.pathname, '/en/');
    assert.equal(new URLSearchParams(link.hash.slice(1)).get('url'), 'https://example.com/');
    assert.equal(link.searchParams.has('url'), false);
});
test('denied clipboard access offers a selectable link', async () => {
    const app = setup('#url=https%3A%2F%2Fexample.com%2F', true);
    await app.elements['#copy-check-link'].handlers.click();
    assert.equal(app.elements['#share-link-fallback'].hidden, false);
    assert.equal(app.elements['#share-link-fallback'].selected, true);
});
test('dropping a link runs the existing form and busy scans are not submitted again', () => {
    const app = setup();
    const event = {preventDefault(){}, dataTransfer:{getData(type){return type === 'text/uri-list' ? '#comment\r\nhttps://example.com/\r\n' : '';}}};
    app.elements['#url'].handlers.drop(event);
    assert.equal(app.scans(), 1);
    app.elements['#submit'].disabled = true;
    app.elements['#url'].handlers.drop(event);
    assert.equal(app.scans(), 1);
});
test('bookmarklet opens the current page as an encoded target without opener or referrer', () => {
    const app = setup();
    let opened;
    const code = decodeURIComponent(app.elements['#preview-bookmarklet'].href.slice('javascript:'.length));
    vm.runInNewContext(code, {location:{protocol:'https:',href:'https://example.com/?q=1#part'},window:{open(...args){opened = args;}}});
    assert.equal(new URLSearchParams(new URL(opened[0]).hash.slice(1)).get('url'), 'https://example.com/?q=1#part');
    assert.equal(opened[2], 'noopener,noreferrer');
});
