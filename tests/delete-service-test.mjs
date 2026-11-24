import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import ServiceService from '../views/assets/_common/classes/services/ServiceService.js';

const base = process.env.JOILLE_BASE_URL;
assert.equal(base, 'http://127.0.0.1:8086', 'Use o ambiente isolado.');
const php = process.env.JOILLE_PHP ?? 'C:/wamp64/bin/php/php8.3.28/php.exe';
execFileSync(php, ['tests/support/verify-isolation.php']);
const ids = [];
async function call(path, options) {
    const response = await fetch(base + '/api' + path, options);
    return {status: response.status, ...await response.json()};
}
const login = await call('/users/login-admin', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email:'admin@joille.com',password:'admin@123'})});
assert.equal(login.code, 200);
const headers = {'Authorization':'Bearer '+login.data.token,'Content-Type':'application/json'};
const categories = await call('/categories', {headers});
const categoryId = categories.data.find(item => Number(item.active) === 1).id;
const service = new ServiceService(); service.setAuthToken(login.data.token);
try {
    for (let i = 0; i < 2; i++) {
        const created = await call('/services', {method:'POST',headers,body:JSON.stringify({name:'Teste DELETE '+Date.now()+' '+i,description:'Serviço temporário de regressão',categories_id:categoryId,value:10})});
        assert.equal(created.code, 201); ids.push(created.data.id);
    }
    const malformed = await call('/services/'+ids[0], {method:'DELETE',headers,body:'{broken'});
    assert.equal(malformed.code, 400, 'JSON malformado continua recusado');
    const removed = await service.remove(ids[0]);
    assert.equal(removed.ok, true, removed.message);
    const legacy = await call('/services/'+ids[1], {method:'DELETE',headers});
    assert.equal(legacy.code, 200, 'DELETE sem corpo aceita o cabeçalho do cliente antigo');
    for (const id of ids) {
        const saved = await call('/services/'+id, {headers});
        assert.equal(Number(saved.data.active), 0, 'Serviço foi inativado no banco');
    }
    console.log('PASS: exclusão pelo cliente real, DELETE vazio com JSON, persistência e rejeição de JSON inválido.');
} finally {
    for (const id of ids) execFileSync(php, ['tests/support/db.php'], {input:JSON.stringify({sql:'DELETE FROM services WHERE id=?',params:[id]})});
}
