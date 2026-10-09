<template>
    <div class="card card-pad">
        <div class="flex items-start justify-between gap-3">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ label }}</p>
            <span v-if="hint" class="text-[11px] text-slate-400">{{ hint }}</span>
        </div>
        <p class="mt-3 text-2xl font-semibold tabular-nums tracking-tight text-slate-900">{{ value }}</p>
        <p v-if="sub" class="mt-1 text-xs text-slate-500">{{ sub }}</p>
    </div>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    sub: { type: String, default: '' },
    hint: { type: String, default: '' },
});
</script>
