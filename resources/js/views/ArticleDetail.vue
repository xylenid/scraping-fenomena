<template>
    <div v-if="article" class="mx-auto max-w-3xl space-y-5">
        <button class="btn btn-ghost -ml-2 px-2 py-1 text-xs" @click="$router.back()">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6" />
            </svg>
            Kembali
        </button>

        <article class="card card-pad">
            <div class="flex flex-wrap items-center gap-2">
                <Badge tone="neutral">{{ article.source?.name }}</Badge>
                <Badge :tone="categoryTone(article.category_primary)">{{ categoryLabel(article.category_primary) }}</Badge>
                <Badge v-if="article.is_addon" tone="info">Addon</Badge>
                <Badge v-if="article.needs_review" tone="warn">Perlu review</Badge>
            </div>

            <h2 class="mt-3 text-xl font-semibold leading-snug tracking-tight text-slate-900">{{ article.title }}</h2>

            <p class="mt-2 text-xs text-slate-500">
                {{ article.author || 'Tanpa penulis' }} · Terbit {{ formatDate(article.published_at, { withTime: true }) }}
                <template v-if="article.event_date">
                    · Kejadian {{ formatDate(article.event_date) }} ({{ article.event_date_confidence }})
                </template>
            </p>

            <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-4 border-t border-slate-100 pt-5 sm:grid-cols-4">
                <div>
                    <dt class="text-xs text-slate-500">Periode target</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ formatPeriod(article.period_target) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Panjang teks</dt>
                    <dd class="num mt-1 text-slate-900">{{ formatNumber(article.content_length) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Confidence kategori</dt>
                    <dd class="num mt-1 text-slate-900">{{ article.category_confidence ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Entitas</dt>
                    <dd class="num mt-1 text-slate-900">{{ formatNumber(article.entities?.length) }}</dd>
                </div>
            </dl>

            <p v-if="article.category_secondary?.length" class="mt-4 text-xs text-slate-500">
                Kategori sekunder: {{ article.category_secondary.map(categoryLabel).join(', ') }}
            </p>

            <a
                :href="article.url"
                target="_blank"
                rel="noopener"
                class="mt-5 inline-flex items-center gap-1.5 text-sm font-medium text-slate-900 underline decoration-slate-300 underline-offset-4 transition-colors hover:decoration-slate-900"
            >
                Buka artikel asli
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M9 7h8v8" />
                </svg>
            </a>
        </article>

        <section class="card">
            <header class="border-b border-slate-200 px-5 py-4">
                <h3 class="card-title">Isi Artikel</h3>
            </header>
            <div class="px-5 py-5">
                <p v-if="article.content?.content_cleaned" class="whitespace-pre-wrap text-[15px] leading-relaxed text-slate-700">
                    {{ article.content.content_cleaned }}
                </p>
                <p v-else class="text-sm text-slate-500">Isi artikel belum tersimpan.</p>
            </div>
        </section>

        <section v-if="article.entities?.length" class="card">
            <header class="flex items-baseline justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="card-title">Entitas Terdeteksi</h3>
                <span class="card-subtitle">{{ article.entities.length }} entitas</span>
            </header>
            <div class="px-5 py-5">
                <ul class="flex flex-wrap gap-2">
                    <li
                        v-for="entity in article.entities"
                        :key="entity.id"
                        class="inline-flex items-center gap-1.5 rounded-md bg-slate-50 px-2.5 py-1 text-xs text-slate-700 ring-1 ring-inset ring-slate-200"
                    >
                        <span class="text-slate-400">{{ entityLabel(entity.entity_type) }}</span>
                        {{ entity.entity_name }}
                    </li>
                </ul>
            </div>
        </section>
    </div>

    <div v-else-if="error" class="card px-4 py-12 text-center text-sm text-rose-600">{{ error }}</div>

    <div v-else class="card px-4 py-12 text-center text-sm text-slate-500">Memuat artikel…</div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Badge from '../components/Badge.vue';
import { errorMessage, fetchArticle } from '../lib/api';
import { categoryLabel, entityLabel, formatDate, formatNumber, formatPeriod } from '../lib/format';

const CATEGORY_TONES = { commodity: 'accent', policy: 'info', logistics: 'warn' };

const props = defineProps({ id: { type: [String, Number], required: true } });

const article = ref(null);
const error = ref('');

onMounted(async () => {
    try {
        article.value = await fetchArticle(props.id);
    } catch (e) {
        error.value = errorMessage(e, 'Gagal memuat artikel.');
    }
});

function categoryTone(category) {
    return CATEGORY_TONES[category] ?? 'muted';
}
</script>
