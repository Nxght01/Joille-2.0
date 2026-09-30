import assert from "node:assert/strict";
import ImageGallery from "./views/assets/_common/prototypes/ImageGallery.js";
import { validateImages } from "./views/assets/_common/scripts/image-validation.js";

const photo = { type: "image/png", size: 1024 };
assert.equal(validateImages([photo]), null);
assert.ok(validateImages([]));
assert.ok(validateImages([{ ...photo, size: 4194305 }]));
assert.ok(validateImages([{ ...photo, type: "image/gif" }]));
assert.ok(validateImages([photo], 0));

const pending = [];
const gallery = new ImageGallery({
    container: { innerHTML: "", querySelectorAll: () => [] },
    input: { value: "old.png" },
    service: { listByService: () => new Promise(resolve => pending.push(resolve)) }
});
gallery.render = function () { this.displayed = this.images; return this; };
const first = gallery.setServiceId(1);
const second = gallery.setServiceId(2);
pending[1]({ ok: true, entities: ["second"] });
await second;
pending[0]({ ok: true, entities: ["first"] });
await first;
assert.deepEqual(gallery.displayed, ["second"]);
assert.equal(gallery.input.value, "");
const third = gallery.setServiceId(3);
await gallery.setServiceId(null);
pending[2]({ ok: true, entities: ["closed"] });
await third;
assert.deepEqual(gallery.images, []);
assert.equal(gallery.input.disabled, true);

let finish;
let calls = 0;
let changes = 0;
gallery.serviceId = 1;
gallery.service.setCover = () => { calls++; return new Promise(resolve => { finish = resolve; }); };
gallery.toast = { fromResponse() {} };
gallery.onChange = () => { changes++; };
const mutation = gallery.setCover(9);
await gallery.setCover(9);
assert.equal(calls, 1);
await gallery.setServiceId(null);
finish({ ok: true });
await mutation;
assert.equal(changes, 1);
assert.equal(gallery.busy, false);
assert.equal(gallery.input.disabled, true);
console.log("Testes de imagens passaram: validação, respostas fora de ordem, fechamento e envio duplicado.");
