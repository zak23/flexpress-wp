const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../wp-content/themes/flexpress/assets/js/main.js'), 'utf8');
const start = source.indexOf('    createPreviewPlayer: async function');
const end = source.indexOf('    removePreviewPlayer:', start);
assert.ok(start >= 0 && end > start);
const method = source.slice(start, end);

async function run(response) {
  let videos = 0, posted;
  const element = { append() {}, 0: { play: () => Promise.resolve() } };
  const $ = (tag) => { if (tag === '<video>') videos++; return element; };
  $.ajax = async (options) => {
    posted = options.data;
    if (response instanceof Error) throw response;
    return response;
  };
  const context = {
    $, FlexPressData: { bunnycdnUrl: 'media.example.invalid', libraryId: 'fixture', ajaxurl: '/ajax', nonce: 'fixture' },
    setTimeout() {}, console,
  };
  const player = vm.runInNewContext(`({${method}})`, context);
  const card = { data: () => 101, find: () => ({ length: 1, append() {} }), append() {} };
  await player.createPreviewPlayer(card, 'fixture-video');
  assert.equal(posted.post_id, '101');
  return videos;
}
(async () => {
  for (const denied of [
    { success: false },
    { success: true, data: { token: '', expires: 9999999999 } },
    { success: true, data: { token: 'a'.repeat(64), expires: 1 } },
    { success: true, data: { token: 'a'.repeat(64), expires: '9999999999' } },
    new Error('Synthetic network failure'),
  ]) assert.equal(await run(denied), 0, 'Denied signing request fell back to unsigned media');
  assert.equal(await run({ success: true, data: { token: 'a'.repeat(64), expires: Math.floor(Date.now() / 1000) + 300 } }), 1);
  console.log('Preview player tests passed (6 response scenarios): no unsigned fallback.');
})().catch((error) => { console.error(error); process.exitCode = 1; });
