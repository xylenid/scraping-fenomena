<template>
    <div class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-600">Periode</label>
                    <select v-model="period" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="">Semua</option>
                        <option v-for="p in periods" :key="p" :value="p">{{ p }}</option>
                    </select>
                </div>
                <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800" @click="loadJobs">
                    Filter
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Periode</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Window</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Artikel</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Mulai</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Selesai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="job in jobs" :key="job.id" class="cursor-pointer transition-colors hover:bg-slate-50" @click="openJob(job.id)">
                            <td class="px-4 py-3 font-medium text-slate-900">#{{ job.id }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ job.period }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="job.window_type === 'addon' ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-600'">
                                    {{ job.window_type }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(job.status)">{{ job.status }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ job.articles_count }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(job.started_at) }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(job.finished_at) }}</td>
                        </tr>
                        <tr v-if="!jobs.length">
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-400">Belum ada crawl job.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const jobs = ref([]);
const periods = ref([]);
const period = ref('');

onMounted(async () => {
    await loadJobs();
});

async function loadJobs() {
    try {
        const params = {};
        if (period.value) params.period = period.value;
        const { data } = await axios.get('/crawl-jobs', { params });
        const rows = Array.isArray(data.data) ? data.data : [];
        jobs.value = rows;
        periods.value = [...new Set(rows.map((j) => j.period))];
    } catch (e) {
        console.error(e);
    }
}

function openJob(id) {
    // Detail job bisa ditambahkan; untuk sekarang tampilkan alert sederhana
    alert(`Job #${id} — detail lengkap dapat dilihat via API /crawl-jobs/${id}`);
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
</script>