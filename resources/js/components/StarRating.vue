<script setup lang="ts">
import { Star } from '@lucide/vue';
import { useTrans } from '@/composables/useTrans';

const props = withDefaults(
    defineProps<{
        rating: number;
        size?: string;
    }>(),
    { size: 'size-4' },
);

const { t } = useTrans();
</script>

<template>
    <span
        class="inline-flex items-center gap-0.5"
        role="img"
        :aria-label="t(':rating out of 5 stars', { rating: props.rating })"
    >
        <Star
            v-for="index in 5"
            :key="index"
            :class="[
                size,
                index <= Math.round(rating)
                    ? 'fill-gold text-gold'
                    : 'text-muted-foreground/40',
            ]"
            aria-hidden="true"
        />
    </span>
</template>
