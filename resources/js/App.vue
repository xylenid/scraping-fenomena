<template>
    <div class="min-h-screen bg-slate-50">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 text-slate-100 transition-transform duration-200 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 items-center gap-3 border-b border-slate-800 px-5">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500 text-lg font-bold text-white">
                    SF
                </div>
                <div>
                    <div class="text-sm font-semibold leading-tight">Scraping Fenomena</div>
                    <div class="text-[11px] text-slate-400">Berita Ekonomi & Perdagangan</div>
                </div>
            </div>

            <nav class="mt-4 space-y-1 px-3">
                <RouterLink
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                    :class="isActive(item.to) ? 'bg-emerald-500/15 text-emerald-300' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
                >
                    <span class="text-base">{{ item.icon }}</span>
                    {{ item.label }}
                </RouterLink>
            </nav>

            <div class="absolute bottom-0 left-0 right-0 border-t border-slate-800 p-4">
                <div class="text-[11px] leading-relaxed text-slate-500">
                    Data berita sebagai explanatory variables analisis ekspor Indonesia.
                </div>
            </div>
        </aside>

        <!-- Overlay mobile -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <!-- Main -->
        <div class="lg:pl-64">
            <!-- Topbar -->
            <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/80 px-4 backdrop-blur lg:px-8">
                <button class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = !sidebarOpen">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div>
                    <h1 class="text-base font-semibold text-slate-900">{{ pageTitle }}</h1>
                    <p class="text-xs text-slate-500">{{ pageSubtitle }}</p>
                </div>
                <div class="ml-auto flex items-center gap-3">
                    <span class="hidden rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 sm:inline">
                        ● Sistem Aktif
                    </span>
                </div>
            </header>

            <main class="p-4 lg:p-8">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const sidebarOpen = ref(false);

const navItems = [
    { to: '/', label: 'Dashboard', icon: '📊' },
    { to: '/articles', label: 'Artikel', icon: '📰' },
    { to: '/crawl-jobs', label: 'Crawl Jobs', icon: '⚙️' },
    { to: '/sources', label: 'Sumber Berita', icon: '🌐' },
];

const pageTitle = computed(() => {
    const map = {
        dashboard: 'Dashboard',
        articles: 'Artikel Berita',
        'article-detail': 'Detail Artikel',
        'crawl-jobs': 'Crawl Jobs',
        sources: 'Sumber Berita',
    };
    return map[route.name] ?? 'Scraping Fenomena';
});

const pageSubtitle = computed(() => {
    const map = {
        dashboard: 'Ringkasan pengumpulan berita bulanan',
        articles: 'Jelajahi dan filter berita per periode',
        'article-detail': 'Isi lengkap artikel',
        'crawl-jobs': 'Riwayat dan status crawling otomatis',
        sources: 'Konfigurasi sumber berita',
    };
    return map[route.name] ?? '';
});

function isActive(to) {
    if (to === '/') return route.path === '/';
    return route.path.startsWith(to);
}
</script>