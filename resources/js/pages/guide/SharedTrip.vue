<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Clock, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import LeafletMap from '@/components/guide/LeafletMap.vue';
import type { MapMarker } from '@/components/guide/LeafletMap.vue';
import TravelLeg from '@/components/trips/TravelLeg.vue';
import { useTrans } from '@/composables/useTrans';
import { formatClock, formatDate } from '@/lib/format';
import { register } from '@/routes';
import listingsRoutes from '@/routes/listings';
import type { ItineraryDay, PlannerWarning, Trip } from '@/types';

const props = defineProps<{
    trip: Trip;
    days: ItineraryDay[];
    warnings: PlannerWarning[];
    estimatedCost: number;
}>();

const { t } = useTrans();
const selectedDay = ref(1);

const markers = computed<MapMarker[]>(() => {
    const day = props.days.find((item) => item.number === selectedDay.value);

    return (day?.items ?? [])
        .map((item, index) => ({ item, index }))
        .filter(({ item }) => item.latitude !== null && item.longitude !== null)
        .map(({ item, index }) => ({
            id: item.id,
            lat: item.latitude!,
            lng: item.longitude!,
            title: item.title,
            label: index + 1,
            color: item.listing?.category?.color ?? '#475569',
        }));
});
</script>

<template>
    <Head :title="trip.title">
        <meta name="robots" content="noindex" />
    </Head>

    <div class="mx-auto max-w-6xl px-4 py-8 md:px-6">
        <p class="text-sm text-muted-foreground">{{ t('Shared itinerary') }}</p>
        <h1 class="font-display text-3xl md:text-4xl">{{ trip.title }}</h1>
        <p
            class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground"
        >
            <span class="flex items-center gap-1"
                ><CalendarDays class="size-4" />
                {{ formatDate(trip.start_date) }} –
                {{ formatDate(trip.end_date) }}</span
            >
            <span class="flex items-center gap-1"
                ><Users class="size-4" />
                {{ t(':count travellers', { count: trip.pax }) }}</span
            >
            <span v-if="trip.owner">{{
                t('Planned by :name', { name: trip.owner.name })
            }}</span>
        </p>

        <nav
            class="mt-6 flex gap-2 overflow-x-auto pb-1"
            :aria-label="t('Days')"
        >
            <button
                v-for="day in days"
                :key="day.number"
                type="button"
                class="shrink-0 rounded-lg border px-3 py-2 text-sm"
                :class="
                    day.number === selectedDay
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'hover:border-primary'
                "
                @click="selectedDay = day.number"
            >
                {{ t('Day :day', { day: day.number }) }} ·
                {{ formatDate(day.date) }}
            </button>
        </nav>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
            <section>
                <template v-for="day in days" :key="day.number">
                    <div v-if="day.number === selectedDay">
                        <p
                            v-if="day.items.length === 0"
                            class="rounded-lg border border-dashed p-6 text-center text-muted-foreground"
                        >
                            {{ t('Nothing planned for this day yet.') }}
                        </p>
                        <div v-for="(item, index) in day.items" :key="item.id">
                            <TravelLeg
                                v-if="index > 0"
                                :minutes="item.travel_minutes_from_previous"
                                :km="item.distance_km_from_previous"
                                :mode="trip.travel_mode"
                            />
                            <div class="flex gap-3 rounded-lg border p-3">
                                <div class="w-16 shrink-0 text-sm">
                                    <p class="font-semibold">
                                        {{ formatClock(item.start_time) }}
                                    </p>
                                    <p
                                        class="flex items-center gap-1 text-xs text-muted-foreground"
                                    >
                                        <Clock class="size-3" />
                                        {{ item.visit_minutes }}m
                                    </p>
                                </div>
                                <div class="min-w-0">
                                    <Link
                                        v-if="item.listing"
                                        :href="
                                            listingsRoutes.show(
                                                item.listing.slug,
                                            )
                                        "
                                        class="font-medium hover:text-primary"
                                        >{{ item.title }}</Link
                                    >
                                    <p v-else class="font-medium">
                                        {{ item.title }}
                                    </p>
                                    <p
                                        v-if="item.notes"
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ item.notes }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </section>
            <aside class="space-y-4">
                <div class="h-72 overflow-hidden rounded-xl border">
                    <LeafletMap
                        :key="selectedDay"
                        :center="{ lat: 18.0617, lng: 120.5222 }"
                        :markers="markers"
                        route
                    />
                </div>
                <div class="rounded-xl border bg-accent/40 p-4 text-sm">
                    <p class="font-medium">
                        {{ t('Planning your own trip to Paoay?') }}
                    </p>
                    <Link
                        :href="register()"
                        class="text-primary hover:underline"
                        >{{ t('Create a free PaTH account') }}</Link
                    >
                </div>
            </aside>
        </div>
    </div>
</template>
