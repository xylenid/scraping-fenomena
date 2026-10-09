<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Ringkasan pengumpulan</h2>
                <p class="text-xs text-slate-500">
                    Data berita ekonomi sebagai explanatory variables analisis ekspor.
                </p>
            </div>
            <div class="w-44">
                <label class="field-label" for="dashboard-period">Periode target</label>
                <select id="dashboard-period" v-model="period" class="input" @change="load">
                    <option value="">Semua periode</option>
                    <option v-for="p in stats.periods ?? []" :key="p" :value="p">{{ formatPeriod(p) }}</option>
                </select>
            </div>
        </div>

        <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ error }}
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                label="Total Artikel"
                :value="formatNumber(stats.total_articles)"
                :sub="period ? `Periode ${formatPeriod(period)}` : 'Seluruh periode tersimpan'"
            />
            <StatCard
                label="Perlu Review"
                :value="formatNumber(stats.needs_review)"
                sub="Kategori atau tanggal belum pasti"
            />
            <StatCard
                label="Artikel Addon"
                :value="formatNumber(stats.addon_articles)"
                sub="Tanggal kejadian 1–10 bulan berikutnya"
            />
            <StatCard
                label="Sumber Aktif"
                :value="formatNumber(stats.total_sources)"
                sub="Media yang ikut terjadwal crawl"
            />
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section class="card">
                <header class="flex items-baseline justify-between border-b border-slate-200 px-5 py-4">
                    <h3 class="card-title">Kategori Fenomena</h3>
                    <span class="card-subtitle">Klasifikasi utama</span>
                </header>
                <div v-if="categoryRows.length" class="space-y-3.5 px-5 py-5">
                    <div v-for="row in categoryRows" :key="row.key" class="grid grid-cols-[7rem_1fr_2.5rem] items-center gap-3">
                        <span class="truncate text-xs font-medium text-slate-600">{{ row.label }}</span>
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-emerald-500" :style="{ width: barWidth(row.count) }"></div>
                        </div>
                        <span class="num text-right text-slate-700">{{ row.count }}</span>
                    </div>
                </div>
                <EmptyState v-else title="Belum ada klasifikasi" description="Jalankan crawl untuk mengisi kategori artikel." />
            </section>

            <section class="card">
                <header class="flex items-baseline justify-between border-b border-slate-200 px-5 py-4">
                    <h3 class="card-title">Artikel per Sumber</h3>
                    <span class="card-subtitle">Diurutkan terbanyak</span>
                </header>
                <div v-if="sourceRows.length" class="space-y-3.5 px-5 py-5">
                    <div v-for="row in sourceRows" :key="row.code ?? row.source" class="grid grid-cols-[7rem_1fr_2.5rem] items-center gap-3">
                        <span class="truncate text-xs font-medium text-slate-600" :title="row.source">{{ row.source }}</span>
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-slate-400" :style="{ width: barWidth(row.total) }"></div>
                        </div>
                        <span class="num text-right text-slate-700">{{ row.total }}</span>
                    </div>
                </div>
                <EmptyState v-else title="Belum ada artikel tersimpan" description="Crawl pertama akan mengisi data per sumber." />
            </section>
        </div>

        <section class="card">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <h3 class="card-title">Crawl Job Terakhir</h3>
                <RouterLink to="/crawl-jobs" class="text-xs font-medium text-slate-500 transition-colors hover:text-slate-900">
                    Lihat semua job
                </RouterLink>
            </header>

            <div v-if="job" class="grid grid-cols-2 gap-x-6 gap-y-5 px-5 py-5 sm:grid-cols-4">
                <div>
                    <p class="text-xs text-slate-500">Periode</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ formatPeriod(job.period) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Window</p>
                    <p class="mt-1">
                        <Badge :tone="job.window_type === 'addon' ? 'warn' : 'neutral'">{{ windowLabel(job.window_type) }}</Badge>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Status</p>
                    <p class="mt-1"><Badge :tone="jobTone(job.status)">{{ jobStatusLabel(job.status) }}</Badge></p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Artikel disimpan</p>
                    <p class="num mt-1 text-slate-900">{{ formatNumber(job.stats?.kept) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Ditemukan</p>
                    <p class="num mt-1 text-slate-700">{{ formatNumber(job.stats?.fetched) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Duplikat</p>
                    <p class="num mt-1 text-slate-700">{{ formatNumber(job.stats?.skipped_duplicate) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Gagal</p>
                    <p class="num mt-1 text-slate-700">{{ formatNumber(job.stats?.failed) }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-500">Selesai</p>
                    <p class="mt-1 text-sm text-slate-700">{{ formatDate(job.finished_at, { withTime: true }) }}</p>
                </div>
            </div>

            <EmptyState v-else title="Belum ada crawl job" description="Job pertama muncul setelah crawl terjadwal atau manual dijalankan." />
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import Badge from '../components/Badge.vue';
import EmptyState from '../components/EmptyState.vue';
import StatCard from '../components/StatCard.vue';
import { fetchDashboardStats, errorMessage } from '../lib/api';
import { categoryLabel, formatDate, formatNumber, formatPeriod, jobStatusLabel, windowLabel } from '../lib/format';

const JOB_TONES = { completed: 'accent', partial: 'warn', failed: 'danger', running: 'info', queued: 'info' };

const stats = ref({});
const period = ref('');
const error = ref('');

onMounted(load);

async function load() {
    error.value = '';
    try {
        stats.value = await fetchDashboardStats(period.value ? { period: period.value } : undefined);
    } catch (e) {
        error.value = errorMessage(e, 'Gagal memuat ringkasan dashboard.');
    }
}

const categoryRows = computed(() =>
    Object.entries(stats.value.by_category ?? {}).map(([key, count]) => ({
        key,
        label: categoryLabel(key),
        count,
    })),
);

const sourceRows = computed(() => stats.value.by_source ?? []);

const job = computed(() => stats.value.latest_job ?? null);

const maxValue = computed(() => {
    const counts = [
        ...categoryRows.value.map((row) => row.count),
        ...sourceRows.value.map((row) => row.total),
    ];
    return Math.max(...counts, 1);
});

function barWidth(count) {
    if (!count) return '0%';
    return `${Math.max(4, Math.round((count / maxValue.value) * 100))}%`;
}

function jobTone(status) {
    return JOB_TONES[status] ?? 'neutral';
}
</script>
