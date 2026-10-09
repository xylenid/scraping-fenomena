<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-800 bg-slate-900 transition-transform duration-200 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 items-center gap-3 border-b border-slate-800 px-5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-400 ring-1 ring-inset ring-emerald-500/30">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 15V9m4 6V7m4 8v-4" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold tracking-tight text-white">Scraping Fenomena</div>
                    <div class="truncate text-[11px] text-slate-400">Berita ekonomi bulanan</div>
                </div>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <RouterLink
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                    :class="isActive(item.to)
                        ? 'bg-slate-800 text-white'
                        : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100'"
                >
                    <svg
                        class="h-4 w-4 shrink-0"
                        :class="isActive(item.to) ? 'text-emerald-400' : 'text-slate-500 group-hover:text-slate-300'"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                    {{ item.label }}
                </RouterLink>
            </nav>

            <div class="border-t border-slate-800 px-5 py-4">
                <p class="text-[11px] leading-relaxed text-slate-500">
                    Menyediakan explanatory variables untuk analisis ekspor Indonesia.
                </p>
            </div>
        </aside>

        <!-- Overlay mobile -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <!-- Main -->
        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/85 backdrop-blur">
                <div class="flex h-16 items-center gap-3 px-4 lg:px-8">
                    <button
                        class="-ml-1 rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900 lg:hidden"
                        aria-label="Buka navigasi"
                        @click="sidebarOpen = !sidebarOpen"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>
                    <div class="min-w-0">
                        <h1 class="truncate text-[15px] font-semibold tracking-tight text-slate-900">{{ pageTitle }}</h1>
                        <p class="truncate text-xs text-slate-500">{{ pageSubtitle }}</p>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 lg:px-8 lg:py-8">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const sidebarOpen = ref(false);

const navItems = [
    { to: '/', label: 'Dashboard', icon: 'M4 5h6v6H4zM14 5h6v4h-6zM4 15h6v4H4zM14 13h6v6h-6z' },
    { to: '/articles', label: 'Artikel', icon: 'M5 4h9l5 5v11H5zM14 4v5h5M8 13h8M8 17h5' },
    { to: '/crawl-jobs', label: 'Crawl Jobs', icon: 'M12 7v5l3 2M4.5 12a7.5 7.5 0 1 0 15 0 7.5 7.5 0 0 0-15 0z' },
    { to: '/sources', label: 'Sumber Berita', icon: 'M12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16zM4 12h16M12 4c2 2.2 3 5 3 8s-1 5.8-3 8c-2-2.2-3-5-3-8s1-5.8 3-8z' },
];

const pageMeta = {
    dashboard: { title: 'Dashboard', subtitle: 'Ringkasan pengumpulan berita bulanan' },
    articles: { title: 'Artikel Berita', subtitle: 'Telusuri hasil pengumpulan per periode dan kategori' },
    'article-detail': { title: 'Detail Artikel', subtitle: 'Isi lengkap, klasifikasi, dan entitas terdeteksi' },
    'crawl-jobs': { title: 'Crawl Jobs', subtitle: 'Riwayat proses crawl dan hasilnya' },
    sources: { title: 'Sumber Berita', subtitle: 'Konfigurasi sumber dan status feed' },
};

const pageTitle = computed(() => pageMeta[route.name]?.title ?? 'Scraping Fenomena');
const pageSubtitle = computed(() => pageMeta[route.name]?.subtitle ?? '');

watch(
    () => route.fullPath,
    () => {
        sidebarOpen.value = false;
    },
);

function isActive(to) {
    if (to === '/') return route.path === '/';
    return route.path.startsWith(to);
}
</script>