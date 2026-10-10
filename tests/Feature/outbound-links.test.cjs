const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const context = {URL, URLSearchParams, window:{outboundSource:'opengraph.works'}, location:{origin:'https://opengraph.works',hostname:'opengraph.works'}};
vm.runInNewContext(fs.readFileSync('public/js/outbound-links.js','utf8'), context);
test('preview links carry fixed attribution without losing encoded queries, duplicate keys or fragments', () => {
    const original = 'https://example.com/?token=a%20b&key=1&key=2&utm_source=old#part';
    const result = context.outboundUrl(original, 'preview_discord');
    assert.ok(result.includes('token=a%20b&key=1&key=2'));
    assert.equal(new URL(result).hash, '#part');
    assert.equal(new URL(result).searchParams.getAll('utm_source').length, 1);
    assert.equal(new URL(result).searchParams.get('utm_source'), 'opengraph.works');
    assert.equal(new URL(result).searchParams.get('utm_content'), 'preview_discord');
    assert.equal(original, 'https://example.com/?token=a%20b&key=1&key=2&utm_source=old#part');
});
test('internal, non-http and malformed URLs remain unchanged', () => {
    for (const value of ['https://opengraph.works/de/', 'mailto:kontakt@opengraph.works', 'invalid']) {
        assert.equal(context.outboundUrl(value, 'footer_project'), value);
    }
});
