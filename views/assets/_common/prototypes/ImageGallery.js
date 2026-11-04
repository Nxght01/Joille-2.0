import { bindImagePreview } from "../scripts/image-preview.js";
import { showEmpty, showListError, showLoading, serviceUnavailableMessage } from "../scripts/ui.js";
import { escapeHtml } from "../scripts/functions.js";
import { validateImages } from "../scripts/image-validation.js";

/**
 * ImageGallery — POO PROTOTÍPICA (função construtora + prototype).
 *
 * Gerencia as N imagens de um registro da entidade principal: carrega a
 * galeria, envia novos arquivos, define a capa e exclui.
 *
 * Como CrudController, é COMPOSIÇÃO e não herança — a galeria reúne:
 *
 *      service : ServiceImageService  (classe, faz HTTP e converte em entidades)
 *      toast   : toastPrototype       (protótipo, exibe as mensagens da API)
 *
 * Os métodos moram uma única vez em `ImageGallery.prototype`; o que é próprio
 * de cada galeria é o serviço, o contêiner, o input de arquivos e o id atual.
 */
export function ImageGallery({
    service,
    toast,
    container,
    input = null,
    uploadButton = null,
    preview = null,
    counter = null,
    labels = {},
    onAuthFailure = null,
    readOnly = false,
    onChange = null
} = {}) {
    if (!(this instanceof ImageGallery)) {
        throw new TypeError("Use 'new ImageGallery({...})'");
    }

    if (!container) {
        throw new TypeError("ImageGallery exige um contêiner");
    }

    this.service = service;
    this.toast = toast;
    this.container = container;
    this.input = input;
    this.uploadButton = uploadButton;
    this.counter = counter;
    this.onAuthFailure = onAuthFailure;
    this.readOnly = readOnly;
    this.onChange = onChange;
    this.busy = false;
    this.revision = 0;

    this.labels = {
        loading: "Carregando imagens...",
        empty: "Nenhuma imagem enviada ainda.",
        noSelection: "Escolha ao menos uma imagem para enviar.",
        confirmDelete: "Tem certeza que deseja excluir esta imagem?",
        ...labels
    };

    // Estado próprio de cada galeria
    this.serviceId = null;
    this.images = [];
    this.clearPreview = preview && input ? bindImagePreview(input, preview,
        files => validateImages(files, 10 - this.images.length), message => toast.warning(message)) : () => {};
}

/** Aponta a galeria para outro registro e recarrega (null apenas limpa a tela). */
ImageGallery.prototype.setServiceId = async function setServiceId(serviceId) {
    this.revision++;
    this.clearPreview();
    if (this.input) this.input.value = "";
    this.serviceId = serviceId === null || serviceId === undefined || serviceId === ""
        ? null
        : Number(serviceId);

    this.images = [];

    if (this.serviceId === null) {
        this.container.innerHTML = "";
        this.updateCounter();
        this.setEnabled(false);
        return this;
    }

    this.setEnabled(true);
    return this.load();
};

ImageGallery.prototype.setEnabled = function setEnabled(enabled) {
    enabled = enabled && !this.busy && !this.readOnly && this.serviceId !== null;
    if (this.input) {
        this.input.disabled = !enabled;
    }

    if (this.uploadButton) {
        this.uploadButton.disabled = !enabled;
    }
    this.container.querySelectorAll?.("button").forEach((button) => {
        button.disabled = !enabled || (button.classList.contains("btn-cover") &&
            button.closest(".is-cover") !== null);
    });

    return this;
};

ImageGallery.prototype.updateCounter = function updateCounter() {
    if (this.counter) {
        this.counter.textContent = String(this.images.length);
    }

    return this;
};

ImageGallery.prototype.load = async function load() {
    if (this.serviceId === null) {
        return this;
    }

    const revision = ++this.revision;
    showLoading(this.container, this.labels.loading);

    try {
        const result = await this.service.listByService(this.serviceId);
        if (revision !== this.revision) return this;

        if (!result.ok) {
            this.handleFailure(result);
            showListError(this.container, result.message);
            return this;
        }

        this.images = result.entities;
        return this.render();
    } catch (error) {
        if (revision !== this.revision) return this;
        console.error("Erro ao carregar imagens:", error);
        showListError(this.container, serviceUnavailableMessage());
        return this;
    }
};

ImageGallery.prototype.render = function render() {
    this.updateCounter();

    if (this.images.length === 0) {
        showEmpty(this.container, this.labels.empty);
        return this;
    }

    this.container.innerHTML = "";

    this.images.forEach((image) => {
        const figure = document.createElement("figure");
        figure.className = `gallery-item${image.isCover ? " is-cover" : ""}`;
        figure.setAttribute("image-id", image.id);

        const actions = this.readOnly
            ? ""
            : `<div class="gallery-actions">
                   <button type="button" class="btn btn-cover" ${image.isCover ? "disabled" : ""}>
                       ${image.isCover ? "Capa" : "Definir capa"}
                   </button>
                   <button type="button" class="btn btn-delete-image">Excluir</button>
               </div>`;

        // A legenda é digitada pelo administrador; escapada para não escapar do atributo
        figure.innerHTML = `
            <img src="${encodeURI(image.url)}" alt="${escapeHtml(image.alt)}" loading="lazy">
            ${image.isCover ? '<span class="gallery-badge">capa</span>' : ""}
            ${actions}
        `;

        this.container.appendChild(figure);
    });

    this.setEnabled(true);
    return this;
};

/** Envia os arquivos escolhidos no input. */
ImageGallery.prototype.upload = async function upload() {
    if (this.busy || this.readOnly) return this;
    if (this.serviceId === null) {
        this.toast.warning("Salve o registro antes de enviar imagens.");
        return this;
    }

    const files = this.input?.files;

    if (!files || files.length === 0) {
        this.toast.warning(this.labels.noSelection);
        return this;
    }

    const validation = validateImages(files, 10 - this.images.length);
    if (validation) {
        this.toast.warning(validation);
        return this;
    }
    const serviceId = this.serviceId;
    this.busy = true;
    if (this.uploadButton) this.uploadButton.textContent = "Enviando…";
    this.setEnabled(false);

    try {
        const result = await this.service.upload(serviceId, Array.from(files));

        this.toast.fromResponse(result);

        if (!result.ok) {
            this.handleFailure(result);
            return this;
        }

        await this.onChange?.();
        if (this.serviceId !== serviceId) return this;
        this.input.value = "";
        this.clearPreview();
        return await this.load();
    } catch (error) {
        console.error("Erro no upload:", error);
        this.toast.error(serviceUnavailableMessage());
        return this;
    } finally {
        this.busy = false;
        if (this.uploadButton) this.uploadButton.textContent = "Enviar imagens";
        this.setEnabled(true);
    }
};

ImageGallery.prototype.setCover = async function setCover(imageId) {
    if (this.busy || this.readOnly || this.serviceId === null) return this;
    const serviceId = this.serviceId;
    this.busy = true;
    this.setEnabled(false);
    try {
        const result = await this.service.setCover(serviceId, imageId);

        this.toast.fromResponse(result);

        if (!result.ok) {
            this.handleFailure(result);
            return this;
        }

        await this.onChange?.();
        if (this.serviceId !== serviceId) return this;
        return await this.load();
    } catch (error) {
        console.error("Erro ao definir a capa:", error);
        this.toast.error(serviceUnavailableMessage());
        return this;
    } finally {
        this.busy = false;
        this.setEnabled(true);
    }
};

ImageGallery.prototype.remove = async function remove(imageId) {
    if (this.busy || this.readOnly || this.serviceId === null) return this;
    if (!confirm(this.labels.confirmDelete)) {
        return this;
    }

    const serviceId = this.serviceId;
    this.busy = true;
    this.setEnabled(false);
    try {
        const result = await this.service.remove(serviceId, imageId);

        this.toast.fromResponse(result);

        if (!result.ok) {
            this.handleFailure(result);
            return this;
        }

        await this.onChange?.();
        if (this.serviceId !== serviceId) return this;
        return await this.load();
    } catch (error) {
        console.error("Erro ao excluir a imagem:", error);
        this.toast.error(serviceUnavailableMessage());
        return this;
    } finally {
        this.busy = false;
        this.setEnabled(true);
    }
};

ImageGallery.prototype.handleFailure = function handleFailure(result) {
    if ([401, 403].includes(Number(result?.code))) {
        this.onAuthFailure?.(result);
    }

    return this;
};

/** Liga os eventos do DOM. Um único listener no contêiner atende toda a grade. */
ImageGallery.prototype.bind = function bind() {
    this.uploadButton?.addEventListener("click", (event) => {
        event.preventDefault();
        this.upload();
    });

    this.container.addEventListener("click", (event) => {
        const figure = event.target.closest("figure");

        if (!figure) {
            return;
        }

        const imageId = figure.getAttribute("image-id");

        if (event.target.classList.contains("btn-cover")) {
            this.setCover(imageId);
        }

        if (event.target.classList.contains("btn-delete-image")) {
            this.remove(imageId);
        }
    });

    return this;
};

export default ImageGallery;
