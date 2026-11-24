import assert from 'node:assert/strict';
import test from 'node:test';
import CrudController from '../views/assets/_common/prototypes/CrudController.js';
import Service from '../views/assets/_common/classes/entities/Service.js';
import Category from '../views/assets/_common/classes/entities/Category.js';
import Faq from '../views/assets/_common/classes/entities/Faq.js';
import User from '../views/assets/_common/classes/entities/User.js';

test('Exclusão desaparece nas quatro listagens, inclusive após buscar e recarregar', async t => {
    const originalDocument = globalThis.document, originalConfirm = globalThis.confirm;
    t.after(() => { globalThis.document = originalDocument; globalThis.confirm = originalConfirm; });
    globalThis.document = {createElement: () => ({classList:{add(){}},setAttribute(key,value){this[key]=value;}})};
    globalThis.confirm = () => true;
    for (const Entity of [Service, Category, Faq, User]) {
        const make = (id, active) => new Entity({id, active, name:'Registro '+id, description:'Descrição', question:'Pergunta?',answer:'Resposta',email:'teste@example.test'});
        let records = [make(1,1),make(2,'0'),make(3,1)], calls = 0, loads = 0, allowed = true;
        const container = {children:[],set innerHTML(value){this.children=[];this.html=value;},appendChild(child){this.children.push(child);}};
        const crud = new CrudController({container, idAttribute:'record-id',template:row=>String(row.id),toast:{fromResponse(){},error(){}},
            service:{list:async()=>{loads++; return {ok:true,entities:records};}, remove:async id=>{calls++; if(!allowed)return {ok:false,code:403}; records=records.map(row=>row.id===Number(id)?make(row.id,0):row);return {ok:true};}}});
        const shown = () => container.children.map(row=>row['record-id']);
        await crud.load(); assert.deepEqual(shown(),[1,3],Entity.name);
        crud.setFilter(row=>row.id===2); assert.deepEqual(shown(),[]);
        crud.setFilter(null);
        globalThis.confirm=()=>false;
        await crud.remove('1'); assert.equal(calls,0); assert.deepEqual(shown(),[1,3]);
        globalThis.confirm=()=>true;
        allowed=false;
        await crud.remove('1'); assert.deepEqual(shown(),[1,3],'Falha da API preserva a linha');
        allowed=true;
        await crud.remove('1'); assert.equal(loads,1,'Não depende de nova requisição'); assert.deepEqual(shown(),[3]);
        await crud.load(); assert.deepEqual(shown(),[3],'Recarregar não ressuscita excluídos');
        await crud.remove('3'); assert.deepEqual(shown(),[]); assert.match(container.html,/Nenhum registro/);
    }
});
