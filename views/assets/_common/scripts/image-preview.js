// A prévia usa apenas arquivos selecionados localmente e nunca é enviada ao servidor.
export function bindImagePreview(input, container, validate, warn) {
    let urls = [];
    function clear() {
        container.replaceChildren();
        urls.forEach(url => URL.revokeObjectURL(url));
        urls = [];
    }
    input.addEventListener("change", () => {
        clear();
        if (!input.files.length) return;
        const error = validate(input.files);
        if (error) {
            input.value = "";
            warn(error);
            return;
        }
        for (const file of input.files) {
            const img = document.createElement("img");
            img.src = URL.createObjectURL(file);
            urls.push(img.src);
            img.alt = "Prévia: " + file.name;
            img.width = 96;
            container.append(img);
        }
    });
    window.addEventListener("pagehide", clear);
    return clear;
}
