<template>
    <div class="space-y-4">
        <section class="card p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="field-label" for="filter-period">Periode</label>
                    <select id="filter-period" v-model="filters.period" class="input" @change="reload">
                        <option value="">Semua periode</option>
                        <option v-for="p in periods" :key="p" :value="p">{{ formatPeriod(p) }}</option>
                    </select>
                </div>
                <div>
                    <label class="field-label" for="filter-source">Sumber</label>
                    <select id="filter-source" v-model="filters.source" class="input" @change="reload">
                        <option value="">Semua sumber</option>
                        <option v-for="s in sources" :key="s.code" :value="s.code">{{ s.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="field-label" for="filter-category">Kategori</label>
                    <select id="filter-category" v-model="filters.category" class="input" @change="reload">
                        <option value="">Semua kategori</option>
                        <option v-for="c in categories" :key="c" :value="c">{{ categoryLabel(c) }}</option>
                    </select>
                </div>
                <div>
                    <label class="field-label" for="filter-q">Cari</label>
                    <input id="filter-q" v-model="filters.q" class="input" placeholder="Judul atau penulis" />
                </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-3 border-t border-slate-100 pt-3">
                <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
                    <input
                        v-model="filters.needs_review"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                        @change="reload"
                    />
                    Perlu review
                </label>
                <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
                    <input
                        v-model="filters.is_addon"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                        @change="reload"
                    />
                    Artikel addon
                </label>
                <button
                    v-if="hasActiveFilters"
                    class="btn btn-ghost -ml-2 px-2 py-1 text-xs"
                    type="button"
                    @click="resetFilters"
                >
                    Reset filter
                </button>
                <div class="ml-auto flex items-center gap-2">
                    <label class="text-xs text-slate-500" for="filter-per-page">Baris</label>
                    <select id="filter-per-page" v-model.number="filters.per_page" class="input h-8 w-20" @change="reload">
                        <option v-for="n in [20, 50, 100]" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>
            </div>
        </section>

        <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ error }}
        </p>

        <section class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="th">Judul</th>
                            <th class="th">Sumber</th>
                            <th class="th">Kategori</th>
                            <th class="th">Tanggal terbit</th>
                            <th class="th">Periode</th>
                            <th class="th">Penanda</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="a in articles"
                            :key="a.id"
                            class="cursor-pointer transition-colors hover:bg-slate-50"
                            @click="openArticle(a.id)"
                        >
                            <td class="td max-w-md">
                                <div class="line-clamp-2 font-medium text-slate-900">{{ a.title }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">
                                    {{ a.author || 'Tanpa penulis' }} · {{ a.entities_count }} entitas
                                </div>
                            </td>
                            <td class="td whitespace-nowrap text-slate-600">{{ a.source?.name }}</td>
                            <td class="td whitespace-nowrap">
                                <Badge :tone="categoryTone(a.category_primary)">{{ categoryLabel(a.category_primary) }}</Badge>
                            </td>
                            <td class="td whitespace-nowrap text-slate-600">{{ formatDate(a.published_at) }}</td>
                            <td class="td whitespace-nowrap text-slate-600">{{ formatPeriod(a.period_target) }}</td>
                            <td class="td whitespace-nowrap">
                                <div class="flex gap-1.5">
                                    <Badge v-if="a.is_addon" tone="info">Addon</Badge>
                                    <Badge v-if="a.needs_review" tone="warn">Review</Badge>
                                    <span v-if="!a.is_addon && !a.needs_review" class="text-xs text-slate-400">—</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="loading" class="px-4 py-10 text-center text-sm text-slate-500">Memuat artikel…</div>

            <EmptyState
                v-else-if="!articles.length"
                :title="hasActiveFilters ? 'Tidak ada artikel yang cocok' : 'Belum ada artikel'"
                :description="hasActiveFilters
                    ? 'Coba longgarkan filter periode, sumber, atau kategori.'
                    : 'Artikel akan muncul setelah crawl pertama selesai.'"
            />

            <footer
                v-if="meta.total"
                class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3"
            >
                <p class="text-xs text-slate-500">
                    Menampilkan {{ meta.from ?? 0 }}–{{ meta.to ?? 0 }} dari {{ formatNumber(meta.total) }} artikel
                </p>
                <div class="flex items-center gap-2">
                    <button class="btn btn-secondary px-3 py-1.5 text-xs" :disabled="page <= 1" @click="goToPage(page - 1)">
                        Sebelumnya
                    </button>
                    <span class="text-xs text-slate-500">Halaman {{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button
                        class="btn btn-secondary px-3 py-1.5 text-xs"
                        :disabled="page >= meta.last_page"
                        @click="goToPage(page + 1)"
                    >
                        Berikutnya
                    </button>
                </div>
            </footer>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Badge from '../components/Badge.vue';
import EmptyState from '../components/EmptyState.vue';
import { errorMessage, fetchArticles, fetchDashboardStats, fetchSources } from '../lib/api';
import { categoryLabel, formatDate, formatNumber, formatPeriod } from '../lib/format';

const CATEGORIES = ['commodity', 'policy', 'logistics', 'other', 'unclear'];
const CATEGORY_TONES = { commodity: 'accent', policy: 'info', logistics: 'warn' };

const route = useRoute();
const router = useRouter();

const articles = ref([]);
const sources = ref([]);
const periods = ref([]);
const meta = ref({});
const page = ref(1);
const loading = ref(false);
const error = ref('');

const filters = reactive({
    period: '',
    source: '',
    category: '',
    q: '',
    needs_review: false,
    is_addon: false,
    per_page: 20,
});

const categories = CATEGORIES;

const hasActiveFilters = computed(() =>
    Boolean(filters.period || filters.source || filters.category || filters.q || filters.needs_review || filters.is_addon),
);

onMounted(async () => {
    applyQuery(route.query);
    await Promise.all([loadSources(), loadPeriods(), loadArticles()]);
});

watch(
    () => route.query,
    (query) => {
        if (!isSameQuery(query)) {
            applyQuery(query);
            loadArticles();
        }
    },
);

let searchTimer;
watch(
    () => filters.q,
    () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(reload, 350);
    },
);

async function loadSources() {
    try {
        sources.value = await fetchSources();
    } catch (e) {
        error.value = errorMessage(e, 'Gagal memuat daftar sumber.');
    }
}

async function loadPeriods() {
    try {
        const stats = await fetchDashboardStats();
        periods.value = stats.periods ?? [];
    } catch {
        periods.value = [];
    }
}

async function loadArticles() {
    loading.value = true;
    error.value = '';

    try {
        const params = { page: page.value, per_page: filters.per_page };
        if (filters.period) params.period = filters.period;
        if (filters.source) params.source = filters.source;
        if (filters.category) params.category = filters.category;
        if (filters.q) params.q = filters.q;
        if (filters.needs_review) params.needs_review = 1;
        if (filters.is_addon) params.is_addon = 1;

        const data = await fetchArticles(params);
        articles.value = Array.isArray(data.data) ? data.data : [];
        meta.value = data;
    } catch (e) {
        articles.value = [];
        meta.value = {};
        error.value = errorMessage(e, 'Gagal memuat artikel.');
    } finally {
        loading.value = false;
    }
}

function reload() {
    page.value = 1;
    syncQuery();
    loadArticles();
}

function goToPage(next) {
    if (next < 1 || (meta.value.last_page && next > meta.value.last_page)) return;
    page.value = next;
    syncQuery();
    loadArticles();
}

function resetFilters() {
    filters.period = '';
    filters.source = '';
    filters.category = '';
    filters.q = '';
    filters.needs_review = false;
    filters.is_addon = false;
    reload();
}

function applyQuery(query) {
    filters.period = query.period ?? '';
    filters.source = query.source ?? '';
    filters.category = query.category ?? '';
    filters.q = query.q ?? '';
    filters.needs_review = query.needs_review === '1';
    filters.is_addon = query.is_addon === '1';
    filters.per_page = Number(query.per_page) || 20;
    page.value = Number(query.page) || 1;
}

function isSameQuery(query) {
    return (query.period ?? '') === filters.period
        && (query.source ?? '') === filters.source
        && (query.category ?? '') === filters.category
        && (query.q ?? '') === filters.q
        && (query.needs_review === '1') === filters.needs_review
        && (query.is_addon === '1') === filters.is_addon
        && (Number(query.per_page) || 20) === filters.per_page
        && (Number(query.page) || 1) === page.value;
}

function syncQuery() {
    const query = {};
    if (filters.period) query.period = filters.period;
    if (filters.source) query.source = filters.source;
    if (filters.category) query.category = filters.category;
    if (filters.q) query.q = filters.q;
    if (filters.needs_review) query.needs_review = '1';
    if (filters.is_addon) query.is_addon = '1';
    if (filters.per_page !== 20) query.per_page = String(filters.per_page);
    if (page.value > 1) query.page = String(page.value);

    router.replace({ query });
}

function openArticle(id) {
    router.push({ name: 'article-detail', params: { id } });
}

function categoryTone(category) {
    return CATEGORY_TONES[category] ?? 'muted';
}
</script>
