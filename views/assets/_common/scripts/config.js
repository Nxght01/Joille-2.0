// O módulo está sempre em views/assets/_common/scripts; acompanha subpastas e portas.
export const URL_BASE = import.meta.url.startsWith('http')
    ? new URL('../../../../', import.meta.url).href.replace(/\/$/, '')
    : (globalThis.process?.env?.JOILLE_BASE_URL ?? 'http://localhost/projeto/joille2');
export const API_BASE_URL = `${URL_BASE}/api`;
