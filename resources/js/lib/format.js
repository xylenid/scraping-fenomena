const CATEGORY_LABELS = {
    commodity: 'Komoditas',
    policy: 'Kebijakan',
    logistics: 'Logistik',
    other: 'Lainnya',
    unclear: 'Belum jelas',
};

const FEED_CATEGORY_LABELS = {
    news: 'Umum',
    market: 'Pasar',
    ekonomi: 'Ekonomi',
    finance: 'Keuangan',
    bisnis: 'Bisnis',
};

const ENTITY_LABELS = {
    commodity: 'Komoditas',
    country: 'Negara',
    org: 'Organisasi',
    location: 'Lokasi',
};

const JOB_STATUS_LABELS = {
    queued: 'Menunggu',
    running: 'Berjalan',
    completed: 'Selesai',
    partial: 'Sebagian gagal',
    failed: 'Gagal',
};

const SOURCE_LOG_STATUS_LABELS = {
    running: 'Berjalan',
    success: 'Sukses',
    partial: 'Sebagian',
    failed: 'Gagal',
    skipped: 'Dilewati',
};

const ERROR_TYPE_LABELS = {
    timeout: 'Timeout',
    selector: 'Selector',
    http_4xx: 'HTTP 4xx',
    http_5xx: 'HTTP 5xx',
    js: 'JavaScript',
    parse: 'Parsing',
    other: 'Lainnya',
};

const BADGE_TONES = {
    neutral: 'bg-slate-100 text-slate-600 ring-slate-200',
    accent: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    info: 'bg-sky-50 text-sky-700 ring-sky-200',
    warn: 'bg-amber-50 text-amber-700 ring-amber-200',
    danger: 'bg-rose-50 text-rose-700 ring-rose-200',
    muted: 'bg-slate-50 text-slate-500 ring-slate-200',
};

const JOB_STATUS_TONES = {
    queued: 'info',
    running: 'info',
    completed: 'accent',
    partial: 'warn',
    failed: 'danger',
};

const SOURCE_LOG_STATUS_TONES = {
    running: 'info',
    success: 'accent',
    partial: 'warn',
    failed: 'danger',
    skipped: 'muted',
};

const CATEGORY_TONES = {
    commodity: 'accent',
    policy: 'info',
    logistics: 'warn',
    other: 'neutral',
    unclear: 'muted',
};

const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

function capitalize(value) {
    return value ? value.charAt(0).toUpperCase() + value.slice(1) : value;
}

function label(map, value) {
    return map[value] ?? capitalize(value);
}

export const categoryLabel = (value) => label(CATEGORY_LABELS, value);
export const feedCategoryLabel = (value) => label(FEED_CATEGORY_LABELS, value);
export const entityLabel = (value) => label(ENTITY_LABELS, value);
export const jobStatusLabel = (value) => label(JOB_STATUS_LABELS, value);
export const sourceLogStatusLabel = (value) => label(SOURCE_LOG_STATUS_LABELS, value);
export const errorTypeLabel = (value) => label(ERROR_TYPE_LABELS, value);
export const windowLabel = (value) => (value === 'addon' ? 'Addon' : 'Main');

export function badgeClass(tone = 'neutral') {
    return [
        'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset',
        BADGE_TONES[tone] ?? BADGE_TONES.neutral,
    ].join(' ');
}

export const categoryBadgeClass = (value) => badgeClass(CATEGORY_TONES[value]);
export const jobStatusBadgeClass = (value) => badgeClass(JOB_STATUS_TONES[value]);
export const sourceLogStatusBadgeClass = (value) => badgeClass(SOURCE_LOG_STATUS_TONES[value]);
export const windowBadgeClass = (value) => badgeClass(value === 'addon' ? 'warn' : 'neutral');

export function formatDate(value, { withTime = false } = {}) {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '—';
    return date.toLocaleString('id-ID', withTime
        ? { dateStyle: 'medium', timeStyle: 'short' }
        : { dateStyle: 'medium' });
}

export function formatNumber(value) {
    return new Intl.NumberFormat('id-ID').format(value ?? 0);
}

export function formatPeriod(value) {
    if (!value) return '—';
    const [year, month] = String(value).split('-');
    const index = Number(month) - 1;
    if (!year || Number.isNaN(index) || index < 0 || index > 11) return value;
    return `${MONTHS_SHORT[index]} ${year}`;
}

export function formatDuration(startedAt, finishedAt) {
    if (!startedAt || !finishedAt) return '—';
    const start = new Date(startedAt).getTime();
    const end = new Date(finishedAt).getTime();
    if (Number.isNaN(start) || Number.isNaN(end) || end < start) return '—';

    const seconds = Math.round((end - start) / 1000);
    if (seconds < 60) return `${seconds} detik`;

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes} menit`;

    return `${Math.floor(minutes / 60)} jam ${minutes % 60} menit`;
}

export function periodOffset(months) {
    const base = new Date();
    const shifted = new Date(base.getFullYear(), base.getMonth() + months, 1);
    return `${shifted.getFullYear()}-${String(shifted.getMonth() + 1).padStart(2, '0')}`;
}
