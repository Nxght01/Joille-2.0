import assert from 'node:assert/strict';
import { writeFileSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import Category from '../views/assets/_common/classes/entities/Category.js';
import Service from '../views/assets/_common/classes/entities/Service.js';
import Faq from '../views/assets/_common/classes/entities/Faq.js';
import User from '../views/assets/_common/classes/entities/User.js';
import CategoryService from '../views/assets/_common/classes/services/CategoryService.js';
import ServiceService from '../views/assets/_common/classes/services/ServiceService.js';
import FaqService from '../views/assets/_common/classes/services/FaqService.js';
import FaqTypeService from '../views/assets/_common/classes/services/FaqTypeService.js';
import UserService from '../views/assets/_common/classes/services/UserService.js';

const run = 'AUDIT06-' + Date.now();
const created = [];
const evidence = [];
const users = new UserService();
const categories = new CategoryService();
const services = new ServiceService();
const faqs = new FaqService();
function check(label, condition) {
    assert.ok(condition, label);
    evidence.push(label);
    console.log('PASS ' + label);
}
function record(resource, result) {
    assert.equal(result.ok, true, result.message);
    assert.ok(result.entity?.id);
    created.push({resource, id: result.entity.id});
    return result.entity;
}
try {
    const auth = await users.loginAdmin({email: process.env.JOILLE_ADMIN_EMAIL, password: process.env.JOILLE_ADMIN_PASSWORD});
    check('Admin POST login returns User and token', auth.ok && auth.entity instanceof User && !!auth.entity.token);
    [users, categories, services, faqs].forEach(client => client.setAuthToken(auth.entity.token));
    const category = record('categories', await categories.create(new Category({name: run + ' categoria'})));
    check('Category POST persists entity', (await categories.find(category.id)).entity.name === category.name);
    category.name = run + ' categoria editada';
    check('Category PUT succeeds', (await categories.update(category.id, category)).ok);
    check('Category GET reads edited data', (await categories.find(category.id)).entity.name === category.name);
    check('Category list includes created entity', (await categories.list()).entities.some(x => x.id === category.id));

    const service = record('services', await services.create(new Service({name: run + ' servico', categories_id: category.id, description: 'Auditoria isolada da Etapa 06', value: 0})));
    check('Service POST and GET accept zero price', (await services.find(service.id)).entity.value === 0);
    service.name = run + ' servico editado'; service.value = 225.75;
    check('Service PUT succeeds', (await services.update(service.id, service)).ok);
    const persisted = (await services.find(service.id)).entity;
    check('Service GET reads edited value and name', persisted.value === 225.75 && persisted.name === service.name);
    service.value = 0;
    check('Service PUT accepts zero price', (await services.update(service.id, service)).ok);
    check('Service GET confirms price changed back to zero', (await services.find(service.id)).entity.value === 0);
    check('Service category list contains entity', (await services.listByCategory(category.id)).entities.some(x => x.id === service.id));
    const blocked = await categories.remove(category.id);
    check('Category with active service cannot be deleted', !blocked.ok && Number(blocked.code) === 400);

    const types = await new FaqTypeService().list();
    assert.ok(types.entities.length);
    const faq = record('questions', await faqs.create(new Faq({types_id: types.entities[0].id, question: run + ' pergunta?', answer: 'Resposta de auditoria.'})));
    check('FAQ POST and GET persist entity', (await faqs.find(faq.id)).entity.question === faq.question);
    faq.answer = 'Resposta editada.';
    check('FAQ PUT succeeds', (await faqs.update(faq.id, faq)).ok);
    check('FAQ GET reads edited answer', (await faqs.find(faq.id)).entity.answer === faq.answer);
    check('FAQ list includes entity', (await faqs.list()).entities.some(x => x.id === faq.id));

    const email = run.toLowerCase() + '@example.invalid';
    const password = 'Local-audit-06-only';
    const registered = record('users', await users.register(new User({name: run + ' cliente', email, password})));
    check('Public registration returns client User', registered instanceof User && registered.typeId === 2);
    const customer = new UserService();
    const login = await customer.login({email, password});
    check('Customer POST login succeeds', login.ok && !!login.entity.token);
    customer.setAuthToken(login.entity.token);
    check('Authenticated profile loads', (await customer.me()).entity.id === registered.id);
    const profile = await customer.updateProfile(new User({name: run + ' cliente editado', email}));
    check('Profile PUT succeeds', profile.ok);
    check('Profile GET reads edited name', (await customer.me()).entity.name === run + ' cliente editado');
    const clientCategories = new CategoryService();
    clientCategories.setAuthToken(login.entity.token);
    const forbidden = await clientCategories.create({name:run+' forbidden'});
    check('Client cannot write admin categories', !forbidden.ok && Number(forbidden.code) === 403);

    for (const [label, client, entity] of [['FAQ',faqs,faq],['Service',services,service],['Category',categories,category],['User',users,registered]]) {
        check(label + ' DELETE succeeds', (await client.remove(entity.id)).ok);
        check(label + ' deletion persisted as inactive', (await client.find(entity.id)).entity.active === 0);
    }
    check('Public service list omits deleted record', !(await services.list()).entities.some(x => x.id === service.id));
    check('Public category list omits deleted record', !(await categories.list()).entities.some(x => x.id === category.id));
    check('Public FAQ list omits deleted record', !(await faqs.list()).entities.some(x => x.id === faq.id));
} catch (error) {
    evidence.push('FAIL: ' + error.message);
    console.error(error);
    process.exitCode = 1;
} finally {
    const cleanup = spawnSync(process.env.JOILLE_PHP ?? 'C:/wamp64/bin/php/php8.3.28/php.exe', [fileURLToPath(new URL('./etapa06-audit-cleanup.php', import.meta.url))], {
        input: JSON.stringify({run, created}), encoding:'utf8'
    });
    const clean = cleanup.status === 0;
    evidence.push(clean ? 'PASS exact-ID database cleanup' : 'FAIL database cleanup: ' + cleanup.stderr + cleanup.stdout);
    if (!clean) process.exitCode = 1;
    console.log(clean ? 'PASS exact-ID database cleanup' : evidence.at(-1));
    writeFileSync(new URL('../SE2-Etapa06-live-results.json', import.meta.url), JSON.stringify({date:new Date().toISOString(), run, created, evidence, success:!process.exitCode},null,2));
}
