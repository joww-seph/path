<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { useTrans } from '@/composables/useTrans';
import type { ListingStatus } from '@/types';

defineProps<{
    status: ListingStatus;
}>();

const { t } = useTrans();

const labels: Record<ListingStatus, string> = {
    draft: 'Draft',
    pending: 'Awaiting approval',
    published: 'Published',
    rejected: 'Changes requested',
    archived: 'Archived',
};

const styles: Record<ListingStatus, string> = {
    draft: 'border-slate-300 bg-slate-50 text-slate-700 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200',
    pending:
        'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200',
    published:
        'border-green-300 bg-green-50 text-green-800 dark:border-green-700 dark:bg-green-950 dark:text-green-200',
    rejected:
        'border-red-300 bg-red-50 text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-200',
    archived:
        'border-slate-300 bg-slate-100 text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400',
};
</script>

<template>
    <Badge variant="outline" :class="styles[status]">{{
        t(labels[status])
    }}</Badge>
</template>
