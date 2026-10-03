<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { List, LocateFixed } from '@lucide/vue';
import { computed, ref } from 'vue';
import CategoryIcon from '@/components/guide/CategoryIcon.vue';
import LeafletMap from '@/components/guide/LeafletMap.vue';
import type { MapMarker } from '@/components/guide/LeafletMap.vue';
import { Button } from '@/components/ui/button';
import { useGeolocation } from '@/composables/useGeolocation';
import { useTrans } from '@/composables/useTrans';
import { formatPeso } from '@/lib/format';
import listingsRoutes from '@/routes/listings';
import type { Category, LatLng, ListingCard } from '@/types';

const props = defineProps<{
    listings: ListingCard[];
    categories: Category[];
    center: LatLng;
}>();

const { t } = useTrans();
const { locate, locating, position, error } = useGeolocation();
const hidden = ref<Set<string>>(new Set());
const showList = ref(false);
const mapRef = ref<InstanceType<typeof LeafletMap> | null>(null);

const visible = computed(() =>
    props.listings.filter(
        (listing) => !hidden.value.has(listing.category?.slug ?? ''),
    ),
);

function escapeHtml(text: string) {
    return text.replace(/[&<>"']/g, (char) => `&#${char.charCodeAt(0)};`);
}

const markers = computed<MapMarker[]>(() =>
    visible.value.map((listing) => ({
        id: listing.id,
        lat: listing.latitude!,
        lng: listing.longitude!,
        title: listing.name,
        color: listing.category?.color,
        popupHtml: `<strong>${escapeHtml(listing.name)}</strong><br>
            <span style="color:#64748b">${escapeHtml(t(listing.category?.name ?? ''))}${
                listing.starting_price !== null && !listing.is_free
                    ? ' · ' +
                      t('From :price', {
                          price: formatPeso(listing.starting_price),
                      })
                    : ''
            }</span><br>
            <a href="${listingsRoutes.show.url(listing.slug)}">${escapeHtml(t('View details'))} →</a>`,
    })),
);

function toggle(slug: string) {
    const next = new Set(hidden.value);

    if (next.has(slug)) {
        next.delete(slug);
    } else {
        next.add(slug);
    }

    hidden.value = next;
}

function focus(listing: ListingCard) {
    showList.value = false;
    mapRef.value?.focus(listing.latitude!, listing.longitude!);
}
</script>

<template>
    <Head :title="t('Map of Paoay')">
        <meta
            name="description"
            content="Interactive map of attractions, food places, lodging and services in Paoay, Ilocos Norte."
        />
    </Head>

    <div class="relative flex h-[calc(100dvh-4rem)] flex-col">
        <div
            class="flex flex-wrap items-center gap-2 border-b bg-background px-4 py-2"
        >
            <button
                v-for="category in categories"
                :key="category.slug"
                type="button"
                class="flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm"
                :class="{ 'opacity-40': hidden.has(category.slug) }"
                :aria-pressed="!hidden.has(category.slug)"
                @click="toggle(category.slug)"
            >
                <span
                    class="size-2.5 rounded-full"
                    :style="{ backgroundColor: category.color }"
                />
                {{ t(category.name) }}
            </button>
            <div class="ml-auto flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="locating"
                    @click="locate"
                >
                    <LocateFixed /> {{ t('Near me') }}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    class="lg:hidden"
                    @click="showList = !showList"
                >
                    <List /> {{ t('List') }}
                </Button>
            </div>
        </div>
        <p
            v-if="error"
            class="bg-destructive/10 px-4 py-1 text-sm text-destructive"
        >
            {{ t(error) }}
        </p>

        <div class="flex min-h-0 flex-1">
            <aside
                class="w-80 shrink-0 overflow-y-auto border-r bg-background max-lg:absolute max-lg:inset-x-0 max-lg:top-[3.25rem] max-lg:bottom-0 max-lg:z-10 max-lg:w-full"
                :class="{ 'max-lg:hidden': !showList }"
            >
                <p class="px-4 pt-3 text-sm text-muted-foreground">
                    {{
                        t(':count places on the map', { count: visible.length })
                    }}
                </p>
                <ul class="divide-y">
                    <li
                        v-for="listing in visible"
                        :key="listing.id"
                        class="flex items-start gap-3 px-4 py-3"
                    >
                        <span
                            class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full text-white"
                            :style="{
                                backgroundColor: listing.category?.color,
                            }"
                        >
                            <CategoryIcon
                                :icon="listing.category?.icon"
                                class="size-4"
                            />
                        </span>
                        <div class="min-w-0 flex-1">
                            <button
                                type="button"
                                class="text-left font-medium hover:text-primary"
                                @click="focus(listing)"
                            >
                                {{ listing.name }}
                            </button>
                            <p class="truncate text-sm text-muted-foreground">
                                {{ listing.summary }}
                            </p>
                            <Link
                                :href="listingsRoutes.show(listing.slug)"
                                class="text-sm text-primary hover:underline"
                            >
                                {{ t('View details') }}
                            </Link>
                        </div>
                    </li>
                </ul>
            </aside>

            <div class="min-w-0 flex-1">
                <LeafletMap
                    ref="mapRef"
                    :center="center"
                    :zoom="13"
                    :markers="markers"
                    :fit-markers="false"
                    :user-location="position"
                    cluster
                />
            </div>
        </div>
    </div>
</template>
