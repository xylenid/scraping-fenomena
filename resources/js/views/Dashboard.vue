<template>
    <div class="space-y-6">
        <!-- Stat cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div v-for="card in statCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</span>
                    <span class="text-lg">{{ card.icon }}</span>
                </div>
                <div class="mt-2 text-2xl font-bold text-slate-900">{{ card.value }}</div>
                <div class="mt-1 text-xs text-slate-500">{{ card.sub }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <!-- Kategori -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-slate-900">Kategori Fenomena</h3>
                <div v-if="stats.by_category" class="space-y-3">
                    <div v-for="(count, cat) in stats.by_category" :key="cat" class="flex items-center gap-3">
                        <span class="w-24 text-xs font-medium text-slate-600">{{ categoryLabel(cat) }}</span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full" :style="{ width: barWidth(count), backgroundColor: categoryColor(cat) }"></div>
                        </div>
                        <span class="w-8 text-right text-xs font-semibold text-slate-700">{{ count }}</span>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">Belum ada data.</p>
            </div>

            <!-- Per sumber -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-slate-900">Artikel per Sumber</h3>
                <div v-if="stats.by_source?.length" class="space-y-3">
                    <div v-for="row in stats.by_source" :key="row.code" class="flex items-center gap-3">
                        <span class="w-32 truncate text-xs font-medium text-slate-600">{{ row.source }}</span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-indigo-500" :style="{ width: barWidth(row.total) }"></div>
                        </div>
                        <span class="w-8 text-right text-xs font-semibold text-slate-700">{{ row.total }}</span>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">Belum ada data.</p>
            </div>

            <!-- Job terakhir -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-sm font-semibold text-slate-900">Crawl Job Terakhir</h3>
                <div v-if="stats.latest_job" class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Periode</span>
                        <span class="font-semibold text-slate-900">{{ stats.latest_job.period }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Window</span>
                        <span class="font-semibold text-slate-900">{{ stats.latest_job.window_type }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Status</span>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(stats.latest_job.status)">
                            {{ stats.latest_job.status }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Artikel disimpan</span>
                        <span class="font-semibold text-slate-900">{{ stats.latest_job.stats?.kept ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Selesai</span>
                        <span class="font-semibold text-slate-900">{{ formatDate(stats.latest_job.finished_at) }}</span>
                    </div>
                </div>
                <p v-else class="text-sm text-slate-400">Belum ada job dijalankan.</p>
            </div>
        </div>

        <!-- Aksi cepat -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-slate-900">Jalankan Crawl Manual</h3>
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Periode (YYYY-MM)</label>
                    <input
                        v-model="runPeriod"
                        type="month"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Window</label>
                    <select
                        v-model="runWindow"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    >
                        <option value="main">Main (1–30 bulan target)</option>
                        <option value="addon">Addon (1–10 bulan berikutnya)</option>
                    </select>
                </div>
                <button
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="running || !runPeriod"
                    @click="runCrawl"
                >
                    {{ running ? 'Menjalankan...' : '▶ Jalankan Crawl' }}
                </button>
                <span v-if="runMessage" class="text-sm" :class="runError ? 'text-red-600' : 'text-emerald-600'">{{ runMessage }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';

const stats = ref({});
const loading = ref(true);
const runPeriod = ref('');
const runWindow = ref('main');
const running = ref(false);
const runMessage = ref('');
const runError = ref(false);

const statCards = computed(() => [
    { label: 'Total Artikel', value: stats.value.total_articles ?? 0, icon: '📰', sub: stats.value.period ? `Periode ${stats.value.period}` : 'Semua periode' },
    { label: 'Perlu Review', value: stats.value.needs_review ?? 0, icon: '🔍', sub: 'Kategori/tanggal tidak pasti' },
    { label: 'Artikel Addon', value: stats.value.addon_articles ?? 0, icon: '➕', sub: 'Berita 1–10 bulan berikutnya' },
    { label: 'Sumber Aktif', value: stats.value.total_sources ?? 0, icon: '🌐', sub: 'Media terkonfigurasi' },
]);

onMounted(async () => {
    try {
        const { data } = await axios.get('/dashboard/stats');
        if (data && typeof data === 'object') stats.value = data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
});

function categoryLabel(cat) {
    return { commodity: 'Komoditas', policy: 'Kebijakan', logistics: 'Logistik', other: 'Lainnya', unclear: 'Belum jelas' }[cat] ?? cat;
}

function categoryColor(cat) {
    return { commodity: '#10b981', policy: '#6366f1', logistics: '#f59e0b', other: '#94a3b8', unclear: '#cbd5e1' }[cat] ?? '#94a3b8';
}

function barWidth(count) {
    const max = Math.max(...Object.values(stats.value.by_category ?? {}), ...(stats.value.by_source ?? []).map((r) => r.total), 1);
    return Math.max(4, Math.round((count / max) * 100)) + '%';
}

function statusClass(status) {
    return {
        completed: 'bg-emerald-100 text-emerald-700',
        partial: 'bg-amber-100 text-amber-700',
        failed: 'bg-red-100 text-red-700',
        running: 'bg-blue-100 text-blue-700',
        queued: 'bg-slate-100 text-slate-600',
    }[status] ?? 'bg-slate-100 text-slate-600';
}

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}

async function runCrawl() {
    running.value = true;
    runMessage.value = '';
    runError.value = false;
    try {
        const { data } = await axios.post('/crawl-jobs/run', {
            period: runPeriod.value,
            window: runWindow.value,
            trigger: 'manual',
        });
        runMessage.value = `Job #${data.job.id} dibuat (${data.job.status}).`;
    } catch (e) {
        runError.value = true;
        runMessage.value = e.response?.data?.message ?? 'Gagal menjalankan crawl.';
    } finally {
        running.value = false;
    }
}
</script>