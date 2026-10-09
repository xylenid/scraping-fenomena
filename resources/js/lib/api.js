import axios from 'axios';

/**
 * URL backend. Wajib diisi saat build (Vite menyalin nilainya ke bundle),
 * karena SPA di-deploy terpisah dari API.
 */
const apiBase = (import.meta.env.VITE_API_BASE_URL || '').trim().replace(/\/+$/, '');

export const API_CONFIGURED = apiBase !== '';

export const http = axios.create({
    baseURL: `${apiBase}/api/v1`,
    headers: { Accept: 'application/json' },
});

const CONFIG_HINT =
    'VITE_API_BASE_URL belum diisi saat build. Set variabel tersebut di Vercel lalu deploy ulang.';

export function errorMessage(error, fallback = 'Terjadi kesalahan saat menghubungi server.') {
    if (!API_CONFIGURED) {
        return CONFIG_HINT;
    }

    // Rewrite catch-all di hosting SPA bisa membalas HTML dengan status 200.
    if (typeof error?.response?.data === 'string' && error.response.data.trimStart().startsWith('<')) {
        return 'Server membalas HTML, bukan JSON. Periksa URL API backend.';
    }

    return error?.response?.data?.message
        ?? Object.values(error?.response?.data?.errors ?? {})?.flat()?.[0]
        ?? fallback;
}

export async function fetchDashboardStats(params) {
    const { data } = await http.get('/dashboard/stats', { params });
    return data;
}

export async function fetchSources() {
    const { data } = await http.get('/sources');
    return Array.isArray(data) ? data : [];
}

export async function fetchArticles(params) {
    const { data } = await http.get('/articles', { params });
    return data;
}

export async function fetchArticle(id) {
    const { data } = await http.get(`/articles/${id}`);
    return data;
}

export async function fetchCrawlJobs(params) {
    const { data } = await http.get('/crawl-jobs', { params });
    return data;
}

export async function fetchCrawlJobPeriods() {
    const { data } = await http.get('/crawl-jobs/periods');
    return Array.isArray(data) ? data : [];
}

export async function fetchCrawlJob(id) {
    const { data } = await http.get(`/crawl-jobs/${id}`);
    return data;
}

export async function runCrawlJob(payload) {
    const { data } = await http.post('/crawl-jobs/run', payload);
    return data;
}
