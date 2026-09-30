/**
 * Verificação automatizada da Etapa 06 — POO clássica e prototípica.
 *
 *      node poo-test.mjs
 *
 * Exercita as classes de entidade, a herança dos serviços HTTP e o
 * compartilhamento de métodos entre objetos criados a partir do mesmo
 * protótipo. Não depende de DOM nem do servidor.
 */

import Entity from "./views/assets/_common/classes/entities/Entity.js";
import Service from "./views/assets/_common/classes/entities/Service.js";
import Category from "./views/assets/_common/classes/entities/Category.js";
import Faq from "./views/assets/_common/classes/entities/Faq.js";
import FaqType from "./views/assets/_common/classes/entities/FaqType.js";
import User from "./views/assets/_common/classes/entities/User.js";
import ServiceImage from "./views/assets/_common/classes/entities/ServiceImage.js";
import ContactMessage from "./views/assets/_common/classes/entities/ContactMessage.js";

import HttpClientBase from "./views/assets/_common/classes/HttpClientBase.js";
import ApiService from "./views/assets/_common/classes/services/ApiService.js";
import ServiceService from "./views/assets/_common/classes/services/ServiceService.js";
import CategoryService from "./views/assets/_common/classes/services/CategoryService.js";
import FaqService from "./views/assets/_common/classes/services/FaqService.js";
import FaqTypeService from "./views/assets/_common/classes/services/FaqTypeService.js";
import UserService from "./views/assets/_common/classes/services/UserService.js";
import ServiceImageService from "./views/assets/_common/classes/services/ServiceImageService.js";
import ContactService from "./views/assets/_common/classes/services/ContactService.js";
import PasswordService from "./views/assets/_common/classes/services/PasswordService.js";
import ReportService from "./views/assets/_common/classes/services/ReportService.js";

import { createToast, toastPrototype } from "./views/assets/_common/prototypes/Toast.js";
import FormController from "./views/assets/_common/prototypes/FormController.js";
import CrudController from "./views/assets/_common/prototypes/CrudController.js";
import ImageGallery from "./views/assets/_common/prototypes/ImageGallery.js";
import Accordion from "./views/assets/_common/prototypes/Accordion.js";
import { createChartPanel, chartPanelPrototype, DEFAULT_PALETTE }
    from "./views/assets/_common/prototypes/ChartPanel.js";
import { escapeHtml } from "./views/assets/_common/scripts/functions.js";

let pass = 0;
let fail = 0;

function ok(label, condition, detail = "") {
    if (condition) {
        pass++;
        console.log(`  ok   ${label}`);
    } else {
        fail++;
        console.log(`  FAIL ${label}${detail ? ` :: ${detail}` : ""}`);
    }
}

function throws(label, fn, expected) {
    try {
        fn();
        fail++;
        console.log(`  FAIL ${label} :: não lançou erro`);
    } catch (error) {
        const good = error instanceof expected;
        good ? pass++ : fail++;
        console.log(`  ${good ? "ok  " : "FAIL"} ${label} (${error.constructor.name}: ${error.message})`);
    }
}

// ---------------------------------------------------------------------------
console.log("\n=== 1. Encapsulamento (campos privados + validação nos setters) ===");

const service = new Service({
    id: 7, categories_id: 3, name: "  Polimento  ",
    description: "Polimento de joias", value: "1550", active: 1, category_name: "Avançado"
});

ok("setter apara espaços do nome", service.name === "Polimento", service.name);
ok("id convertido para número", service.id === 7 && typeof service.id === "number");
ok("value convertido para número", service.value === 1550 && typeof service.value === "number");
ok("campos privados não aparecem em Object.keys", Object.keys(service).length === 0, JSON.stringify(Object.keys(service)));
ok("campo privado inacessível de fora", service["#name"] === undefined);

throws("nome vazio é rejeitado", () => { service.name = "   "; }, TypeError);
throws("valor negativo é rejeitado", () => { service.value = -1; }, RangeError);
throws("id zero é rejeitado", () => { service.id = 0; }, RangeError);
throws("active fora de 0/1 é rejeitado", () => { service.active = 5; }, RangeError);
throws("categoria inválida é rejeitada", () => { service.categoriesId = "abc"; }, RangeError);
throws("e-mail inválido é rejeitado", () => new User({ name: "A", email: "nao-e-email" }), TypeError);
throws("senha curta é rejeitada", () => { new User({ name: "A", email: "a@b.com" }).password = "123"; }, RangeError);
throws("perfil inválido é rejeitado", () => new User({ name: "A", email: "a@b.com", type_id: 9 }), RangeError);

const user = new User({ id: 1, name: "Maria Souza", email: "maria@joille.com", type_id: 1, password: "senha12345" });
ok("senha não tem getter público", user.password === undefined);
ok("hasPassword indica que a senha foi definida", user.hasPassword === true);
ok("toSession() não expõe a senha", !("password" in user.toSession()));
ok("toJSON() envia a senha quando informada", user.toJSON().password === "senha12345");
ok("toSession() traz os dados mínimos", user.toSession().type_id === 1 && user.toSession().id === 1);

// ---------------------------------------------------------------------------
console.log("\n=== 2. Herança ===");

ok("Service herda de Entity", new Service({ name: "x", description: "y", value: 1, categories_id: 1 }) instanceof Entity);
ok("Category herda de Entity", new Category({ name: "Básico" }) instanceof Entity);
ok("Faq herda de Entity", new Faq({ types_id: 1, question: "q", answer: "a" }) instanceof Entity);
ok("FaqType herda de Entity", new FaqType({ description: "Geral" }) instanceof Entity);
ok("User herda de Entity", user instanceof Entity);

const serviceService = new ServiceService();
ok("ServiceService herda de ApiService", serviceService instanceof ApiService);
ok("ApiService herda de HttpClientBase", serviceService instanceof HttpClientBase);
ok("cadeia completa de herança",
    Object.getPrototypeOf(Object.getPrototypeOf(Object.getPrototypeOf(serviceService))) === HttpClientBase.prototype);

[["CategoryService", new CategoryService()], ["FaqService", new FaqService()],
 ["FaqTypeService", new FaqTypeService()], ["UserService", new UserService()]]
    .forEach(([name, instance]) => ok(`${name} herda de ApiService`, instance instanceof ApiService));

["list", "find", "create", "update", "remove"].forEach((method) => {
    ok(`ServiceService herda o método ${method}()`, typeof serviceService[method] === "function");
});

ok("UserService sobrescreve list() (rota /users/admin/list)",
    UserService.prototype.list !== ApiService.prototype.list);
ok("ServiceService NÃO sobrescreve list() (reaproveita a base)",
    Object.getPrototypeOf(ServiceService.prototype).list === ApiService.prototype.list);

// ---------------------------------------------------------------------------
console.log("\n=== 3. Conversão payload da API <-> objeto ===");

const fromApi = Service.fromApi({
    id: "11", categories_id: "2", name: "Gravação", description: "Gravação a laser",
    value: "250", active: "1", category_name: "Intermediário"
});

ok("fromApi() devolve a subclasse correta", fromApi instanceof Service);
ok("snake_case -> camelCase", fromApi.categoriesId === 2 && fromApi.categoryName === "Intermediário");
ok("strings da API viram números", fromApi.id === 11 && fromApi.value === 250);
ok("camelCase -> snake_case no toJSON()", fromApi.toJSON().categories_id === 2);
ok("toJSON() não vaza category_name (não é coluna)", !("category_name" in fromApi.toJSON()));
ok("formattedValue formata em BRL", fromApi.formattedValue.includes("250"), fromApi.formattedValue);

const collection = Category.collectionFromApi([{ id: 1, name: "Básico" }, { id: 2, name: "Avançado" }]);
ok("collectionFromApi() devolve entidades", collection.length === 2 && collection.every((c) => c instanceof Category));
ok("getter label usado nos selects", collection[0].label === "Básico");
ok("isNew identifica registro sem id", new Category({ name: "Nova" }).isNew === true);
ok("isNew é falso quando há id", collection[0].isNew === false);

// ---------------------------------------------------------------------------
console.log("\n=== 4. POO prototípica: métodos compartilhados ===");

const toastA = createToast();
const toastB = createToast({ duration: 8000, selector: "#toast-container" });

ok("createToast() usa Object.create", Object.getPrototypeOf(toastA) === toastPrototype);
ok("dois toasts compartilham o MESMO protótipo", Object.getPrototypeOf(toastA) === Object.getPrototypeOf(toastB));
ok("o método show é a MESMA função nos dois", toastA.show === toastB.show);
ok("show não é propriedade própria", !Object.hasOwn(toastA, "show"));
ok("estado é próprio de cada objeto", toastA.duration === 3000 && toastB.duration === 8000);
ok("Toast não é uma class", typeof toastPrototype === "object");

const fakeForm = { querySelector: () => null, reset: () => {}, addEventListener: () => {} };
const formA = new FormController(fakeForm, { rules: { name: { required: true } } });
const formB = new FormController(fakeForm, { busyLabel: "Enviando..." });

ok("FormController é função construtora, não class",
    Object.getOwnPropertyDescriptor(FormController, "prototype").writable === true);
ok("dois FormControllers compartilham o protótipo",
    Object.getPrototypeOf(formA) === Object.getPrototypeOf(formB));
ok("validate é a MESMA função nos dois", formA.validate === formB.validate);
ok("validate vive no protótipo, não na instância", !Object.hasOwn(formA, "validate"));
ok("rules é estado próprio", Object.hasOwn(formA, "rules") && formB.busyLabel === "Enviando...");
throws("FormController exige 'new'", () => FormController(fakeForm), TypeError);
throws("FormController exige um form", () => new FormController(null), TypeError);

const crudA = Object.create(CrudController.prototype);
const crudB = Object.create(CrudController.prototype);
ok("dois CrudControllers compartilham o protótipo",
    Object.getPrototypeOf(crudA) === Object.getPrototypeOf(crudB));
ok("submit é a MESMA função nos dois", crudA.submit === crudB.submit);
["load", "render", "create", "edit", "remove", "submit", "bind", "openModal", "closeModal", "setFilter"]
    .forEach((m) => ok(`CrudController.prototype.${m} existe`, typeof CrudController.prototype[m] === "function"));

// ---------------------------------------------------------------------------
console.log("\n=== 5. Composição (CrudController reúne colaboradores) ===");

const composed = new CrudController({
    service: serviceService,
    form: formA,
    toast: toastA,
    container: null,
    modal: null,
    template: () => "",
    idAttribute: "service-id",
    toEntity: (values) => new Service(values)
});

ok("compõe um ApiService", composed.service instanceof ApiService);
ok("compõe um FormController", Object.getPrototypeOf(composed.form) === FormController.prototype);
ok("compõe um Toast prototípico", Object.getPrototypeOf(composed.toast) === toastPrototype);
ok("CrudController NÃO herda dos colaboradores", !(composed instanceof ApiService));
ok("labels recebem valores padrão", composed.labels.empty.length > 0);
throws("CrudController exige 'new'", () => CrudController({}), TypeError);

// ---------------------------------------------------------------------------
console.log("\n=== 6. Toast: tratamento das respostas da API ===");

const logged = [];
const quietToast = createToast({ logger: { error: (...args) => logged.push(args) } });

ok("sucesso -> verde", quietToast.variantFor({ type: "success", code: 200 }) === "success");
ok("400 -> amarelo (o usuário pode corrigir)", quietToast.variantFor({ type: "error", code: 400 }) === "warning");
ok("401 -> vermelho", quietToast.variantFor({ type: "error", code: 401 }) === "error");
ok("403 -> vermelho", quietToast.variantFor({ type: "error", code: 403 }) === "error");
ok("500 -> vermelho", quietToast.variantFor({ type: "error", code: 500 }) === "error");
ok("grafia 'success' é a usada", TOASTS_INCLUDE_SUCCESS());

function TOASTS_INCLUDE_SUCCESS() {
    return quietToast.variantFor({ type: "success" }) === "success";
}

// DOM mínimo para exercitar show()/fromResponse() fora do navegador
function fakeElement() {
    return {
        className: "",
        textContent: "",
        classList: { add() {}, remove() {} },
        setAttribute() {},
        appendChild() {},
        remove() {}
    };
}

globalThis.document = {
    body: fakeElement(),
    querySelector: () => fakeElement(),
    createElement: () => fakeElement(),
    getElementById: () => fakeElement()
};
globalThis.requestAnimationFrame = (fn) => fn();

// Resposta inesperada não pode ser silenciada
quietToast.fromResponse(undefined);
ok("resposta indefinida é registrada no console", logged.length === 1);
quietToast.fromResponse({ code: 200, data: { id: 1 } });
ok("resposta sem 'message' é registrada no console", logged.length === 2);
ok("a data inteira nunca vira mensagem",
    !logged.some((args) => typeof args[1] === "string" && args[1].includes("{")));

// ---------------------------------------------------------------------------
console.log("\n=== 7. Serviços não manipulam HTML ===");

const serviceSource = [
    ApiService, ServiceService, CategoryService, FaqService, FaqTypeService, UserService,
    ServiceImageService, ContactService, PasswordService, ReportService
].map((klass) => klass.toString()).join("\n");

["document.", "innerHTML", "querySelector", "createElement"].forEach((forbidden) => {
    ok(`nenhum serviço usa ${forbidden}`, !serviceSource.includes(forbidden));
});

const entitySource = [Entity, Service, Category, Faq, FaqType, User, ServiceImage, ContactMessage]
    .map((k) => k.toString()).join("\n");
["document.", "innerHTML", "querySelector"].forEach((forbidden) => {
    ok(`nenhuma entidade usa ${forbidden}`, !entitySource.includes(forbidden));
});

// ---------------------------------------------------------------------------
console.log("\n=== 8. Etapa 07: entidades das imagens e das mensagens ===");

const image = new ServiceImage({
    id: 4, services_id: 9, path: "uploads/images/2026/08/foto.png", caption: "  Anel pronto  ", cover: 1
});

ok("ServiceImage herda de Entity", image instanceof Entity);
ok("url é derivada do path quando a API não a envia",
    image.url.endsWith("/uploads/images/2026/08/foto.png"), image.url);
ok("legenda é aparada", image.caption === "Anel pronto");
ok("isCover reflete cover = 1", image.isCover === true);
ok("alt usa a legenda", image.alt === "Anel pronto");
ok("alt tem texto padrão sem legenda",
    new ServiceImage({ path: "a/b.png" }).alt === "Imagem do serviço");
ok("cover ausente vira 0", new ServiceImage({ path: "a/b.png" }).cover === 0);
throws("path vazio é recusado", () => new ServiceImage({ path: "   " }), TypeError);
throws("services_id inválido é recusado",
    () => new ServiceImage({ path: "a/b.png", services_id: 0 }), RangeError);

const contact = new ContactMessage({
    id: 2, name: "  Ana  ", email: "ana@exemplo.com", subject: "  Orçamento  ",
    message: "Gostaria de um orçamento para banho de ouro.", answered: 0,
    created_at: "2026-08-24 14:30:00"
});

ok("ContactMessage herda de Entity", contact instanceof Entity);
ok("nome e assunto são aparados", contact.name === "Ana" && contact.subject === "Orçamento");
ok("assunto vazio vira null", new ContactMessage({
    name: "A", email: "a@b.co", message: "mensagem longa o bastante", subject: "  "
}).subject === null);
ok("isAnswered reflete answered = 0", contact.isAnswered === false);
ok("data é formatada em pt-BR", /\d{2}\/\d{2}\/\d{4}/.test(contact.formattedDate), contact.formattedDate);
throws("e-mail inválido é recusado", () => new ContactMessage({
    name: "A", email: "nao-e-email", message: "mensagem longa o bastante"
}), TypeError);
throws("mensagem curta é recusada", () => new ContactMessage({
    name: "A", email: "a@b.co", message: "curta"
}), RangeError);

// A capa do serviço chega junto da listagem, para a área pública
const withCover = new Service({
    id: 1, categories_id: 1, name: "Banho de ouro", description: "18k", value: "200",
    cover_image: "uploads/images/2026/08/capa.png", images_count: 3
});

ok("Service expõe a capa como URL absoluta",
    withCover.coverImageUrl.endsWith("/uploads/images/2026/08/capa.png"));
ok("Service conta as imagens", withCover.imagesCount === 3);
ok("hasImages é verdadeiro com capa", withCover.hasImages === true);
ok("hasImages é falso sem imagem",
    new Service({ categories_id: 1, name: "X", description: "Y", value: 1 }).hasImages === false);
ok("coverImageUrl é null sem capa",
    new Service({ categories_id: 1, name: "X", description: "Y", value: 1 }).coverImageUrl === null);
ok("a capa não é enviada no payload de gravação",
    !("cover_image" in withCover.toJSON()) && !("images_count" in withCover.toJSON()));

// ---------------------------------------------------------------------------
console.log("\n=== 9. Serviços sem entidade (relatórios e senha) ===");

const reports = new ReportService();
const passwords = new PasswordService();

ok("ReportService herda de ApiService", reports instanceof ApiService);
ok("PasswordService herda de ApiService", passwords instanceof ApiService);
ok("recurso sem entidade tem entityClass null", reports.entityClass === null);
ok("toResult sem entidade devolve só o envelope", (() => {
    const result = reports.toResult({ type: "success", code: 200, message: "ok", data: { totals: {} } });
    return result.ok === true && !("entities" in result);
})());
ok("toPdfResult reconhece um Blob", (() => {
    const blob = new Blob(["%PDF-"], { type: "application/pdf" });
    const result = reports.toPdfResult(blob);
    return result.ok === true && result.blob === blob;
})());
ok("toPdfResult repassa o erro da API", (() => {
    const result = reports.toPdfResult({ type: "error", code: 401, message: "sem token" });
    return result.ok === false && result.blob === null && result.code === 401;
})());

// ---------------------------------------------------------------------------
console.log("\n=== 10. Etapa 08: ImageGallery e ChartPanel são prototípicos ===");

const galleryA = new ImageGallery({ container: fakeElement(), service: {}, toast: quietToast });
const galleryB = new ImageGallery({ container: fakeElement(), service: {}, toast: quietToast });

ok("ImageGallery não é uma class", !ImageGallery.toString().startsWith("class"));
throws("ImageGallery exige new", () => ImageGallery({ container: fakeElement() }), TypeError);
throws("ImageGallery exige um contêiner", () => new ImageGallery({}), TypeError);
ok("duas galerias compartilham o mesmo protótipo",
    Object.getPrototypeOf(galleryA) === Object.getPrototypeOf(galleryB));
ok("os métodos não são recriados a cada new", galleryA.load === galleryB.load);
ok("o estado é próprio de cada objeto",
    Object.hasOwn(galleryA, "images") && !Object.hasOwn(ImageGallery.prototype, "images"));
ok("readOnly é respeitado", galleryA.readOnly === false &&
    new ImageGallery({ container: fakeElement(), readOnly: true }).readOnly === true);

// Texto vindo do usuário nunca pode escapar do template de innerHTML
ok("escapeHtml neutraliza tags", escapeHtml("<script>alert(1)</script>")
    === "&lt;script&gt;alert(1)&lt;/script&gt;");
ok("escapeHtml neutraliza aspas de atributo",
    escapeHtml('" onerror="alert(1)') === "&quot; onerror=&quot;alert(1)");
ok("escapeHtml trata null", escapeHtml(null) === "" && escapeHtml(undefined) === "");
ok("escapeHtml escapa o & antes das entidades", escapeHtml("&lt;") === "&amp;lt;");

const panelA = createChartPanel();
const panelB = createChartPanel();

// Object.create sobre um objeto literal: não há função construtora envolvida,
// então o construtor herdado é o próprio Object
ok("ChartPanel usa Object.create, não class", panelA.constructor === Object);
ok("dois painéis compartilham o mesmo protótipo",
    Object.getPrototypeOf(panelA) === Object.getPrototypeOf(panelB) &&
    Object.getPrototypeOf(panelA) === chartPanelPrototype);
ok("os métodos moram no protótipo", panelA.bar === panelB.bar);
ok("cada painel tem seu próprio mapa de gráficos", panelA.charts !== panelB.charts);
ok("a paleta cicla", panelA.color(0) === panelA.color(DEFAULT_PALETTE.length));
// Logger silencioso: o aviso esperado não deve poluir a saída da suíte
const quietPanel = createChartPanel({ logger: { warn() {} } });
ok("sem Chart.js o painel não quebra",
    quietPanel.isAvailable() === false && quietPanel.draw("x", {}) === null);

// ---------------------------------------------------------------------------
console.log("\n=== 11. Accordion (sanfona do FAQ) é prototípico e acessível ===");

/** DOM mínimo com a marcação que o Accordion espera. */
function stubAccordion(count = 3) {
    const items = [];

    for (let index = 0; index < count; index++) {
        const panel = {
            style: {},
            scrollHeight: 100 + index,
            classList: {
                set: new Set(),
                add(name) { this.set.add(name); },
                remove(name) { this.set.delete(name); },
                contains(name) { return this.set.has(name); }
            }
        };

        const item = { querySelector: () => panel };

        const trigger = {
            attrs: { "aria-expanded": "false" },
            getAttribute(name) { return this.attrs[name] ?? null; },
            setAttribute(name, value) { this.attrs[name] = String(value); },
            closest: () => item
        };

        items.push({ trigger, panel });
    }

    const container = {
        listeners: {},
        addEventListener(type, fn) { (this.listeners[type] ??= []).push(fn); },
        querySelectorAll: () => items.map((entry) => entry.trigger),
        contains: () => true
    };

    return { container, items };
}

globalThis.window = { addEventListener() {} };

const { container: accContainer, items: accItems } = stubAccordion(3);
const accordion = new Accordion({ container: accContainer });

ok("Accordion não é uma class", !Accordion.toString().startsWith("class"));
throws("Accordion exige new", () => Accordion({ container: accContainer }), TypeError);
throws("Accordion exige um contêiner", () => new Accordion({}), TypeError);
ok("dois accordions compartilham o protótipo",
    Object.getPrototypeOf(accordion) === Object.getPrototypeOf(new Accordion({ container: accContainer })));
ok("os métodos moram no protótipo",
    accordion.toggle === Accordion.prototype.toggle && !Object.hasOwn(accordion, "toggle"));

// Abrir: aria-expanded vira "true", a classe entra e a altura vem do scrollHeight
accordion.open(accItems[0].trigger);
ok("abrir marca aria-expanded=true", accItems[0].trigger.getAttribute("aria-expanded") === "true");
ok("abrir adiciona a classe do painel", accItems[0].panel.classList.contains("open"));
ok("abrir usa a altura real do conteúdo", accItems[0].panel.style.maxHeight === "100px",
    accItems[0].panel.style.maxHeight);

// Fechar volta tudo ao estado inicial
accordion.close(accItems[0].trigger);
ok("fechar marca aria-expanded=false", accItems[0].trigger.getAttribute("aria-expanded") === "false");
ok("fechar remove a classe", !accItems[0].panel.classList.contains("open"));
ok("fechar zera a altura inline", accItems[0].panel.style.maxHeight === "");

// toggle alterna
accordion.toggle(accItems[1].trigger);
ok("toggle abre o painel fechado", accordion.isOpen(accItems[1].trigger));
accordion.toggle(accItems[1].trigger);
ok("toggle fecha o painel aberto", !accordion.isOpen(accItems[1].trigger));

// Vários abertos ao mesmo tempo é o comportamento padrão (igual ao acme)
accordion.open(accItems[0].trigger);
accordion.open(accItems[2].trigger);
ok("por padrão mantém vários painéis abertos",
    accordion.isOpen(accItems[0].trigger) && accordion.isOpen(accItems[2].trigger));

accordion.closeAll();
ok("closeAll fecha todos",
    accItems.every((entry) => entry.trigger.getAttribute("aria-expanded") === "false"));

// refresh remede os painéis abertos (o texto pode ter refluído)
accordion.open(accItems[0].trigger);
accItems[0].panel.scrollHeight = 250;
accordion.refresh();
ok("refresh remede o painel aberto", accItems[0].panel.style.maxHeight === "250px");

// Modo single: abrir um fecha os outros
const { container: singleContainer, items: singleItems } = stubAccordion(3);
const singleAccordion = new Accordion({ container: singleContainer, single: true });

singleAccordion.open(singleItems[0].trigger);
singleAccordion.open(singleItems[1].trigger);
ok("single: abrir um fecha o anterior",
    !singleAccordion.isOpen(singleItems[0].trigger) && singleAccordion.isOpen(singleItems[1].trigger));

// A delegação é um único listener no contêiner, e não um por pergunta
const { container: bindContainer, items: bindItems } = stubAccordion(2);
const bound = new Accordion({ container: bindContainer }).bind();
ok("bind registra um único listener de clique", bindContainer.listeners.click.length === 1);

// Um clique em um filho do botão precisa abrir a pergunta (closest resolve o alvo)
bindContainer.listeners.click[0]({ target: { closest: () => bindItems[0].trigger } });
ok("clique delegado abre a pergunta certa", bound.isOpen(bindItems[0].trigger));

// Clique fora de qualquer gatilho não pode explodir
bindContainer.listeners.click[0]({ target: { closest: () => null } });
ok("clique fora de um gatilho é ignorado", bound.isOpen(bindItems[0].trigger));

// ---------------------------------------------------------------------------
console.log(`\n===== ${pass} passaram, ${fail} falharam =====`);
process.exit(fail > 0 ? 1 : 0);
