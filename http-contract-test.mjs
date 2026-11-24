/** Run: node --test http-contract-test.mjs. No server or database writes. */
import test from "node:test";
import assert from "node:assert/strict";
import Service from "./views/assets/_common/classes/entities/Service.js";
import Category from "./views/assets/_common/classes/entities/Category.js";
import Faq from "./views/assets/_common/classes/entities/Faq.js";
import ServiceService from "./views/assets/_common/classes/services/ServiceService.js";
import CategoryService from "./views/assets/_common/classes/services/CategoryService.js";
import FaqService from "./views/assets/_common/classes/services/FaqService.js";
import UserService from "./views/assets/_common/classes/services/UserService.js";
import ContactService from "./views/assets/_common/classes/services/ContactService.js";
import { createToast } from "./views/assets/_common/prototypes/Toast.js";
import { API_BASE_URL } from "./views/assets/_common/scripts/config.js";

function respond(t, payload, status = 200) {
    return t.mock.method(globalThis, "fetch", async () => new Response(JSON.stringify(payload), {
        status, headers: { "Content-Type": "application/json" }
    }));
}

for (const status of ['sent', 'captured', 'failed']) {
    test(`Contato: confirmação ${status} não exige uma mensagem completa`, async t => {
        const acknowledgement = {id:9,name:'Teste Joille',email:'teste@example.test',subject:'Teste',mail_status:status,mail_delivered:status==='sent',inbox_url:status==='captured'?'/storage/mail/':null};
        respond(t, {code:201,type:'success',message:'Contato registrado.',data:acknowledgement}, 201);
        const result = await new ContactService().create({name:'Teste Joille',email:'teste@example.test',subject:'Teste',message:'Mensagem de teste do formulário.'});
        assert.equal(result.ok,true);
        assert.deepEqual(result.data,acknowledgement);
        assert.equal(result.message,'Contato registrado.');
        assert.equal(result.entity,undefined);
    });
}

for (const [Client, Entity, resource, record] of [
    [ServiceService, Service, "/services", { id: 7, categories_id: 2, name: "Polimento", description: "Joias", value: "150.50" }],
    [CategoryService, Category, "/categories", { id: 7, name: "Reparos" }],
    [FaqService, Faq, "/faqs", { id: 7, types_id: 1, question: "Qual o prazo?", answer: "Cinco dias." }]
]) {
    test(`${Client.name}: CRUD uses real route formats, entities and authentication`, async (t) => {
        const client = new Client();
        client.setAuthToken("test-token");
        const fetchMock = respond(t, { type: "success", code: 200, message: "OK", data: [record] });
        const list = await client.list();
        assert.ok(list.entities[0] instanceof Entity);
        assert.equal(list.entities[0].id, 7);
        fetchMock.mock.mockImplementation(async () => Response.json({ type: "success", code: 200, message: "OK", data: record }));
        assert.ok((await client.find(7)).entity instanceof Entity);
        const entity = new Entity(record);
        await client.create(entity);
        await client.update(7, entity);
        fetchMock.mock.mockImplementation(async () => Response.json({ type: "success", code: 200, message: "Inativado" }));
        const removed = await client.remove(7);
        assert.equal(removed.ok, true);
        assert.equal(removed.entity, null);
        const calls = fetchMock.mock.calls.map(({ arguments: args }) => args);
        assert.equal(calls[4][1].headers['Content-Type'], undefined, 'DELETE sem corpo não anuncia JSON');
        assert.equal(calls[4][1].body, undefined);
        assert.deepEqual(calls.map(([url, config]) => [url, config.method]), [
            [API_BASE_URL + resource, "GET"], [API_BASE_URL + resource + "/7", "GET"],
            [API_BASE_URL + resource, "POST"], [API_BASE_URL + resource + "/7", "PUT"],
            [API_BASE_URL + resource + "/7", "DELETE"]
        ]);
        assert.ok(calls.every(([, config]) => config.headers.Authorization === "Bearer test-token"));
        assert.ok(calls[2][1].body instanceof FormData);
        assert.equal(calls[2][1].headers["Content-Type"], undefined);
        assert.equal(calls[3][1].headers["Content-Type"], "application/x-www-form-urlencoded");
        assert.equal(new URLSearchParams(calls[3][1].body).get("id"), "7");
        assert.equal(calls[2][1].body.get(resource === "/faqs" ? "question" : "name"), record.question ?? record.name);
        client.clearAuthToken();
        await client.list();
        assert.equal(fetchMock.mock.calls.at(-1).arguments[1].headers.Authorization, undefined);
    });
}

test("payload conversion works without browser globals and preserves FormData", () => {
    assert.equal(typeof HTMLFormElement, "undefined");
    const client = new CategoryService();
    assert.equal(client.toPayload({ name: "Reparos" }).get("name"), "Reparos");
    const form = new FormData();
    assert.equal(client.toPayload(form), form);
    assert.equal(client.toPayload(new Category({ name: "Nova" })).has("id"), false);
});

test("user login, registration and profile use the backend POST/PUT routes", async (t) => {
    const client = new UserService();
    const fetchMock = respond(t, { type: "success", code: 200, message: "OK", data: {
        id: 1, name: "Ana", email: "ana@example.com", type_id: 1, token: "test-token"
    } });
    const input = { email: "ana@example.com", password: "example123" };
    assert.equal((await client.login(input)).entity.token, "test-token");
    await client.loginAdmin(input);
    await client.register({ ...input, name: "Ana" });
    await client.updateProfile({ name: "Ana Maria" });
    assert.deepEqual(fetchMock.mock.calls.map(({ arguments: [url, config] }) => [url, config.method]), [
        [API_BASE_URL + "/users/login", "POST"], [API_BASE_URL + "/users/login-admin", "POST"],
        [API_BASE_URL + "/users/register", "POST"], [API_BASE_URL + "/users/update", "PUT"]
    ]);
});

test("paginated responses become entities and keep pagination metadata", async (t) => {
    respond(t, { type: "success", data: { page: 2, total: 11, data: [{ id: 1, categories_id: 1, name: "X", description: "Y", value: 1 }] } });
    const result = await new ServiceService().listPaginated(2, 10);
    assert.equal(result.data.total, 11);
    assert.ok(result.entities[0] instanceof Service);
});

test("API errors stay errors without creating invalid entities", async (t) => {
    respond(t, { type: "error", code: 401, message: "Sem acesso" }, 401);
    const result = await new CategoryService().list();
    assert.equal(result.ok, false);
    assert.equal(result.code, 401);
    assert.deepEqual(result.entities, []);
});

test("network failures and malformed JSON are not silently swallowed", async (t) => {
    const fetchMock = t.mock.method(globalThis, "fetch", async () => { throw new Error("offline"); });
    await assert.rejects(new CategoryService().list(), /offline/);
    fetchMock.mock.mockImplementation(async () => new Response("invalid", { headers: { "Content-Type": "application/json" } }));
    await assert.rejects(new CategoryService().list(), /Request failed/);
});

test("toast supports all three types, with or without data, and logs unexpected responses", () => {
    const logged = [];
    const toast = createToast({ logger: { error: (...args) => logged.push(args) } });
    toast.show = (message, type) => ({ message, type });
    for (const type of ["success", "warning", "error"]) {
        for (const extra of [{}, { data: { secret: "not a message" } }]) {
            assert.deepEqual(toast.fromResponse({ type, message: "Mensagem", ...extra }), { type, message: "Mensagem" });
        }
    }
    assert.equal(toast.variantFor({ type: "error", code: 400 }), "warning");
    assert.equal(toast.fromResponse(undefined).type, "error");
    assert.equal(toast.fromResponse({ type: "success", data: {} }).type, "error");
    assert.equal(logged.length, 2);
});
