<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { reactive } from 'vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import ListingStatusBadge from '@/components/listings/ListingStatusBadge.vue';
import ReviewListingButtons from '@/components/listings/ReviewListingButtons.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { useTrans } from '@/composables/useTrans';
import listingsRoutes from '@/routes/listings';
import office from '@/routes/office';
import type { ListingCard, ResourcePage } from '@/types';

const props = defineProps<{
    listings: ResourcePage<ListingCard>;
    filters: { q?: string; status?: string; category?: string | number };
    categories: { id: number; name: string }[];
    pendingCount: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Listings', href: office.listings.index() }],
    },
});

const { t } = useTrans();

const form = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    category: props.filters.category ?? '',
});

function apply() {
    router.get(
        office.listings.index.url(),
        Object.fromEntries(
            Object.entries(form).filter(([, value]) => value !== ''),
        ),
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <Head :title="t('Listings')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="t('Listings')"
                :description="
                    t(
                        'Attractions, partner businesses and services shown in PaTH.',
                    )
                "
            />
            <Button as-child>
                <Link :href="office.listings.create()"
                    ><Plus /> {{ t('Add a listing') }}</Link
                >
            </Button>
        </div>

        <button
            v-if="pendingCount > 0 && form.status !== 'pending'"
            type="button"
            class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-left text-sm text-amber-900 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100"
            @click="
                form.status = 'pending';
                apply();
            "
        >
            {{
                t(':count partner listings are waiting for your approval.', {
                    count: pendingCount,
                })
            }}
        </button>

        <form class="flex flex-wrap gap-2" @submit.prevent="apply">
            <Input
                v-model="form.q"
                type="search"
                class="max-w-xs"
                :placeholder="t('Search listings')"
                :aria-label="t('Search listings')"
            />
            <NativeSelect
                v-model="form.status"
                class="w-auto"
                :aria-label="t('Status')"
                @change="apply"
            >
                <option value="">{{ t('Any status') }}</option>
                <option value="pending">{{ t('Awaiting approval') }}</option>
                <option value="published">{{ t('Published') }}</option>
                <option value="draft">{{ t('Draft') }}</option>
                <option value="rejected">{{ t('Changes requested') }}</option>
                <option value="archived">{{ t('Archived') }}</option>
            </NativeSelect>
            <NativeSelect
                v-model="form.category"
                class="w-auto"
                :aria-label="t('Category')"
                @change="apply"
            >
                <option value="">{{ t('All categories') }}</option>
                <option
                    v-for="category in categories"
                    :key="category.id"
                    :value="category.id"
                >
                    {{ t(category.name) }}
                </option>
            </NativeSelect>
            <Button type="submit" variant="outline">{{ t('Search') }}</Button>
        </form>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="p-3 font-medium">{{ t('Listing') }}</th>
                        <th class="p-3 font-medium">{{ t('Managed by') }}</th>
                        <th class="p-3 font-medium">{{ t('Status') }}</th>
                        <th class="p-3">
                            <span class="sr-only">{{ t('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="listings.data.length === 0">
                        <td
                            colspan="4"
                            class="p-6 text-center text-muted-foreground"
                        >
                            {{ t('No listings found.') }}
                        </td>
                    </tr>
                    <tr v-for="listing in listings.data" :key="listing.id">
                        <td class="p-3">
                            <Link
                                :href="office.listings.edit(listing.slug)"
                                class="font-medium hover:text-primary"
                            >
                                {{ listing.name }}
                            </Link>
                            <p class="text-muted-foreground">
                                {{ t(listing.category?.name ?? '') }}
                            </p>
                        </td>
                        <td class="p-3 text-muted-foreground">
                            {{ listing.business_name ?? t('Tourism office') }}
                        </td>
                        <td class="p-3">
                            <ListingStatusBadge :status="listing.status" />
                        </td>
                        <td class="p-3">
                            <div class="flex justify-end gap-2">
                                <ReviewListingButtons
                                    v-if="listing.status === 'pending'"
                                    :listing-slug="listing.slug"
                                    :listing-name="listing.name"
                                />
                                <Button as-child variant="ghost" size="sm">
                                    <Link
                                        :href="
                                            listingsRoutes.show(listing.slug)
                                        "
                                        >{{ t('View') }}</Link
                                    >
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :paginator="listings" />
    </div>
</template>
