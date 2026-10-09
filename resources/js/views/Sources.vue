<template>
    <div class="space-y-6">
        <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ error }}
        </p>

        <div v-if="loading" class="card px-4 py-12 text-center text-sm text-slate-500">Memuat sumber berita…</div>

        <div v-else-if="sources.length" class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <section v-for="source in sources" :key="source.code" class="card">
                <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div class="min-w-0">
                        <h3 class="card-title">{{ source.name }}</h3>
                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ source.domain }}</p>
                    </div>
                    <Badge :tone="source.enabled ? 'accent' : 'muted'">{{ source.enabled ? 'Aktif' : 'Nonaktif' }}</Badge>
                </header>

                <dl class="grid grid-cols-3 gap-4 px-5 py-4">
                    <div>
                        <dt class="text-xs text-slate-500">Artikel</dt>
                        <dd class="num mt-1 text-slate-900">{{ formatNumber(source.articles_count) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Rate limit</dt>
                        <dd class="num mt-1 text-slate-900">{{ source.rate_limit_per_min }}/menit</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-500">Playwright</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ source.playwright_enabled ? 'Aktif' : 'Tidak' }}</dd>
                    </div>
                </dl>

                <div class="border-t border-slate-100 px-5 py-4">
                    <div class="flex items-baseline justify-between">
                        <h4 class="text-xs font-semibold text-slate-700">RSS Feed</h4>
                        <span class="text-[11px] text-slate-400">{{ source.rss_feeds?.length ?? 0 }} feed</span>
                    </div>
                    <ul v-if="source.rss_feeds?.length" class="mt-2.5 space-y-2">
                        <li v-for="feed in source.rss_feeds" :key="feed.url" class="flex items-start gap-2">
                            <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full bg-slate-300"></span>
                            <div class="min-w-0">
                                <p class="truncate text-xs text-slate-700" :title="feed.url">{{ feedUrl(feed.url) }}</p>
                                <p v-if="feed.category" class="text-[11px] text-slate-400">{{ feedCategoryLabel(feed.category) }}</p>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="mt-2 text-xs text-slate-500">Sumber ini tidak memakai RSS feed.</p>
                </div>
            </section>
        </div>

        <EmptyState
            v-else
            title="Belum ada sumber berita"
            description="Jalankan php artisan sources:seed untuk mengisi konfigurasi sumber."
        />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Badge from '../components/Badge.vue';
import EmptyState from '../components/EmptyState.vue';
import { errorMessage, fetchSources } from '../lib/api';
import { feedCategoryLabel, formatNumber } from '../lib/format';

const sources = ref([]);
const loading = ref(true);
const error = ref('');

onMounted(async () => {
    try {
        sources.value = await fetchSources();
    } catch (e) {
        error.value = errorMessage(e, 'Gagal memuat sumber berita.');
    } finally {
        loading.value = false;
    }
});

function feedUrl(url) {
    try {
        const { pathname } = new URL(url);
        return pathname === '/' ? url : pathname;
    } catch {
        return url;
    }
}
</script>
