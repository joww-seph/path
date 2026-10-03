<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, Send } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import ListingForm from '@/components/listings/ListingForm.vue';
import ListingStatusBadge from '@/components/listings/ListingStatusBadge.vue';
import PhotoManager from '@/components/listings/PhotoManager.vue';
import RateManager from '@/components/listings/RateManager.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import listingsRoutes from '@/routes/listings';
import partner from '@/routes/partner';
import type {
    Barangay,
    Category,
    LatLng,
    ListingDetail,
    Option,
    VerificationStatus,
} from '@/types';

const props = defineProps<{
    listing: ListingDetail | null;
    business: {
        id: number;
        name: string;
        verification_status: VerificationStatus;
    };
    categories: Category[];
    barangays: Barangay[];
    rateUnits: Option[];
    center: LatLng;
    canFeature: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My listings', href: partner.listings.index() }],
    },
});

const { t } = useTrans();

const action = computed(() =>
    props.listing
        ? partner.listings.update.form(props.listing.slug)
        : partner.listings.store.form(),
);

const canSubmit = computed(
    () =>
        props.listing !== null &&
        props.business.verification_status === 'approved' &&
        ['draft', 'rejected'].includes(props.listing.status),
);

function submitForApproval() {
    if (props.listing) {
        router.post(
            partner.listings.submit.url(props.listing.slug),
            {},
            { preserveScroll: true },
        );
    }
}

function destroy() {
    if (
        props.listing &&
        confirm(t('Delete this draft? This cannot be undone.'))
    ) {
        router.delete(partner.listings.destroy.url(props.listing.slug));
    }
}
</script>

<template>
    <Head :title="listing ? listing.name : t('New listing')" />

    <div class="mx-auto w-full max-w-4xl space-y-10 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="listing ? listing.name : t('New listing')"
                :description="business.name"
            />
            <div v-if="listing" class="flex items-center gap-2">
                <ListingStatusBadge :status="listing.status" />
                <Button as-child variant="ghost" size="sm">
                    <Link :href="listingsRoutes.show(listing.slug)"
                        ><ExternalLink /> {{ t('Preview') }}</Link
                    >
                </Button>
            </div>
        </div>

        <div
            v-if="listing?.status === 'rejected' && listing.review_note"
            class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm dark:border-red-800 dark:bg-red-950"
        >
            <p class="font-medium">
                {{ t('The tourism office asked for changes:') }}
            </p>
            <p class="mt-1">{{ listing.review_note }}</p>
        </div>

        <div
            v-if="canSubmit"
            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-accent/40 p-4"
        >
            <p class="text-sm">
                {{
                    t(
                        'Ready? Add photos and rates first, then send the listing to the tourism office.',
                    )
                }}
            </p>
            <Button @click="submitForApproval"
                ><Send /> {{ t('Submit for approval') }}</Button
            >
        </div>
        <p
            v-else-if="listing?.status === 'pending'"
            class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-700 dark:bg-amber-950"
        >
            {{ t('The tourism office is reviewing this listing.') }}
        </p>

        <ListingForm
            :action="action"
            :listing="listing"
            :categories="categories"
            :barangays="barangays"
            :center="center"
            :can-feature="false"
            :submit-label="listing ? t('Save changes') : t('Save draft')"
        >
            <template
                v-if="listing && ['draft', 'rejected'].includes(listing.status)"
                #actions
            >
                <Button
                    type="button"
                    variant="ghost"
                    class="text-destructive"
                    @click="destroy"
                    >{{ t('Delete draft') }}</Button
                >
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
        </template>
    </div>
</template>
