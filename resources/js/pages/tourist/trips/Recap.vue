<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { MapPin, Route, Star, Ticket, Wallet } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import StarRating from '@/components/StarRating.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { formatDate, formatPeso } from '@/lib/format';
import listingsRoutes from '@/routes/listings';
import tourist from '@/routes/tourist';
import type { Trip } from '@/types';

type VisitedStop = {
    id: number;
    title: string;
    day_number: number;
    listing: { id: number; name: string; slug: string } | null;
    photo: string | null;
    color: string | null;
    my_rating: number | null;
};

const props = defineProps<{
    trip: Trip;
    stats: {
        stops_planned: number;
        stops_visited: number;
        distance_km: number;
        bookings_completed: number;
    };
    visited: VisitedStop[];
    spending: {
        spent: number;
        budget: number | null;
        by_category: { category: string; label: string; total: number }[];
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My trips', href: tourist.trips.index() }],
    },
});

const { t } = useTrans();

const toReview = computed(() =>
    props.visited.filter((stop) => stop.listing && stop.my_rating === null),
);

const categories = computed(() =>
    props.spending.by_category
        .filter((row) => row.total > 0)
        .sort((a, b) => b.total - a.total),
);

const largestCategory = computed(() =>
    Math.max(1, ...categories.value.map((row) => row.total)),
);
</script>

<template>
    <Head :title="t('Recap: :trip', { trip: trip.title })" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="t('Your Paoay trip recap')"
                :description="
                    t(':trip · :start – :end', {
                        trip: trip.title,
                        start: formatDate(trip.start_date),
                        end: formatDate(trip.end_date),
                    })
                "
            />
            <Button as-child variant="outline" size="sm">
                <Link :href="tourist.trips.show(trip.id)">{{
                    t('Back to planner')
                }}</Link>
            </Button>
        </div>

        <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl border p-4">
                <dt
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <MapPin class="size-4" /> {{ t('Places visited') }}
                </dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">
                    {{ stats.stops_visited }}
                    <span class="text-sm font-normal text-muted-foreground">{{
                        t('of :count planned', { count: stats.stops_planned })
                    }}</span>
                </dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <Route class="size-4" /> {{ t('Distance travelled') }}
                </dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">
                    {{ stats.distance_km }} km
                </dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <Ticket class="size-4" /> {{ t('Bookings completed') }}
                </dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">
                    {{ stats.bookings_completed }}
                </dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <Wallet class="size-4" /> {{ t('Total spent') }}
                </dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">
                    {{ formatPeso(spending.spent) }}
                </dd>
                <p v-if="spending.budget" class="text-xs text-muted-foreground">
                    {{
                        t('Budget :amount', {
                            amount: formatPeso(spending.budget),
                        })
                    }}
                </p>
            </div>
        </dl>

        <section
            v-if="toReview.length"
            class="rounded-xl border border-gold/50 bg-gold/10 p-4"
        >
            <h2 class="flex items-center gap-2 font-semibold">
                <Star class="size-5 fill-gold text-gold" />
                {{ t('Help the next visitor: rate the places you went') }}
            </h2>
            <ul class="mt-3 flex flex-wrap gap-2">
                <li v-for="stop in toReview" :key="stop.id">
                    <Button as-child size="sm" variant="outline">
                        <Link
                            :href="`${listingsRoutes.show.url(stop.listing!.slug)}#reviews`"
                            >{{ t('Rate :name', { name: stop.title }) }}</Link
                        >
                    </Button>
                </li>
            </ul>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ t('Where you went') }}
                </h2>
                <p
                    v-if="visited.length === 0"
                    class="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    {{
                        t(
                            'Tick off stops in your planner as you visit them and they will show up here.',
                        )
                    }}
                </p>
                <ul v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <li
                        v-for="stop in visited"
                        :key="stop.id"
                        class="overflow-hidden rounded-xl border bg-card"
                    >
                        <div
                            class="aspect-[4/3] bg-muted bg-cover bg-center"
                            :style="
                                stop.photo
                                    ? { backgroundImage: `url(${stop.photo})` }
                                    : {
                                          backgroundColor:
                                              stop.color ?? undefined,
                                      }
                            "
                            role="img"
                            :aria-label="stop.title"
                        />
                        <div class="space-y-1 p-3">
                            <p class="text-xs text-muted-foreground">
                                {{ t('Day :day', { day: stop.day_number }) }}
                            </p>
                            <p class="font-medium">
                                <Link
                                    v-if="stop.listing"
                                    :href="
                                        listingsRoutes.show(stop.listing.slug)
                                    "
                                    class="hover:text-primary"
                                    >{{ stop.title }}</Link
                                >
                                <span v-else>{{ stop.title }}</span>
                            </p>
                            <StarRating
                                v-if="stop.my_rating"
                                :rating="stop.my_rating"
                            />
                        </div>
                    </li>
                </ul>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold">
                    {{ t('Spending by category') }}
                </h2>
                <p
                    v-if="categories.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No expenses recorded.') }}
                </p>
                <ul v-else class="space-y-2 rounded-xl border p-4">
                    <li v-for="row in categories" :key="row.category">
                        <div class="flex justify-between text-sm">
                            <span>{{ t(row.label) }}</span>
                            <span class="font-medium tabular-nums">{{
                                formatPeso(row.total)
                            }}</span>
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full bg-primary"
                                :style="{
                                    width: `${(row.total / largestCategory) * 100}%`,
                                }"
                            />
                        </div>
                    </li>
                </ul>
                <Button as-child variant="outline" size="sm">
                    <Link :href="tourist.trips.budget(trip.id)">{{
                        t('Open budget')
                    }}</Link>
                </Button>
            </section>
        </div>
    </div>
</template>
