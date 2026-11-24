import { showEmpty, showListError, showLoading, serviceUnavailableMessage } from "../scripts/ui.js";

/**
 * CrudController — POO PROTOTÍPICA (função construtora + prototype).
 *
 * É também o melhor exemplo de COMPOSIÇÃO do projeto: o controller não herda
 * de ninguém, ele *reúne* colaboradores prontos —
 *
 *      service : ApiService   (classe, faz HTTP e converte em entidades)
 *      form    : FormController (protótipo, serializa e valida o formulário)
 *      toast   : toastPrototype (protótipo, exibe as mensagens da API)
 *
 * As telas administrativas de Serviços, Categorias, FAQs e Usuários criam
 * quatro objetos a partir DESTE mesmo protótipo; os métodos existem uma única
 * vez na memória.
 */
export function CrudController({
    service,
    form,
    toast,
    container,
    modal,
    modalTitle,
    template,
    idAttribute,
    itemClass = "service-item",
    labels = {},
    toEntity,
    loader = null,
    onAuthFailure = null,
    onOpen = null,
    onClose = null
} = {}) {
    if (!(this instanceof CrudController)) {
        throw new TypeError("Use 'new CrudController({...})'");
    }

    this.service = service;
    this.form = form;
    this.toast = toast;
    this.container = container;
    this.modal = modal;
    this.modalTitle = modalTitle;
    this.template = template;
    this.idAttribute = idAttribute;
    this.itemClass = itemClass;
    this.toEntity = toEntity;
    this.loader = loader ?? (() => service.list());
    this.onAuthFailure = onAuthFailure;

    // Ganchos opcionais para quem precisa reagir à abertura/fechamento do modal.
    // `onOpen` recebe a entidade em edição, ou null quando é um novo registro —
    // é o que permite a tela de Serviços trocar a galeria de imagens exibida.
    this.onOpen = onOpen;
    this.onClose = onClose;

    this.labels = {
        loading: "Carregando...",
        empty: "Nenhum registro cadastrado.",
        createTitle: "Novo registro",
        editTitle: "Editar registro",
        confirmDelete: "Tem certeza que deseja excluir este registro?",
        ...labels
    };

    // Estado próprio de cada controller
    this.items = [];
    this.filterFn = () => true;
}

// --- modal -------------------------------------------------------------------

CrudController.prototype.openModal = function openModal(title, entity = null) {
    if (this.modalTitle) {
        this.modalTitle.textContent = title;
    }

    this.modal.style.display = "flex";
    this.onOpen?.(entity);

    return this;
};

CrudController.prototype.closeModal = function closeModal() {
    this.modal.style.display = "none";
    this.form.clear();
    this.onClose?.();

    return this;
};

// --- listagem ----------------------------------------------------------------

CrudController.prototype.render = function render() {
    const visible = this.items.filter((item) => item.isActive !== false && this.filterFn(item));

    if (visible.length === 0) {
        showEmpty(this.container, this.labels.empty);
        return this;
    }

    this.container.innerHTML = ``;

    visible.forEach((entity) => {
        const article = document.createElement("article");
        article.classList.add(this.itemClass);
        article.setAttribute(this.idAttribute, entity.id);
        article.innerHTML = this.template(entity);
        this.container.appendChild(article);
    });

    return this;
};

CrudController.prototype.setFilter = function setFilter(filterFn) {
    this.filterFn = filterFn ?? (() => true);
    return this.render();
};

CrudController.prototype.load = async function load() {
    showLoading(this.container, this.labels.loading);

    try {
        const result = await this.loader();

        if (!result.ok) {
            this.handleFailure(result);
            showListError(this.container, result.message);
            return this;
        }

        this.items = result.entities;
        return this.render();
    } catch (error) {
        console.error("Erro na requisição:", error);
        showListError(this.container, serviceUnavailableMessage());
        return this;
    }
};

// --- ações -------------------------------------------------------------------

CrudController.prototype.create = function create() {
    this.form.clear();
    return this.openModal(this.labels.createTitle);
};

CrudController.prototype.edit = async function edit(id) {
    try {
        const result = await this.service.find(id);

        if (!result.ok) {
            this.handleFailure(result);
            this.toast.fromResponse(result);
            return this;
        }

        this.form.fill(result.entity);
        return this.openModal(this.labels.editTitle, result.entity);
    } catch (error) {
        console.error("Erro na requisição:", error);
        this.toast.error(serviceUnavailableMessage());
        return this;
    }
};

CrudController.prototype.remove = async function remove(id) {
    if (!confirm(this.labels.confirmDelete)) {
        return this;
    }

    try {
        const result = await this.service.remove(id);

        this.toast.fromResponse(result);

        if (!result.ok) {
            this.handleFailure(result);
            return this;
        }

        // A exclusão foi confirmada pela API: retire a linha sem depender de
        // outra requisição. A listagem também omite inativos ao recarregar.
        this.items = this.items.filter((item) => String(item.id) !== String(id));
        return this.render();
    } catch (error) {
        console.error("Erro na requisição:", error);
        this.toast.error(serviceUnavailableMessage());
        return this;
    }
};

CrudController.prototype.submit = async function submit() {
    const errors = this.form.validate();

    if (errors.length > 0) {
        this.toast.warning(errors[0]);
        return this;
    }

    let entity;

    // As validações dos setters da entidade viram mensagens para o usuário
    try {
        entity = this.toEntity(this.form.values());
    } catch (error) {
        this.toast.warning(error.message);
        return this;
    }

    this.form.setBusy(true);

    try {
        const result = entity.isNew
            ? await this.service.create(entity)
            : await this.service.update(entity.id, entity);

        this.toast.fromResponse(result);

        if (!result.ok) {
            this.handleFailure(result);
            return this;
        }

        this.closeModal();
        return this.load();
    } catch (error) {
        console.error("Erro na requisição:", error);
        this.toast.error(serviceUnavailableMessage());
        return this;
    } finally {
        this.form.setBusy(false);
    }
};

CrudController.prototype.handleFailure = function handleFailure(result) {
    if ([401, 403].includes(Number(result?.code))) {
        this.onAuthFailure?.(result);
    }

    return this;
};

// --- ligação com o DOM -------------------------------------------------------

CrudController.prototype.bind = function bind({ newButton, cancelButton } = {}) {
    newButton?.addEventListener("click", () => this.create());
    cancelButton?.addEventListener("click", () => this.closeModal());

    this.container.addEventListener("click", (event) => {
        const article = event.target.closest("article");

        if (!article) {
            return;
        }

        const id = article.getAttribute(this.idAttribute);

        if (event.target.classList.contains("btn-edit")) {
            this.edit(id);
        }

        if (event.target.classList.contains("btn-delete")) {
            this.remove(id);
        }
    });

    this.form.form.addEventListener("submit", (event) => {
        event.preventDefault();
        this.submit();
    });

    return this;
};

export default CrudController;
