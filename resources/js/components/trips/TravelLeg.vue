<script setup lang="ts">
import { Bike, Car, Footprints } from '@lucide/vue';
import { useTrans } from '@/composables/useTrans';

defineProps<{
    minutes: number | null;
    km: number | null;
    mode: 'car' | 'tricycle' | 'walk';
}>();

const { t } = useTrans();
</script>

<template>
    <div
        class="ml-28 flex items-center gap-2 border-l-2 border-dashed py-1.5 pl-4 text-xs text-muted-foreground"
    >
        <span v-if="minutes === 0">{{ t('Same place') }}</span>
        <template v-else-if="minutes !== null">
            <Footprints v-if="mode === 'walk'" class="size-3.5" />
            <Bike v-else-if="mode === 'tricycle'" class="size-3.5" />
            <Car v-else class="size-3.5" />
            <span>{{ t('About :minutes min', { minutes }) }}</span>
            <span v-if="km !== null">· {{ km.toFixed(1) }} km</span>
        </template>
        <span v-else>{{ t('Travel time unknown (no map pin)') }}</span>
    </div>
</template>
