<script setup lang="ts">
import { computed } from 'vue';
import { useTrans } from '@/composables/useTrans';
import { formatClock } from '@/lib/format';
import type { OpeningHours } from '@/types';

const props = defineProps<{
    hours: OpeningHours | null;
}>();

const { t } = useTrans();

const days = [
    ['mon', 'Monday'],
    ['tue', 'Tuesday'],
    ['wed', 'Wednesday'],
    ['thu', 'Thursday'],
    ['fri', 'Friday'],
    ['sat', 'Saturday'],
    ['sun', 'Sunday'],
] as const;

// JavaScript weeks start on Sunday (0); ours start on Monday.
const today = computed(() => days[(new Date().getDay() + 6) % 7][0]);
</script>

<template>
    <p v-if="hours === null" class="text-sm text-muted-foreground">
        {{ t('Open all day, or hours not yet confirmed.') }}
    </p>
    <table v-else class="w-full text-sm">
        <tbody>
            <tr
                v-for="[key, label] in days"
                :key="key"
                :class="{ 'font-semibold': key === today }"
            >
                <th scope="row" class="py-1 pr-4 text-left font-normal">
                    {{ t(label) }}
                </th>
                <td class="py-1 text-right">
                    <template v-if="props.hours?.[key]">
                        {{ formatClock(props.hours[key].open) }} –
                        {{ formatClock(props.hours[key].close) }}
                    </template>
                    <span v-else class="text-muted-foreground">{{
                        t('Closed')
                    }}</span>
                </td>
            </tr>
        </tbody>
    </table>
</template>
