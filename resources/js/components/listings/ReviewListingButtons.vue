<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import office from '@/routes/office';

const props = defineProps<{
    listingSlug: string;
    listingName: string;
}>();

const { t } = useTrans();

function approve() {
    router.post(
        office.listings.review.url(props.listingSlug),
        { decision: 'approve' },
        { preserveScroll: true },
    );
}

function reject() {
    const note = prompt(
        t('What should the partner change before :name can go live?', {
            name: props.listingName,
        }),
    );

    if (note) {
        router.post(
            office.listings.review.url(props.listingSlug),
            { decision: 'reject', note },
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <div class="flex gap-2">
        <Button size="sm" @click="approve">{{ t('Approve') }}</Button>
        <Button size="sm" variant="outline" @click="reject">{{
            t('Request changes')
        }}</Button>
    </div>
</template>
