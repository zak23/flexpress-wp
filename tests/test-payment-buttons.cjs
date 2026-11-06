// Offline regression test: no browser, network, or WordPress data.
const fs = require('fs');
const vm = require('vm');
const assert = require('node:assert/strict');
const path = require('node:path');
const root = process.env.FLEXPRESS_TEST_ROOT || path.resolve(__dirname, '..');
const source = fs.readFileSync(root + '/wp-content/themes/flexpress/assets/js/main.js','utf8');
const calls=[];
const jq=(target)=> typeof target==='object' && target.data ? target : {ready(){},on(){}};
jq.ajax=(options)=>calls.push(options.data);
const context={window:{},document:{},jQuery:jq,console:{log(){}},FlexPressData:{isLoggedIn:true,ajaxurl:'/fake-ajax',nonce:'synthetic'},alert(message){throw Error(message);},confirm(){return false;}};
vm.runInNewContext(source,context);
function button(values){return {data(key){return values[key]},text(){return this},prop(){return this},addClass(){return this},css(){return this}};}
context.window.FlexPressUnlock.handlePurchaseClick({preventDefault(){},currentTarget:button({'extras-id':102,'price':20,'original-price':20})});
assert.equal(calls[0].extras_id,102);assert.equal(calls[0].action,'flexpress_create_ppv_purchase');
context.window.FlexPressUnlock.handlePurchaseClick({preventDefault(){},currentTarget:button({'episode-id':101,'price':25,'original-price':25})});
assert.equal(calls[1].episode_id,101);assert.equal(calls[1].action,'flexpress_create_ppv_purchase');
console.log('Episode and Extras button routing passed (4 assertions).');
