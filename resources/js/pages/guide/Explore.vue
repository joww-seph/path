<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { LocateFixed, Search, SlidersHorizontal } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import Pagination from '@/components/Pagination.vue';
import CategoryIcon from '@/components/guide/CategoryIcon.vue';
import ListingCard from '@/components/guide/ListingCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useGeolocation } from '@/composables/useGeolocation';
import { useTrans } from '@/composables/useTrans';
import { explore } from '@/routes';
import type {
    Barangay,
    Category,
    ListingCard as ListingCardType,
    ResourcePage,
} from '@/types';

type Filters = {
    q?: string;
    category?: string;
    barangay?: string;
    max_price?: string | number;
    min_rating?: string | number;
    accessible?: string | boolean;
    bookable?: string | boolean;
    open_on?: string;
    sort?: string;
    lat?: string | number;
    lng?: string | number;
};

const props = defineProps<{
    listings: ResourcePage<ListingCardType>;
    filters: Filters;
    categories: Category[];
    barangays: Barangay[];
}>();

const { t } = useTrans();
const { locate, locating, error: locationError } = useGeolocation();
const showFilters = ref(false);

const form = reactive({
    q: props.filters.q ?? '',
    category: props.filters.category ?? '',
    barangay: props.filters.barangay ?? '',
    max_price: props.filters.max_price ?? '',
    min_rating: props.filters.min_rating ?? '',
    accessible: ['1', 'true', true].includes(props.filters.accessible ?? ''),
    bookable: ['1', 'true', true].includes(props.filters.bookable ?? ''),
    open_on: props.filters.open_on ?? '',
    sort: props.filters.sort ?? 'featured',
    lat: props.filters.lat ?? '',
    lng: props.filters.lng ?? '',
});

const activeFilterCount = computed(
    () =>
        [
            form.barangay,
            form.max_price,
            form.min_rating,
            form.accessible,
            form.bookable,
            form.open_on,
        ].filter(Boolean).length,
);

function apply() {
    const query: Record<string, string | number> = {};

    for (const [key, value] of Object.entries(form)) {
        if (value === '' || value === false || value === null) {
            continue;
        }

        query[key] = value === true ? 1 : value;
    }

    if (form.sort !== 'distance') {
        delete query.lat;
        delete query.lng;
    }

    router.get(explore.url(), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function chooseCategory(slug: string) {
    form.category = form.category === slug ? '' : slug;
    apply();
}

async function nearMe() {
    const position = await locate();

    if (position) {
        form.sort = 'distance';
        form.lat = position.lat;
        form.lng = position.lng;
        apply();
    }
}

function changeSort() {
    if (form.sort === 'distance' && !form.lat) {
        void nearMe();

        return;
    }

    apply();
}

function reset() {
    router.get(explore.url());
}
</script>

<template>
    <Head :title="t('Explore Paoay')">
        <meta
            name="description"
            content="Attractions, food places, lodging, tours and services in Paoay, Ilocos Norte, with hours, fees and map locations."
        />
        <meta property="og:title" content="Explore Paoay · PaTH" />
    </Head>

    <section class="border-b bg-muted/40">
        <div class="mx-auto max-w-7xl px-4 py-8 md:px-6 md:py-10">
            <h1 class="font-display text-3xl md:text-4xl">
                {{ t('Explore Paoay') }}
            </h1>
            <p class="mt-2 max-w-2xl text-muted-foreground">
                {{
                    t(
                        'Heritage sites, the lake and dunes, Ilocano food, places to stay and the people who can take you there.',
                    )
                }}
            </p>

            <form
                class="mt-6 flex flex-wrap gap-2"
                role="search"
                @submit.prevent="apply"
            >
                <div class="relative min-w-60 flex-1">
                    <Search
                        class="absolute top-2.5 left-3 size-4 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        v-model="form.q"
                        type="search"
                        class="bg-background pl-9"
                        :placeholder="t('Search places, food, activities…')"
                        :aria-label="t('Search')"
                    />
                </div>
                <Button type="submit">{{ t('Search') }}</Button>
                <Button
                    type="button"
                    variant="outline"
                    class="bg-background"
                    :disabled="locating"
                    @click="nearMe"
                >
                    <LocateFixed />
                    {{ locating ? t('Locating…') : t('Near me') }}
                </Button>
            </form>
            <p v-if="locationError" class="mt-2 text-sm text-destructive">
                {{ t(locationError) }}
            </p>

            <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                <button
                    v-for="category in categories"
                    :key="category.slug"
                    type="button"
                    class="flex shrink-0 items-center gap-1.5 rounded-full border bg-background px-3 py-1.5 text-sm hover:border-primary"
                    :class="{
                        'border-primary bg-primary text-primary-foreground hover:bg-primary':
                            form.category === category.slug,
                    }"
                    :aria-pressed="form.category === category.slug"
                    @click="chooseCategory(category.slug)"
                >
                    <CategoryIcon :icon="category.icon" class="size-4" />
                    {{ t(category.name) }}
                </button>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-6 md:px-6">
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <p class="text-sm text-muted-foreground">
                {{ t(':count places', { count: listings.meta.total }) }}
            </p>
            <div class="ml-auto flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :aria-expanded="showFilters"
                    @click="showFilters = !showFilters"
                >
                    <SlidersHorizontal />
                    {{ t('Filters') }}
                    <span
                        v-if="activeFilterCount"
                        class="rounded-full bg-primary px-1.5 text-xs text-primary-foreground"
                        >{{ activeFilterCount }}</span
                    >
                </Button>
                <NativeSelect
                    v-model="form.sort"
                    class="h-8 w-auto"
                    :aria-label="t('Sort by')"
                    @change="changeSort"
                >
                    <option value="featured">{{ t('Recommended') }}</option>
                    <option value="rating">{{ t('Top rated') }}</option>
                    <option value="price">{{ t('Lowest price') }}</option>
                    <option value="distance">{{ t('Nearest to me') }}</option>
                    <option value="name">{{ t('Name (A–Z)') }}</option>
                </NativeSelect>
            </div>
        </div>

        <form
            v-if="showFilters"
            class="mb-6 grid gap-4 rounded-xl border p-4 sm:grid-cols-2 lg:grid-cols-4"
            @submit.prevent="apply"
        >
            <div class="grid gap-2">
                <Label for="barangay">{{ t('Barangay') }}</Label>
                <NativeSelect id="barangay" v-model="form.barangay">
                    <option value="">{{ t('Anywhere in Paoay') }}</option>
                    <option
                        v-for="barangay in barangays"
                        :key="barangay.slug"
                        :value="barangay.slug"
                    >
                        {{ barangay.name }}
                    </option>
                </NativeSelect>
            </div>
            <div class="grid gap-2">
                <Label for="max_price">{{ t('Budget per item (₱)') }}</Label>
                <Input
                    id="max_price"
                    v-model="form.max_price"
                    type="number"
                    min="0"
                    step="50"
                    :placeholder="t('Any price')"
                />
            </div>
            <div class="grid gap-2">
                <Label for="min_rating">{{ t('Rating') }}</Label>
                <NativeSelect id="min_rating" v-model="form.min_rating">
                    <option value="">{{ t('Any rating') }}</option>
                    <option value="4">{{ t('4 stars and up') }}</option>
                    <option value="3">{{ t('3 stars and up') }}</option>
                </NativeSelect>
            </div>
            <div class="grid gap-2">
                <Label for="open_on">{{ t('Open on') }}</Label>
                <Input id="open_on" v-model="form.open_on" type="date" />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input
                    v-model="form.accessible"
                    type="checkbox"
                    class="accent-primary"
                />
                {{ t('Wheelchair accessible') }}
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input
                    v-model="form.bookable"
                    type="checkbox"
                    class="accent-primary"
                />
                {{ t('Can be booked on PaTH') }}
            </label>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-2 lg:justify-end">
                <Button type="button" variant="ghost" @click="reset">{{
                    t('Clear all')
                }}</Button>
                <Button type="submit">{{ t('Apply filters') }}</Button>
            </div>
        </form>

        <div
            v-if="listings.data.length === 0"
            class="rounded-xl border border-dashed p-10 text-center"
        >
            <p class="font-medium">{{ t('No places match your search.') }}</p>
            <Button variant="link" @click="reset">{{
                t('Clear filters')
            }}</Button>
        </div>

        <div
            v-else
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <ListingCard
                v-for="listing in listings.data"
                :key="listing.id"
                :listing="listing"
            />
        </div>

        <div class="mt-6">
            <Pagination :paginator="listings" />
        </div>
    </div>
</template>
