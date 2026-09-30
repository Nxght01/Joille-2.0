/**
 * Accordion — POO PROTOTÍPICA (função construtora + prototype).
 *
 * Sanfona acessível com **delegação de eventos**: um único listener no contêiner
 * atende todos os painéis, inclusive os inseridos depois (é o caso do FAQ, cuja
 * lista vem da API). Nenhum `onclick` no HTML.
 *
 * Contrato de marcação esperado:
 *
 *      <dl class="accordion">
 *          <faq-item>
 *              <dt><button class="faq-question" aria-expanded="false">…</button></dt>
 *              <dd class="faq-answer">…</dd>
 *          </faq-item>
 *      </dl>
 *
 * O estado visível fica no DOM (`aria-expanded` no gatilho e a classe de aberto no
 * painel), não em uma variável à parte: assim leitores de tela e CSS leem a mesma
 * verdade, e não há como os dois divergirem.
 *
 * Os métodos moram uma única vez em `Accordion.prototype`.
 */
export function Accordion({
    container,
    itemSelector = "faq-item",
    triggerSelector = ".faq-question",
    panelSelector = ".faq-answer",
    openClass = "open",
    single = false
} = {}) {
    if (!(this instanceof Accordion)) {
        throw new TypeError("Use 'new Accordion({...})'");
    }

    if (!container) {
        throw new TypeError("Accordion exige um contêiner");
    }

    this.container = container;
    this.itemSelector = itemSelector;
    this.triggerSelector = triggerSelector;
    this.panelSelector = panelSelector;
    this.openClass = openClass;
    // single = true fecha os outros ao abrir um; false permite vários abertos
    this.single = single;
}

/** Localiza o painel correspondente a um gatilho. */
Accordion.prototype.panelFor = function panelFor(trigger) {
    const item = trigger.closest(this.itemSelector);
    return item ? item.querySelector(this.panelSelector) : null;
};

Accordion.prototype.isOpen = function isOpen(trigger) {
    return trigger.getAttribute("aria-expanded") === "true";
};

Accordion.prototype.open = function open(trigger) {
    const panel = this.panelFor(trigger);

    if (!panel) {
        return this;
    }

    if (this.single) {
        this.closeAll(trigger);
    }

    trigger.setAttribute("aria-expanded", "true");
    panel.classList.add(this.openClass);

    // A altura real é medida no momento da abertura em vez de fixada no CSS:
    // uma resposta longa não fica cortada e a transição continua suave.
    panel.style.maxHeight = `${panel.scrollHeight}px`;

    return this;
};

Accordion.prototype.close = function close(trigger) {
    const panel = this.panelFor(trigger);

    if (!panel) {
        return this;
    }

    trigger.setAttribute("aria-expanded", "false");
    panel.classList.remove(this.openClass);
    panel.style.maxHeight = "";

    return this;
};

Accordion.prototype.toggle = function toggle(trigger) {
    return this.isOpen(trigger) ? this.close(trigger) : this.open(trigger);
};

/** Fecha todos os painéis, opcionalmente preservando um gatilho. */
Accordion.prototype.closeAll = function closeAll(except = null) {
    this.container.querySelectorAll(this.triggerSelector).forEach((trigger) => {
        if (trigger !== except) {
            this.close(trigger);
        }
    });

    return this;
};

/**
 * Reajusta a altura dos painéis abertos.
 * Necessário quando o texto reflui — ao redimensionar a janela, por exemplo —
 * porque a altura foi fixada em pixels na abertura.
 */
Accordion.prototype.refresh = function refresh() {
    this.container.querySelectorAll(this.triggerSelector).forEach((trigger) => {
        if (this.isOpen(trigger)) {
            const panel = this.panelFor(trigger);

            if (panel) {
                panel.style.maxHeight = `${panel.scrollHeight}px`;
            }
        }
    });

    return this;
};

Accordion.prototype.bind = function bind() {
    this.container.addEventListener("click", (event) => {
        const trigger = event.target.closest(this.triggerSelector);

        // Ignora cliques fora de um gatilho e os de outra sanfona aninhada
        if (!trigger || !this.container.contains(trigger)) {
            return;
        }

        this.toggle(trigger);
    });

    window.addEventListener("resize", () => this.refresh());

    return this;
};

export default Accordion;
