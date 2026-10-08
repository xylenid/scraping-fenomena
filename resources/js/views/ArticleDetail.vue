<template>
    <div v-if="article" class="mx-auto max-w-4xl space-y-4">
        <button class="text-sm font-medium text-emerald-600 hover:text-emerald-700" @click="$router.back()">← Kembali</button>

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ article.source?.name }}</span>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="categoryClass(article.category_primary)">
                    {{ categoryLabel(article.category_primary) }}
                </span>
                <span v-if="article.is_addon" class="rounded-full bg-violet-100 px-2.5 py-0.5 text-xs font-medium text-violet-700">Addon</span>
                <span v-if="article.needs_review" class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Perlu Review</span>
            </div>

            <h1 class="text-2xl font-bold leading-snug text-slate-900">{{ article.title }}</h1>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                <span v-if="article.author">✍️ {{ article.author }}</span>
                <span>📅 {{ formatDate(article.published_at) }}</span>
                <span v-if="article.event_date">🗓️ Kejadian: {{ article.event_date }} ({{ article.event_date_confidence }})</span>
            </div>

            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-600">Periode target: {{ article.period_target }}</span>
                <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-600">Panjang: {{ article.content_length }} karakter</span>
                <span class="rounded-md bg-slate-100 px-2 py-1 text-slate-600">Confidence: {{ article.category_confidence }}</span>
                <span v-if="article.category_secondary?.length" class="rounded-md bg-slate-100 px-2 py-1 text-slate-600">
                    Sekunder: {{ article.category_secondary.map(categoryLabel).join(', ') }}
                </span>
            </div>

            <a :href="article.url" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-emerald-600 hover:text-emerald-700">
                Buka artikel asli ↗
            </a>
        </div>

        <!-- Konten -->
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Isi Artikel</h2>
            <div class="whitespace-pre-wrap text-[15px] leading-relaxed text-slate-700">{{ article.content?.content_cleaned }}</div>
        </div>

        <!-- Entitas -->
        <div v-if="article.entities?.length" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Entitas Terdeteksi</h2>
            <div class="flex flex-wrap gap-2">
                <span v-for="e in article.entities" :key="e.id" class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">
                    {{ entityLabel(e.entity_type) }}: {{ e.entity_name }}
                </span>
            </div>
        </div>
    </div>

    <div v-else class="py-20 text-center text-sm text-slate-400">Memuat artikel...</div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';

const props = defineProps({ id: { type: [String, Number], required: true } });
const article = ref(null);

onMounted(async () => {
    try {
        const { data } = await axios.get(`/articles/${props.id}`);
        if (data && typeof data === 'object') article.value = data;
    } catch (e) {
        console.error(e);
    }
});

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

function entityLabel(type) {
    return { commodity: 'Komoditas', country: 'Negara', org: 'Organisasi', location: 'Lokasi' }[type] ?? type;
}

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
}
</script>