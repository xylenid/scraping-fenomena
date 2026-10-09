<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="w-44">
                <label class="field-label" for="job-period">Periode</label>
                <select id="job-period" v-model="period" class="input" @change="reload">
                    <option value="">Semua periode</option>
                    <option v-for="p in periods" :key="p" :value="p">{{ formatPeriod(p) }}</option>
                </select>
            </div>
            <button class="btn btn-accent" @click="showRunForm = !showRunForm">
                {{ showRunForm ? 'Tutup form' : 'Jalankan crawl' }}
            </button>
        </div>

        <section v-if="showRunForm" class="card card-pad">
            <div class="flex flex-wrap items-end gap-3">
                <div class="w-44">
                    <label class="field-label" for="run-period">Periode target</label>
                    <input id="run-period" v-model="runPeriod" type="month" class="input" />
                </div>
                <div class="w-56">
                    <label class="field-label" for="run-window">Window</label>
                    <select id="run-window" v-model="runWindow" class="input">
                        <option value="main">Main — 1–30 bulan target</option>
                        <option value="addon">Addon — 1–10 bulan berikutnya</option>
                    </select>
                </div>
                <button class="btn btn-primary" type="button" :disabled="running || !runPeriod" @click="runCrawl">
                    {{ running ? 'Menjalankan…' : 'Mulai sekarang' }}
                </button>
            </div>
            <p class="mt-3 text-xs leading-relaxed text-slate-500">
                Crawl manual mengambil artikel dari seluruh sumber aktif untuk periode di atas. Proses berjalan di server dan
                hasilnya muncul di daftar begitu selesai.
            </p>
        </section>

        <p v-if="message" class="rounded-lg border px-4 py-3 text-sm" :class="messageTone">{{ message }}</p>

        <section class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="th">Job</th>
                            <th class="th">Periode</th>
                            <th class="th">Window</th>
                            <th class="th">Trigger</th>
                            <th class="th">Status</th>
                            <th class="th">Disimpan</th>
                            <th class="th">Gagal</th>
                            <th class="th">Durasi</th>
                            <th class="th">Selesai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="job in jobs"
                            :key="job.id"
                            class="cursor-pointer transition-colors hover:bg-slate-50"
                            @click="openJob(job.id)"
                        >
                            <td class="td num text-slate-900">#{{ job.id }}</td>
                            <td class="td whitespace-nowrap text-slate-700">{{ formatPeriod(job.period) }}</td>
                            <td class="td whitespace-nowrap">
                                <Badge :tone="job.window_type === 'addon' ? 'warn' : 'neutral'">{{ windowLabel(job.window_type) }}</Badge>
                            </td>
                            <td class="td whitespace-nowrap text-slate-600">{{ job.triggered_by === 'cron' ? 'Terjadwal' : 'Manual' }}</td>
                            <td class="td whitespace-nowrap">
                                <Badge :tone="jobTone(job.status)">{{ jobStatusLabel(job.status) }}</Badge>
                            </td>
                            <td class="td num text-slate-700">{{ formatNumber(job.stats?.kept) }}</td>
                            <td class="td num" :class="job.stats?.failed ? 'text-rose-600' : 'text-slate-400'">
                                {{ formatNumber(job.stats?.failed) }}
                            </td>
                            <td class="td whitespace-nowrap text-slate-600">{{ formatDuration(job.started_at, job.finished_at) }}</td>
                            <td class="td whitespace-nowrap text-slate-600">{{ formatDate(job.finished_at, { withTime: true }) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="loading" class="px-4 py-10 text-center text-sm text-slate-500">Memuat crawl job…</div>

            <EmptyState
                v-else-if="!jobs.length"
                title="Belum ada crawl job"
                description="Job otomatis dibuat oleh jadwal bulanan, atau jalankan sendiri lewat tombol di atas."
            />

            <footer v-if="meta.total" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3">
                <p class="text-xs text-slate-500">
                    Menampilkan {{ meta.from ?? 0 }}–{{ meta.to ?? 0 }} dari {{ formatNumber(meta.total) }} job
                </p>
                <div class="flex items-center gap-2">
                    <button class="btn btn-secondary px-3 py-1.5 text-xs" :disabled="page <= 1" @click="goToPage(page - 1)">
                        Sebelumnya
                    </button>
                    <span class="text-xs text-slate-500">Halaman {{ meta.current_page }} / {{ meta.last_page }}</span>
                    <button class="btn btn-secondary px-3 py-1.5 text-xs" :disabled="page >= meta.last_page" @click="goToPage(page + 1)">
                        Berikutnya
                    </button>
                </div>
            </footer>
        </section>

        <Teleport to="body">
            <div v-if="detailOpen" class="fixed inset-0 z-50 flex justify-end">
                <div class="absolute inset-0 bg-slate-900/40" @click="closeDetail"></div>

                <aside class="relative flex h-full w-full max-w-2xl flex-col border-l border-slate-200 bg-white shadow-xl">
                    <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">
                                Crawl Job #{{ detail?.id ?? selectedId }}
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ detail ? `${formatPeriod(detail.period)} · ${windowLabel(detail.window_type)}` : 'Memuat detail…' }}
                            </p>
                        </div>
                        <button class="btn btn-ghost px-2 py-1" aria-label="Tutup detail" @click="closeDetail">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>
                    </header>

                    <div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
                        <p v-if="detailError" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ detailError }}
                        </p>

                        <template v-if="detail">
                            <div class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                                <div>
                                    <p class="text-xs text-slate-500">Status</p>
                                    <p class="mt-1"><Badge :tone="jobTone(detail.status)">{{ jobStatusLabel(detail.status) }}</Badge></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Trigger</p>
                                    <p class="mt-1 text-sm text-slate-800">{{ detail.triggered_by === 'cron' ? 'Terjadwal' : 'Manual' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Durasi</p>
                                    <p class="mt-1 text-sm text-slate-800">{{ formatDuration(detail.started_at, detail.finished_at) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Ditemukan</p>
                                    <p class="num mt-1 text-slate-800">{{ formatNumber(detail.stats?.fetched) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Disimpan</p>
                                    <p class="num mt-1 text-slate-800">{{ formatNumber(detail.stats?.kept) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Gagal</p>
                                    <p class="num mt-1" :class="detail.stats?.failed ? 'text-rose-600' : 'text-slate-800'">
                                        {{ formatNumber(detail.stats?.failed) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Duplikat</p>
                                    <p class="num mt-1 text-slate-800">{{ formatNumber(detail.stats?.skipped_duplicate) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Luar periode</p>
                                    <p class="num mt-1 text-slate-800">{{ formatNumber(detail.stats?.skipped_period) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Kualitas rendah</p>
                                    <p class="num mt-1 text-slate-800">{{ formatNumber(detail.stats?.skipped_quality) }}</p>
                                </div>
                            </div>

                            <section>
                                <h4 class="card-title mb-3">Status per Sumber</h4>
                                <div v-if="detail.source_logs?.length" class="card divide-y divide-slate-100">
                                    <div
                                        v-for="log in detail.source_logs"
                                        :key="log.id"
                                        class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3"
                                    >
                                        <div class="min-w-40 flex-1">
                                            <p class="text-sm font-medium text-slate-900">{{ log.source?.name ?? `Source #${log.source_id}` }}</p>
                                            <p v-if="log.error_summary" class="mt-0.5 line-clamp-2 text-xs text-rose-600">{{ log.error_summary }}</p>
                                        </div>
                                        <Badge :tone="logTone(log.status)">{{ sourceLogStatusLabel(log.status) }}</Badge>
                                        <span class="num text-xs text-slate-500">
                                            {{ formatNumber(log.kept) }}/{{ formatNumber(log.fetched) }} disimpan
                                        </span>
                                        <span class="num w-20 text-right text-xs text-slate-500">
                                            {{ formatDuration(log.started_at, log.finished_at) }}
                                        </span>
                                    </div>
                                </div>
                                <p v-else class="text-xs text-slate-500">Belum ada log sumber untuk job ini.</p>
                            </section>

                            <section v-if="detail.failures?.length">
                                <h4 class="card-title mb-3">Kegagalan ({{ detail.failures.length }})</h4>
                                <div class="card divide-y divide-slate-100">
                                    <div v-for="failure in detail.failures.slice(0, 20)" :key="failure.id" class="px-4 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Badge tone="danger">{{ errorTypeLabel(failure.error_type) }}</Badge>
                                            <span class="truncate text-xs text-slate-500" :title="failure.url">{{ failure.url }}</span>
                                        </div>
                                        <p class="mt-1.5 text-xs leading-relaxed text-slate-600">{{ failure.error_message }}</p>
                                    </div>
                                </div>
                                <p v-if="detail.failures.length > 20" class="mt-2 text-xs text-slate-500">
                                    Menampilkan 20 dari {{ detail.failures.length }} kegagalan.
                                </p>
                            </section>
                        </template>
                    </div>
                </aside>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import Badge from '../components/Badge.vue';
import EmptyState from '../components/EmptyState.vue';
import { errorMessage, fetchCrawlJob, fetchCrawlJobPeriods, fetchCrawlJobs, runCrawlJob } from '../lib/api';
import {
    errorTypeLabel,
    formatDate,
    formatDuration,
    formatNumber,
    formatPeriod,
    jobStatusLabel,
    sourceLogStatusLabel,
    windowLabel,
} from '../lib/format';

const JOB_TONES = { completed: 'accent', partial: 'warn', failed: 'danger', running: 'info', queued: 'info' };
const LOG_TONES = { running: 'info', success: 'accent', partial: 'warn', failed: 'danger', skipped: 'muted' };

const jobs = ref([]);
const periods = ref([]);
const meta = ref({});
const page = ref(1);
const period = ref('');
const loading = ref(false);

const showRunForm = ref(false);
const runPeriod = ref('');
const runWindow = ref('main');
const running = ref(false);
const message = ref('');
const messageError = ref(false);

const detail = ref(null);
const detailOpen = ref(false);
const detailError = ref('');
const selectedId = ref(null);

const messageTone = computed(() =>
    messageError.value
        ? 'border-rose-200 bg-rose-50 text-rose-700'
        : 'border-emerald-200 bg-emerald-50 text-emerald-700',
);

onMounted(async () => {
    await Promise.all([loadJobs(), loadPeriods()]);
});

async function loadPeriods() {
    try {
        periods.value = await fetchCrawlJobPeriods();
    } catch {
        periods.value = [];
    }
}

async function loadJobs() {
    loading.value = true;
    try {
        const params = { page: page.value };
        if (period.value) params.period = period.value;

        const data = await fetchCrawlJobs(params);
        jobs.value = Array.isArray(data.data) ? data.data : [];
        meta.value = data;
    } catch (e) {
        jobs.value = [];
        meta.value = {};
        messageError.value = true;
        message.value = errorMessage(e, 'Gagal memuat daftar crawl job.');
    } finally {
        loading.value = false;
    }
}

function reload() {
    page.value = 1;
    loadJobs();
}

function goToPage(next) {
    if (next < 1 || (meta.value.last_page && next > meta.value.last_page)) return;
    page.value = next;
    loadJobs();
}

function runCrawl() {
    if (!runPeriod.value) return;

    running.value = true;
    message.value = '';
    messageError.value = false;

    runCrawlJob({ period: runPeriod.value, window: runWindow.value, trigger: 'manual' })
        .then(async (data) => {
            messageError.value = false;
            message.value = `Job #${data.job.id} (${jobStatusLabel(data.job.status)}) selesai diproses.`;
            showRunForm.value = false;
            await Promise.all([loadJobs(), loadPeriods()]);
        })
        .catch((error) => {
            messageError.value = true;
            message.value = errorMessage(error, 'Gagal menjalankan crawl.');
        })
        .finally(() => {
            running.value = false;
        });
}

async function openJob(id) {
    selectedId.value = id;
    detail.value = null;
    detailError.value = '';
    detailOpen.value = true;

    try {
        detail.value = await fetchCrawlJob(id);
    } catch (e) {
        detailError.value = errorMessage(e, 'Gagal memuat detail job.');
    }
}

function closeDetail() {
    detailOpen.value = false;
    detail.value = null;
    detailError.value = '';
}

function jobTone(status) {
    return JOB_TONES[status] ?? 'neutral';
}

function logTone(status) {
    return LOG_TONES[status] ?? 'neutral';
}
</script>
