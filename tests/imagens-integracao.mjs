// Exclusivo de uma cópia descartável do banco (ver Etapa 07 no README).
import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
import {readFileSync, readdirSync, existsSync} from 'node:fs';
import {resolve, join} from 'node:path';
import {randomBytes} from 'node:crypto';
const base = 'http://localhost/projeto/joille2';
const stamp = randomBytes(6).toString('hex');
const mysql = process.env.JOILLE_MYSQL;
function sql(statement) {
    return execFileSync(mysql,['--no-defaults','--host=127.0.0.1','-uroot','--raw','-Nse',statement,'joille'],{encoding:'utf8'}).trim();
}
const directory = resolve(sql('SELECT @@datadir'));
assert.equal(directory, resolve(process.env.JOILLE_TEST_DATADIR));
assert.match(directory, /joille-images-[a-f\d]+[\\/]db$/i);
const png = readFileSync(process.env.JOILLE_TEST_PNG);
const userEmail = 'galeria-' + stamp + '@example.invalid';
const password = randomBytes(16).toString('hex');
async function request(method, path, body, token, code=200) {
    const headers = token ? {Authorization: 'Bearer ' + token} : {};
    const r = await fetch(base+'/api'+path,{method,body,headers});
    const value = await r.json();
    assert.equal(r.status,code,method+' '+path+': '+value.message);
    assert.equal(value.type,code < 400 ? 'success' : 'error');
    // No envelope existente, uma lista vazia chega como data:null (ApiService a normaliza).
    return value.data ?? (code === 200 && method === "GET" && path.startsWith("/service-images/") ? [] : null);
}
function fields(data) { return new URLSearchParams(data); }
function images(files, name='images[]') {
    const form = new FormData();
    for (const file of files) form.append(name,new Blob([file],{type:'image/png'}),'cliente.png');
    return form;
}
const root = resolve('uploads/images');
function snapshot() {
    function walk(dir) {
        if (!existsSync(dir)) return [];
        return readdirSync(dir,{withFileTypes:true}).flatMap(x=>x.isDirectory()?walk(join(dir,x.name)):[join(dir,x.name)]);
    }
    return walk(root).sort();
}
let admin, user, uid, service, other;
const paths = new Set(), triggers = new Set();
const login = async(email,password,admin=false)=>(await request('POST','/users/'+(admin?'login-admin':'login'),fields({email,password}))).token;
try {
    admin = await login(process.env.JOILLE_ADMIN_EMAIL,process.env.JOILLE_ADMIN_PASSWORD,true);
    uid=(await request('POST','/users/register',fields({name:'Auditoria '+stamp,email:userEmail,password}),null,201)).id;
    user=await login(userEmail,password);
    const cat=(await request('GET','/categories'))[0].id;
    async function newService(suffix) {
        return (await request('POST','/services',fields({name:'Imagem '+stamp+suffix,categories_id:cat,description:'Teste isolado',value:15}),admin,201)).id;
    }
    service=await newService('A'); other=await newService('B');
    const gallery='/service-images/'+service;
    assert.deepEqual(await request('GET',gallery),[]);
    await request('POST',gallery,images([png]),null,401);
    await request('POST',gallery,images([png]),user,403);
    await request('POST','/users/photo',images([png],'photo'),null,401);
    await request('POST','/users/photo',new FormData(),user,400);
    await request('POST','/users/photo',images([Buffer.alloc(4194305)],'photo'),user,400);
    const profile=async()=> (await request('GET','/users/me',undefined,user)).photo;
    const first=images([png],'photo'); first.append('id','26');
    const photo=(await request('POST','/users/photo',first,user)).photo;
    paths.add(photo);
    assert.equal(await profile(),photo);
    assert.equal((await fetch(base+'/'+photo)).status,200);
    console.log('OK foto vinculada ao token e persistida');
    const old=photo;
    const saved=(await request('POST','/users/photo',images([png],'photo'),user)).photo;
    paths.add(saved);
    assert.notEqual(saved,old); assert.equal(existsSync(old),false); assert.ok(existsSync(saved));
    const before=snapshot();
    await request('POST',gallery,images([png,'texto com extensão png']),admin,400);
    assert.deepEqual(await request('GET',gallery),[]); assert.deepEqual(snapshot(),before);
    console.log('OK arquivo falso rejeitado; lote misto desfeito no banco e no disco');
    async function simulateFailure(table, operation, condition, run) {
        const trigger='audit_'+stamp+'_'+table;
        const column = table === 'users' ? 'name' : 'services_id'; // ambos são NOT NULL
        sql("CREATE TRIGGER "+trigger+" BEFORE "+operation+" ON "+table+" FOR EACH ROW SET NEW."+column+" = IF("+condition+",NULL,NEW."+column+")");
        triggers.add(trigger);
        try { await run(); } finally { sql('DROP TRIGGER '+trigger); triggers.delete(trigger); }
    }
    await simulateFailure('users','UPDATE','NEW.id='+Number(uid),async()=>{
        await request('POST','/users/photo',images([png],'photo'),user,500);
        assert.deepEqual(snapshot(),before); assert.equal(await profile(),saved);
    });
    await simulateFailure('service_images','INSERT','NEW.services_id='+Number(service),async()=>{
        await request('POST',gallery,images([png,png]),admin,500);
        assert.deepEqual(snapshot(),before); assert.deepEqual(await request('GET',gallery),[]);
    });
    console.log('OK falhas reais de persistência: foto anterior preservada, nenhum arquivo novo órfão');
    const uploaded=await request('POST',gallery,images([png,png]),admin,201);
    uploaded.forEach(x=>paths.add(x.path));
    const list=await request('GET',gallery);
    assert.equal(list.length,2); assert.equal(new Set(list.map(x=>x.path)).size,2);
    assert.equal(sql('SELECT COUNT(*) FROM service_images WHERE services_id='+Number(service)),'2');
    for(const x of list){assert.equal(Number(x.services_id),Number(service));assert.equal((await fetch(x.url)).status,200);}
    assert.deepEqual(await request('GET','/service-images/'+other),[]);
    await request('DELETE','/service-images/'+other+'/'+list[0].id,undefined,admin,404);
    await request('DELETE',gallery+'/'+list[0].id,undefined,admin);
    const remaining=await request('GET',gallery);
    assert.equal(remaining.length,1); assert.equal(remaining[0].id,list[1].id);
    assert.ok(!existsSync(list[0].path) && existsSync(list[1].path));
    await request('DELETE','/services/'+service,undefined,admin);
    assert.equal(sql('SELECT COUNT(*) FROM service_images WHERE services_id='+Number(service)),'0');
    assert.ok(!existsSync(list[1].path));
    console.log('OK duas imagens independentes, URLs públicas, exclusão por serviço e limpeza na inativação');
} finally {
    for (const t of triggers) sql('DROP TRIGGER '+t);
    for (const id of [service,other].filter(Boolean)) {
        if (admin) await request('GET','/service-images/'+id).then(xs=>xs.forEach(x=>paths.add(x.path)));
        sql('DELETE FROM services WHERE id='+Number(id)+" AND name LIKE 'Imagem "+stamp+"%'");
    }
    if (uid) sql('DELETE FROM users WHERE id='+Number(uid)+" AND email='"+userEmail+"'");
    const {unlinkSync} = await import('node:fs');
    for (const p of paths) if (/^uploads\/images\/[\w/-]+\.(png|jpg)$/.test(p) && existsSync(p)) unlinkSync(p);
}
