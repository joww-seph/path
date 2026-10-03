<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { InertiaLinkProps } from '@inertiajs/vue3';

defineProps<{
    label: string;
    value: number | string;
    href?: NonNullable<InertiaLinkProps['href']>;
    hint?: string;
}>();
</script>

<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        class="block rounded-xl border bg-card p-4 text-card-foreground"
        :class="{ 'transition-colors hover:border-primary': href }"
    >
        <p class="text-sm text-muted-foreground">{{ label }}</p>
        <p class="mt-1 text-3xl font-semibold tabular-nums">{{ value }}</p>
        <p v-if="hint" class="mt-1 text-xs text-muted-foreground">{{ hint }}</p>
    </component>
</template>
