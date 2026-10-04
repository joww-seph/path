<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import CategoryIcon from '@/components/guide/CategoryIcon.vue';
import ListingStatusBadge from '@/components/listings/ListingStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { formatPeso } from '@/lib/format';
import partner from '@/routes/partner';
import type { ListingCard, VerificationStatus } from '@/types';

defineProps<{
    business: {
        id: number;
        name: string;
        verification_status: VerificationStatus;
    };
    listings: ListingCard[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My listings', href: partner.listings.index() }],
    },
});

const { t } = useTrans();
</script>

<template>
    <Head :title="t('My listings')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="t('My listings')"
                :description="
                    t('What tourists can find and book from :business.', {
                        business: business.name,
                    })
                "
            />
            <Button as-child>
                <Link :href="partner.listings.create()"
                    ><Plus /> {{ t('New listing') }}</Link
                >
            </Button>
        </div>

        <p
            v-if="business.verification_status !== 'approved'"
            class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100"
        >
            {{
                t(
                    'You can prepare drafts now. You can submit them for approval once the tourism office verifies your business.',
                )
            }}
        </p>

        <div
            v-if="listings.length === 0"
            class="rounded-xl border border-dashed p-10 text-center"
        >
            <p class="font-medium">{{ t('No listings yet.') }}</p>
            <p class="mt-1 text-sm text-muted-foreground">
                {{
                    t(
                        'Add your rooms, rides, tours or menu so tourists can find you.',
                    )
                }}
            </p>
        </div>

        <ul v-else class="divide-y rounded-xl border">
            <li v-for="listing in listings" :key="listing.id">
                <Link
                    :href="partner.listings.edit(listing.slug)"
                    class="flex items-center gap-4 p-4 hover:bg-accent/50"
                >
                    <img
                        loading="lazy"
                        v-if="listing.photo"
                        :src="listing.photo"
                        alt=""
                        class="size-16 rounded-md object-cover"
                    />
                    <span
                        v-else
                        class="flex size-16 items-center justify-center rounded-md"
                        :style="{
                            backgroundColor: `${listing.category?.color}1a`,
                            color: listing.category?.color,
                        }"
                    >
                        <CategoryIcon
                            :icon="listing.category?.icon"
                            class="size-6"
                        />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-medium">{{
                            listing.name
                        }}</span>
                        <span class="block text-sm text-muted-foreground">
                            {{ t(listing.category?.name ?? '') }}
                            <template v-if="listing.starting_price !== null">
                                ·
                                {{
                                    t('From :price', {
                                        price: formatPeso(
                                            listing.starting_price,
                                        ),
                                    })
                                }}</template
                            >
                        </span>
                    </span>
                    <ListingStatusBadge :status="listing.status" />
                </Link>
            </li>
        </ul>
    </div>
</template>
