<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import ListingForm from '@/components/listings/ListingForm.vue';
import ListingStatusBadge from '@/components/listings/ListingStatusBadge.vue';
import PhotoManager from '@/components/listings/PhotoManager.vue';
import RateManager from '@/components/listings/RateManager.vue';
import ReviewListingButtons from '@/components/listings/ReviewListingButtons.vue';
import StoryManager from '@/components/listings/StoryManager.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import listingsRoutes from '@/routes/listings';
import office from '@/routes/office';
import type {
    Barangay,
    Category,
    LatLng,
    ListingDetail,
    Option,
} from '@/types';

const props = defineProps<{
    listing: ListingDetail | null;
    categories: Category[];
    barangays: Barangay[];
    rateUnits: Option[];
    center: LatLng;
    canFeature: boolean;
}>();

const { t } = useTrans();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Listings', href: office.listings.index() }],
    },
});

const action = computed(() =>
    props.listing
        ? office.listings.update.form(props.listing.slug)
        : office.listings.store.form(),
);

function toggleArchive() {
    if (props.listing) {
        router.post(
            office.listings.archive.url(props.listing.slug),
            {},
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head :title="listing ? listing.name : t('Add a listing')" />

    <div class="mx-auto w-full max-w-4xl space-y-10 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="listing ? listing.name : t('Add a listing')"
                :description="
                    listing?.business
                        ? t('Managed by :business', {
                              business: listing.business.name,
                          })
                        : t('Managed by the tourism office')
                "
            />
            <div v-if="listing" class="flex items-center gap-2">
                <ListingStatusBadge :status="listing.status" />
                <Button as-child variant="ghost" size="sm">
                    <Link :href="listingsRoutes.show(listing.slug)"
                        ><ExternalLink /> {{ t('View') }}</Link
                    >
                </Button>
            </div>
        </div>

        <div
            v-if="listing?.status === 'pending'"
            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950"
        >
            <p class="text-sm">
                {{ t('This partner listing is waiting for your approval.') }}
            </p>
            <ReviewListingButtons
                :listing-slug="listing.slug"
                :listing-name="listing.name"
            />
        </div>

        <ListingForm
            :action="action"
            :listing="listing"
            :categories="categories"
            :barangays="barangays"
            :center="center"
            :can-feature="canFeature"
            :submit-label="listing ? t('Save changes') : t('Publish listing')"
        >
            <template
                v-if="
                    listing &&
                    ['published', 'archived'].includes(listing.status)
                "
                #actions
            >
                <Button type="button" variant="outline" @click="toggleArchive">
                    {{
                        listing.status === 'archived'
                            ? t('Restore listing')
                            : t('Archive listing')
                    }}
                </Button>
            </template>
        </ListingForm>

        <template v-if="listing">
            <PhotoManager
                :listing-slug="listing.slug"
                :photos="listing.photos ?? []"
            />
            <RateManager
                :listing-slug="listing.slug"
                :rates="listing.rates ?? []"
                :rate-units="rateUnits"
            />
            <StoryManager
                :listing-slug="listing.slug"
                :stories="listing.heritage_stories ?? []"
            />
        </template>
    </div>
</template>
