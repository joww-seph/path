<script setup lang="ts">
import { CloudOff } from '@lucide/vue';
import { useOnline } from '@/composables/useOnline';
import { useTrans } from '@/composables/useTrans';

const { online } = useOnline();
const { t } = useTrans();
</script>

<template>
    <div
        v-if="!online"
        class="flex items-center gap-2 bg-amber-100 px-4 py-2 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100"
        role="status"
    >
        <CloudOff class="size-4 shrink-0" />
        <span class="flex-1">{{
            t(
                'You are offline. Pages need signal, but your saved trips still work.',
            )
        }}</span>
        <a href="/offline" class="font-medium underline">{{
            t('Open saved trips')
        }}</a>
    </div>
</template>
