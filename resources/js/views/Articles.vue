<template>
    <div class="space-y-4">
        <!-- Filter bar -->
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Periode</label>
                    <select v-model="filters.period" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="">Semua periode</option>
                        <option v-for="p in periods" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Sumber</label>
                    <select v-model="filters.source" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="">Semua sumber</option>
                        <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Kategori</label>
                    <select v-model="filters.category" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="">Semua kategori</option>
                        <option value="commodity">Komoditas</option>
                        <option value="policy">Kebijakan</option>
                        <option value="logistics">Logistik</option>
                        <option value="other">Lainnya</option>
                        <option value="unclear">Belum jelas</option>
                    </select>
                </div>
                <div class="min-w-48 flex-1">
                    <label class="mb-1 block text-xs font-medium text-slate-600">Cari</label>
                    <input
                        v-model="filters.q"
                        placeholder="Judul atau penulis..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none"
                        @keyup.enter="loadArticles"
                    />
                </div>
                <label class="flex items-center gap-2 pb-2 text-sm text-slate-600">
                    <input v-model="filters.needs_review" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-emerald-600" />
                    Perlu review
                </label>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800" @click="loadArticles">
                    Filter
                </button>
            </div>
        </div>

        <!-- Tabel -->
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Judul</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Sumber</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="a in articles" :key="a.id" class="cursor-pointer transition-colors hover:bg-slate-50" @click="openArticle(a.id)">
                            <td class="max-w-md px-4 py-3">
                                <div class="line-clamp-2 font-medium text-slate-900">{{ a.title }}</div>
                                <div v-if="a.author" class="mt-0.5 text-xs text-slate-500">{{ a.author }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ a.source?.name }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="categoryClass(a.category_primary)">
                                    {{ categoryLabel(a.category_primary) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ formatDate(a.published_at) }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span v-if="a.is_addon" class="mr-1 rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-medium text-violet-700">Addon</span>
                                <span v-if="a.needs_review" class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700">Review</span>
                            </td>
                        </tr>
                        <tr v-if="!articles.length && !loading">
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-400">Belum ada artikel. Jalankan crawl terlebih dahulu.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="meta.total > meta.per_page" class="flex items-center justify-between border-t border-slate-200 px-4 py-3">
                <span class="text-xs text-slate-500">
                    Menampilkan {{ meta.from ?? 0 }}–{{ meta.to ?? 0 }} dari {{ meta.total }} artikel
                </span>
                <div class="flex gap-2">
                    <button
                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                        :disabled="!meta.prev_page_url"
                        @click="changePage(meta.current_page - 1)"
                    >
                        ← Sebelumnya
                    </button>
                    <button
                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                        :disabled="!meta.next_page_url"
                        @click="changePage(meta.current_page + 1)"
                    >
                        Berikutnya →
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const articles = ref([]);
const sources = ref([]);
const periods = ref([]);
const meta = ref({});
const loading = ref(false);

const filters = reactive({
    period: '',
    source: '',
    category: '',
    q: '',
    needs_review: false,
});

onMounted(async () => {
    await Promise.all([loadSources(), loadPeriods(), loadArticles()]);
});

async function loadSources() {
    try {
        const { data } = await axios.get('/sources');
        sources.value = Array.isArray(data) ? data : [];
    } catch (e) {
        console.error(e);
    }
}

async function loadPeriods() {
    try {
        const { data } = await axios.get('/dashboard/stats');
        // Ambil periode dari artikel via endpoint terpisah tidak ada; gunakan stats period jika ada
        if (data.period) periods.value = [data.period];
    } catch (e) {
        console.error(e);
    }
}

async function loadArticles() {
    loading.value = true;
    try {
        const params = {};
        if (filters.period) params.period = filters.period;
        if (filters.source) params.source = filters.source;
        if (filters.category) params.category = filters.category;
        if (filters.q) params.q = filters.q;
        if (filters.needs_review) params.needs_review = 1;

        const { data } = await axios.get('/articles', { params });
        articles.value = Array.isArray(data.data) ? data.data : [];
        meta.value = {
            total: data.total,
            per_page: data.per_page,
            current_page: data.current_page,
            prev_page_url: data.prev_page_url,
            next_page_url: data.next_page_url,
            from: data.from,
            to: data.to,
        };
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
}

function changePage(page) {
    // Implementasi sederhana: reload dengan page (perlu state page)
    loadArticles();
}

function openArticle(id) {
    router.push({ name: 'article-detail', params: { id } });
}

function categoryLabel(cat) {
    return { commodity: 'Komoditas', policy: 'Kebijakan', logistics: 'Logistik', other: 'Lainnya', unclear: 'Belum jelas' }[cat] ?? cat;
}

function categoryClass(cat) {
    return {
        commodity: 'bg-emerald-100 text-emerald-700',
        policy: 'bg-indigo-100 text-indigo-700',
        logistics: 'bg-amber-100 text-amber-700',
        other: 'bg-slate-100 text-slate-600',
        unclear: 'bg-slate-100 text-slate-500',
    }[cat] ?? 'bg-slate-100 text-slate-600';
}

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>