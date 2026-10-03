<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { useTrans } from '@/composables/useTrans';
import type { VerificationStatus } from '@/types';

defineProps<{
    status: VerificationStatus;
}>();

const { t } = useTrans();

const styles: Record<VerificationStatus, string> = {
    pending:
        'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200',
    approved:
        'border-green-300 bg-green-50 text-green-800 dark:border-green-700 dark:bg-green-950 dark:text-green-200',
    rejected:
        'border-red-300 bg-red-50 text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-200',
};

const labels: Record<VerificationStatus, string> = {
    pending: 'Awaiting verification',
    approved: 'Verified partner',
    rejected: 'Not approved',
};
</script>

<template>
    <Badge variant="outline" :class="styles[status]">{{
        t(labels[status])
    }}</Badge>
</template>
