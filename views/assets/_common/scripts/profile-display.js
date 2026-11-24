import { getBackendUrl } from './functions.js';

const observedImages = new WeakSet();

/** Atualiza o cabeçalho e o perfil no mesmo documento, sem recarregar a página. */
export function renderProfile(profile, root = document) {
    const fallback = getBackendUrl('views/assets/public/images/avatar-default.svg');
    const photoUrl = new URL(profile?.photo ? getBackendUrl(profile.photo) : fallback);
    if (profile?.photo && profile.photoVersion) photoUrl.searchParams.set('v', profile.photoVersion);

    root.querySelectorAll('img[data-profile-photo]').forEach(image => {
        if (!observedImages.has(image)) {
            image.addEventListener('error', () => {
                if (image.src !== fallback) image.src = fallback;
            });
            observedImages.add(image);
        }
        if (image.src !== photoUrl.href) image.src = photoUrl.href;
    });

    const name = String(profile?.name ?? '').trim();
    root.querySelectorAll('#span-user, #span-admin').forEach(element => {
        element.textContent = name ? `Olá, ${name.split(/\s+/)[0]}!` : 'Minha conta';
    });
    root.querySelectorAll('[data-profile-name]').forEach(element => {
        element.textContent = name;
    });
}
