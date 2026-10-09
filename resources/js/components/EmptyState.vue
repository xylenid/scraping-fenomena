<template>
    <div class="flex flex-col items-center justify-center gap-1.5 px-6 py-12 text-center">
        <p class="text-sm font-medium text-slate-700">{{ title }}</p>
        <p v-if="description" class="max-w-sm text-xs leading-relaxed text-slate-500">{{ description }}</p>
        <slot />
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>
