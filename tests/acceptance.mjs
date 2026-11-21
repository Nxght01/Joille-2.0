import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {readFileSync, readdirSync, existsSync, unlinkSync, writeFileSync, mkdirSync} from 'node:fs';
import {resolve, join} from 'node:path';
import {randomBytes} from 'node:crypto';
import RequestService from '../views/assets/_common/classes/services/RequestService.js';
import ServiceRequest from '../views/assets/_common/classes/entities/ServiceRequest.js';

const base = process.env.JOILLE_BASE_URL;
assert.equal(base, 'http://127.0.0.1:8086', 'Use somente o ambiente de teste isolado.');
const php = process.env.JOILLE_PHP ?? 'C:/wamp64/bin/php/php8.3.28/php.exe';
const db = (sql, params = []) => JSON.parse(execFileSync(php, ['tests/support/db.php'], {input:JSON.stringify({sql, params}), encoding:'utf8'}));
db('SELECT 1');
const run = randomBytes(6).toString('hex');
const evidence = [];
const created = {users:[], categories:[], services:[], questions:[], service_requests:[]};
const files = new Set();
const triggers = new Set();
const check = (label, condition) => { assert.ok(condition, label); evidence.push(label); console.log('PASS ' + label); };
async function request(method, path, body, token, expected = 200) {
    const headers = token ? {Authorization: 'Bearer ' + token} : {};
    if (body && !(body instanceof FormData)) headers['Content-Type'] = 'application/json';
    const response = await fetch(base + '/api' + path, {method, headers, body:body instanceof FormData ? body : body === undefined ? undefined : JSON.stringify(body)});
    const json = await response.json();
    check(`${method} ${path}: HTTP ${expected}`, response.status === expected && json.code === expected && json.type === (expected < 400 ? 'success' : 'error'));
    if (expected >= 400) assert.equal(json.data, null);
    return json.data;
}
const record = (table, row) => { created[table].push(Number(row.id)); return row; };
const password = 'Audit-' + run;
const jpeg = readFileSync('evidencias/etapa07/catalogo.jpg');
function upload(field = 'images[]', count = 1, content = jpeg) {
    const form = new FormData();
    for (let i=0; i<count; i++) form.append(field, new Blob([content], {type:'image/jpeg'}), 'imagem.jpg');
    return form;
}
function snapshot() {
    const walk = folder => !existsSync(folder) ? [] : readdirSync(folder, {withFileTypes:true}).flatMap(item => item.isDirectory() ? walk(join(folder,item.name)) : [resolve(folder,item.name)]);
    return walk('uploads/acceptance').sort();
}
function failWrites(table) {
    const name = 'audit_' + run + '_' + table;
    db(`CREATE TRIGGER ${name} BEFORE ${table === 'users' ? 'UPDATE' : 'INSERT'} ON ${table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Injected acceptance failure'`);
    triggers.add(name); return name;
}
function removeTrigger(name) { db('DROP TRIGGER ' + name); triggers.delete(name); }
try {
    const admin = await request('POST','/users/login-admin',{email:'admin@joille.com',password:'admin@123'});
    const a = record('users', await request('POST','/users/register',{name:'Auditoria A',email:`a-${run}@example.test`,password},null,201));
    const b = record('users', await request('POST','/users/register',{name:'Auditoria B',email:`b-${run}@example.test`,password},null,201));
    const userA = await request('POST','/users/login',{email:a.email,password});
    const userB = await request('POST','/users/login',{email:b.email,password});
    await request('GET','/requests',undefined,undefined,401);
    await request('GET','/requests',undefined,'invalid',401);
    await request('GET','/users/me',undefined,admin.token);
    const updated = {name:'Cliente alterado',email:`updated-${run}@example.test`,address:'Rua das Joias, 42',phone:'(51) 99999-0000'};
    await request('PUT','/users/update',updated,userA.token);
    const profile = await request('GET','/users/me',undefined,userA.token);
    check('Sessão permanece válida após alterar e-mail, com endereço e telefone persistidos', profile.email === updated.email && profile.address === updated.address && profile.phone === updated.phone);
    await request('PUT','/users/update',updated,userA.token);
    await request('POST','/users/register',{name:'  ',email:`blank-${run}@example.test`,password},null,400);
    await request('POST','/users/register-admin',{name:'Admin inválido',email:`bad-${run}@example.test`,password,type_id:9},admin.token,400);
    await request('DELETE','/users/admin/'+admin.id,undefined,admin.token,400);
    await request('PUT','/users/admin/'+admin.id,{name:admin.name,email:admin.email,type_id:2},admin.token,400);
    const category = record('categories',await request('POST','/categories',{name:'Audit '+run},admin.token,201));
    const payload = {name:'Serviço '+run,description:'Reparo e polimento de peças',categories_id:category.id,value:150.50};
    await request('POST','/services',{...payload,value:-1},admin.token,400);
    await request('POST','/services',{...payload,categories_id:99999999},admin.token,400);
    const service = record('services',await request('POST','/services',payload,admin.token,201));
    await request('PUT','/services/'+service.id,{value:'invalido'},admin.token,400);
    await request('PUT','/services/'+service.id,{value:150.50},admin.token);
    for (const entity of ['services','categories']) {
        await request('GET',`/${entity}/list/paginator/-1/10`,undefined,null,400);
        await request('GET',`/${entity}/list/paginator/1/101`,undefined,null,400);
    }
    const client = new RequestService(); client.setAuthToken(userA.token);
    const entity = new ServiceRequest({services_id:service.id,quantity:2,description:'Ajustar e polir duas alianças.'});
    const result = await client.create(entity);
    assert.equal(result.ok,true,result.message);
    const order = record('service_requests', result.entity);
    check('Serviço HTTP converte resposta da API em entidade e calcula o total', order instanceof ServiceRequest && order.total === 301);
    check('GET de solicitações do cliente devolve somente seus registros', (await client.list()).entities.every(item=>item.id === order.id));
    await request('GET',`/requests/${order.id}`,undefined,userB.token,404);
    await request('PUT',`/requests/${order.id}`,{description:'Tentativa de outro cliente',quantity:5},userB.token,404);
    await request('DELETE',`/requests/${order.id}`,undefined,userB.token,404);
    await request('PUT',`/requests/${order.id}`,{description:'Trocar o acabamento das alianças.',quantity:3,users_id:b.id,status:'completed',unit_price:0},userA.token);
    const persisted = await request('GET',`/requests/${order.id}`,undefined,userA.token);
    check('Servidor preserva dono, preço e estado contra campos forjados', persisted.users_id === a.id && Number(persisted.unit_price) === 150.5 && persisted.status === 'pending' && persisted.quantity === 3);
    await request('PUT',`/requests/${order.id}`,{status:'completed'},admin.token,409);
    await request('PUT',`/requests/${order.id}`,{status:'in_progress',admin_notes:'Peças recebidas pela oficina.'},admin.token);
    await request('DELETE',`/requests/${order.id}`,undefined,userA.token,409);
    await request('PUT',`/requests/${order.id}`,{status:'completed',admin_notes:'Atendimento concluído.'},admin.token);
    check('Cliente lê a conclusão persistida pelo administrador', (await client.find(order.id)).entity.status === 'completed');
    const cancel = record('service_requests', await request('POST','/requests',entity.toJSON(),userA.token,201));
    await request('DELETE','/requests/'+cancel.id,undefined,userA.token);
    check('Cancelamento preserva o histórico', (await request('GET','/requests/'+cancel.id,undefined,userA.token)).status === 'cancelled');
    await request('POST','/requests',entity.toJSON(),admin.token,403);
    const faq = record('questions',await request('POST','/faqs',{question:'Auditoria '+run,answer:'Resposta de teste.',types_id:1},admin.token,201));
    await request('PUT','/faqs/'+faq.id,{active:0},admin.token);
    await request('GET','/faqs/'+faq.id,undefined,null,404);
    await request('GET','/faqs/'+faq.id,undefined,admin.token);
    await request('PUT','/faqs/'+faq.id,{active:1},admin.token);
    await request('GET','/faqs/'+faq.id);
    const malformed = await fetch(base+'/api/users/register',{method:'POST',headers:{'Content-Type':'application/json'},body:'{broken'});
    check('JSON malformado retorna 400 padronizado', malformed.status === 400 && (await malformed.json()).data === null);
    await request('POST','/users/register',{name:{nested:true},email:a.email,password},null,400);
    const before = snapshot();
    await request('POST','/service-images/'+service.id,upload('images[]',1,Buffer.from('<?php fake image ?>')),admin.token,400);
    check('Imagem falsa não cria arquivo', JSON.stringify(snapshot()) === JSON.stringify(before));
    const gallery = await request('POST','/service-images/'+service.id,upload('images[]',2),admin.token,201);
    const images = await request('GET','/service-images/'+service.id);
    check('Duas imagens persistidas e independentes', images.length === 2 && images[0].id !== images[1].id);
    images.forEach(image=>files.add(image.path));
    for (const image of images) check('URL pública da imagem retorna 200', (await fetch(image.url)).status === 200);
    const imageTrigger = failWrites('service_images');
    const oldFiles = snapshot();
    await request('POST','/service-images/'+service.id,upload(),admin.token,500);
    check('Falha de banco desfaz arquivo novo e preserva a galeria', JSON.stringify(oldFiles) === JSON.stringify(snapshot()) && (await request('GET','/service-images/'+service.id)).length === 2);
    removeTrigger(imageTrigger);
    const photo = await request('POST','/users/photo',upload('photo'),userA.token);
    files.add(photo.photo);
    const userTrigger = failWrites('users');
    const oldPhotoFiles = snapshot();
    await request('POST','/users/photo',upload('photo'),userA.token,500);
    check('Falha de persistência da foto mantém foto antiga e remove nova', JSON.stringify(oldPhotoFiles) === JSON.stringify(snapshot()) && (await request('GET','/users/me',undefined,userA.token)).photo === photo.photo);
    removeTrigger(userTrigger);
    const forgedPhoto = upload('photo'); forgedPhoto.append('userId',b.id);
    const replaced = await request('POST','/users/photo',forgedPhoto,userA.token);
    files.add(replaced.photo);
    check('Foto vem da identidade do token e remove arquivo anterior após persistir', !existsSync(photo.photo) && !(await request('GET','/users/me',undefined,userB.token)).photo);
    await request('DELETE',`/service-images/${service.id}/${images[0].id}`,undefined,userA.token,403);
    await request('DELETE',`/service-images/${service.id}/${images[0].id}`,undefined,admin.token);
    check('Excluir uma imagem preserva a outra', (await request('GET','/service-images/'+service.id)).length === 1 && existsSync(images[1].path));
    await request('DELETE','/services/'+service.id,undefined,admin.token);
    check('Inativar serviço remove imagens do banco e do disco', !existsSync(images[1].path) && db('SELECT id FROM service_images WHERE services_id=?',[service.id]).length === 0);
    check('Paginação pública omite serviço inativo', !(await request('GET','/services/list/paginator/1/100')).data.some(item=>item.id === service.id));
    await request('GET','/services/'+service.id,undefined,null,404);
    await request('POST','/requests',entity.toJSON(),userA.token,400);
    await request('DELETE','/users/admin/'+b.id,undefined,admin.token);
    await request('GET','/users/me',undefined,userB.token,401);
    for (const path of ['/','/sobre','/contato','/faqs','/services','/login','/cadastro','/app/solicitacoes','/admin/perfil','/admin/solicitacoes']) {
        check('Página '+path+' renderiza sem erro PHP', (await fetch(base+path)).status === 200);
    }
} finally {
    for (const trigger of triggers) removeTrigger(trigger);
    for (const table of ['service_requests','questions','services','categories','users']) {
        if (table === 'services' && created.services.length) db(`DELETE FROM service_images WHERE services_id IN (${created.services.map(()=>'?')})`,created.services);
        if (created[table].length) db(`DELETE FROM ${table} WHERE id IN (${created[table].map(()=>'?')})`,created[table]);
    }
    for (const file of files) {
        const path = resolve(file);
        assert.ok(path.startsWith(resolve('uploads/acceptance') + '\\'));
        if (existsSync(path)) unlinkSync(path);
    }
}
mkdirSync('evidencias/auditoria', {recursive:true});
writeFileSync('evidencias/auditoria/aceite.json', JSON.stringify({date:'2026-11-20',passed:evidence.length,checks:evidence},null,2));
console.log(`${evidence.length} verificações de aceite passaram; registros criados removidos.`);
