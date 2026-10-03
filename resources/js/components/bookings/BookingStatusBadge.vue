<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { useTrans } from '@/composables/useTrans';
import type { BookingStatus } from '@/types';

defineProps<{
    status: BookingStatus;
    label: string;
}>();

const { t } = useTrans();

const styles: Record<BookingStatus, string> = {
    pending:
        'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200',
    confirmed:
        'border-green-300 bg-green-50 text-green-800 dark:border-green-700 dark:bg-green-950 dark:text-green-200',
    completed:
        'border-blue-300 bg-blue-50 text-blue-800 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-200',
    declined:
        'border-red-300 bg-red-50 text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-200',
    cancelled:
        'border-slate-300 bg-slate-50 text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300',
    expired:
        'border-slate-300 bg-slate-50 text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300',
    no_show:
        'border-red-300 bg-red-50 text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-200',
};
</script>

<template>
    <Badge variant="outline" :class="styles[status]">{{ t(label) }}</Badge>
</template>
