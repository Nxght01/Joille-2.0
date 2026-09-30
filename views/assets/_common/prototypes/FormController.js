/**
 * FormController — POO PROTOTÍPICA (função construtora + prototype).
 *
 * Também sem `class`: os métodos são atribuídos uma única vez a
 * `FormController.prototype`, portanto NÃO são recriados a cada `new`.
 * Dois controllers de formulários diferentes compartilham as mesmas funções:
 *
 *     Object.getPrototypeOf(a) === FormController.prototype   // true
 *     a.serialize === b.serialize                             // true
 *
 * Próprio de cada objeto: `form`, `rules` e `busyLabel`.
 * Compartilhado: serialize, values, fill, clear, validate, setBusy, field.
 */
export function FormController(form, { rules = {}, busyLabel = "Salvando..." } = {}) {
    if (!(this instanceof FormController)) {
        throw new TypeError("Use 'new FormController(form)'");
    }

    if (!form) {
        throw new TypeError("FormController exige um elemento <form>");
    }

    this.form = form;
    this.rules = rules;
    this.busyLabel = busyLabel;
}

/** Devolve o FormData bruto, pronto para envio multipart. */
FormController.prototype.serialize = function serialize() {
    return new FormData(this.form);
};

/** Devolve um objeto simples com os valores do formulário. */
FormController.prototype.values = function values() {
    return Object.fromEntries(this.serialize().entries());
};

/** Lê um campo específico. */
FormController.prototype.field = function field(name) {
    return this.serialize().get(name);
};

/** Preenche o formulário a partir de um objeto ou de uma entidade. */
FormController.prototype.fill = function fill(source) {
    const data = typeof source?.toJSON === "function" ? source.toJSON() : source ?? {};

    Object.entries(data).forEach(([key, value]) => {
        const input = this.form.querySelector(`[name="${key}"]`);

        if (input && value !== null && value !== undefined) {
            input.value = value;
        }
    });

    return this;
};

/** Limpa o formulário e zera o campo oculto de id. */
FormController.prototype.clear = function clear() {
    this.form.reset();

    const id = this.form.querySelector('[name="id"]');
    if (id) {
        id.value = "";
    }

    return this;
};

/**
 * Validação simples e declarativa. Cada regra é
 * { required, minLength, email, label } e devolve a lista de erros.
 */
FormController.prototype.validate = function validate() {
    const values = this.values();
    const errors = [];

    Object.entries(this.rules).forEach(([name, rule]) => {
        const label = rule.label ?? name;
        const value = String(values[name] ?? "").trim();

        if (rule.required && value === "") {
            errors.push(`${label} é obrigatório.`);
            return;
        }

        if (value === "") {
            return; // campo opcional em branco: nada mais a validar
        }

        if (rule.minLength && value.length < rule.minLength) {
            errors.push(`${label} deve ter no mínimo ${rule.minLength} caracteres.`);
        }

        if (rule.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            errors.push(`${label} deve ser um e-mail válido.`);
        }

        if (rule.matches && value !== String(values[rule.matches] ?? "")) {
            errors.push(`${label} não confere.`);
        }
    });

    return errors;
};

FormController.prototype.isValid = function isValid() {
    return this.validate().length === 0;
};

/** Bloqueia o botão de envio enquanto a requisição acontece. */
FormController.prototype.setBusy = function setBusy(busy) {
    const button = this.form.querySelector('button[type="submit"], input[type="submit"]');

    if (!button) {
        return this;
    }

    const isInput = button.tagName === "INPUT";
    const current = isInput ? button.value : button.textContent;

    if (busy) {
        button.dataset.originalLabel = button.dataset.originalLabel ?? current;

        if (isInput) {
            button.value = this.busyLabel;
        } else {
            button.textContent = this.busyLabel;
        }

        button.disabled = true;
        return this;
    }

    const original = button.dataset.originalLabel ?? current;

    if (isInput) {
        button.value = original;
    } else {
        button.textContent = original;
    }

    button.disabled = false;
    return this;
};

export default FormController;
