import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';
import App from './App.vue';
import Dashboard from './views/Dashboard.vue';
import Articles from './views/Articles.vue';
import ArticleDetail from './views/ArticleDetail.vue';
import CrawlJobs from './views/CrawlJobs.vue';
import Sources from './views/Sources.vue';

const apiBase = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/+$/, '');
axios.defaults.baseURL = `${apiBase}/api/v1`;

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'dashboard', component: Dashboard },
        { path: '/articles', name: 'articles', component: Articles },
        { path: '/articles/:id', name: 'article-detail', component: ArticleDetail, props: true },
        { path: '/crawl-jobs', name: 'crawl-jobs', component: CrawlJobs },
        { path: '/sources', name: 'sources', component: Sources },
    ],
});

createApp(App).use(router).mount('#app');
