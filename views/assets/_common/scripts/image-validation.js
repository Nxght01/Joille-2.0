export function validateImages(files, available = Infinity) {
    const selected = Array.from(files ?? []);
    if (!selected.length) return "Escolha ao menos uma imagem para enviar.";
    if (selected.length > available) return `Você pode enviar mais ${Math.max(0, available)} imagem(ns).`;
    for (const file of selected) {
        if (!["image/jpeg", "image/jpg", "image/png"].includes(file.type)) {
            return "Envie apenas imagens JPG, JPEG ou PNG.";
        }
        if (!file.size || file.size > 4 * 1024 * 1024) {
            return "Cada imagem deve ter conteúdo e no máximo 4 MB.";
        }
    }
    return null;
}
