<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div v-for="s in sources" :key="s.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-semibold text-slate-900">{{ s.name }}</h3>
                    <p class="text-xs text-slate-500">{{ s.domain }}</p>
                </div>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="s.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                    {{ s.enabled ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>

            <div class="mt-4 space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Artikel tersimpan</span>
                    <span class="font-semibold text-slate-900">{{ s.articles_count }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Rate limit</span>
                    <span class="font-semibold text-slate-900">{{ s.rate_limit_per_min }}/menit</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Playwright</span>
                    <span class="font-semibold text-slate-900">{{ s.playwright_enabled ? 'Ya' : 'Tidak' }}</span>
                </div>
            </div>

            <div class="mt-4 border-t border-slate-100 pt-3">
                <div class="text-xs font-medium text-slate-500">RSS Feeds</div>
                <ul class="mt-1 space-y-1">
                    <li v-for="(feed, i) in s.rss_feeds" :key="i" class="truncate text-xs text-slate-600" :title="feed.url">
                        • {{ feed.url }}
                    </li>
                    <li v-if="!s.rss_feeds?.length" class="text-xs text-slate-400">Tidak ada feed</li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';

const sources = ref([]);

onMounted(async () => {
    try {
        const { data } = await axios.get('/sources');
        sources.value = Array.isArray(data) ? data : [];
    } catch (e) {
        console.error(e);
    }
});
</script>