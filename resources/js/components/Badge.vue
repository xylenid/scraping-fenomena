<template>
    <span :class="badgeClass(tone)"><slot /></span>
</template>

<script setup>
import { badgeClass } from '../lib/format';

defineProps({
    tone: { type: String, default: 'neutral' },
});
</script>
