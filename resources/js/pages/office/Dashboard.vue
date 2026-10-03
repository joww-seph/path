<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import StatCard from '@/components/StatCard.vue';
import { useTrans } from '@/composables/useTrans';
import office from '@/routes/office';

defineProps<{
    stats: {
        pendingPartners: number;
        approvedPartners: number;
        pendingListings: number;
        publishedListings: number;
        upcomingEvents: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Office dashboard', href: office.dashboard() }],
    },
});

const { t } = useTrans();
</script>

<template>
    <Head :title="t('Office dashboard')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('Paoay Municipal Tourism Office')"
            :description="
                t(
                    'Verify partners, publish information and watch visitor demand.',
                )
            "
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                :label="t('Partners awaiting verification')"
                :value="stats.pendingPartners"
                :href="office.partners.index()"
            />
            <StatCard
                :label="t('Listings awaiting approval')"
                :value="stats.pendingListings"
                :href="office.listings.index({ query: { status: 'pending' } })"
            />
            <StatCard
                :label="t('Published listings')"
                :value="stats.publishedListings"
                :href="
                    office.listings.index({ query: { status: 'published' } })
                "
            />
            <StatCard
                :label="t('Upcoming events')"
                :value="stats.upcomingEvents"
                :href="office.events.index()"
            />
        </div>
    </div>
</template>
