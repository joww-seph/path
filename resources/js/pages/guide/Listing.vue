<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Accessibility,
    CalendarDays,
    Clock,
    ExternalLink,
    Globe,
    Mail,
    MapPin,
    Navigation,
    Phone,
    Star,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import AdvisoryBanner from '@/components/AdvisoryBanner.vue';
import CategoryIcon from '@/components/guide/CategoryIcon.vue';
import ReviewSection from '@/components/reviews/ReviewSection.vue';
import BookingRequestCard from '@/components/bookings/BookingRequestCard.vue';
import AddToTripCard from '@/components/trips/AddToTripCard.vue';
import LeafletMap from '@/components/guide/LeafletMap.vue';
import ListingCard from '@/components/guide/ListingCard.vue';
import OpeningHoursTable from '@/components/guide/OpeningHoursTable.vue';
import { useTrans } from '@/composables/useTrans';
import { formatDate, formatPeso } from '@/lib/format';
import { explore } from '@/routes';
import events from '@/routes/events';
import type {
    Advisory,
    ListingCard as ListingCardType,
    ListingDetail,
    Review,
} from '@/types';

const props = defineProps<{
    listing: ListingDetail;
    events: {
        id: number;
        title: string;
        slug: string;
        starts_at: string;
        ends_at: string | null;
    }[];
    nearby: ListingCardType[];
    paymentInstructions: string | null;
    myTrips:
        | { id: number; title: string; start_date: string; day_count: number }[]
        | null;
    availability: Record<string, number | null> | null;
    advisories: Advisory[];
    reviews: Review[];
    canReview: boolean;
    myReview: {
        id: number;
        rating: number;
        comment: string | null;
        status: string;
    } | null;
}>();

const { t } = useTrans();
const activePhoto = ref(0);

const photos = computed(() => props.listing.photos ?? []);
const color = computed(() => props.listing.category?.color ?? '#222d60');

const directionsUrl = computed(() =>
    props.listing.latitude === null
        ? null
        : `https://www.openstreetmap.org/directions?to=${props.listing.latitude}%2C${props.listing.longitude}`,
);

const visitLength = computed(() => {
    const minutes = props.listing.visit_minutes;

    return minutes < 60
        ? t(':minutes minutes', { minutes })
        : t('About :hours h', { hours: Math.round((minutes / 60) * 10) / 10 });
});

const paragraphs = (text: string | null) =>
    (text ?? '').split(/\n{2,}/).filter((paragraph) => paragraph.trim() !== '');
</script>

<template>
    <Head :title="listing.name">
        <meta
            name="description"
            :content="
                listing.summary ?? `${listing.name} in Paoay, Ilocos Norte`
            "
        />
        <meta property="og:title" :content="`${listing.name} · PaTH`" />
        <meta
            property="og:description"
            :content="
                listing.summary ?? `${listing.name} in Paoay, Ilocos Norte`
            "
        />
        <meta v-if="photos[0]" property="og:image" :content="photos[0].url" />
    </Head>

    <article class="mx-auto max-w-7xl px-4 py-6 md:px-6">
        <nav
            class="mb-4 text-sm text-muted-foreground"
            :aria-label="t('Breadcrumb')"
        >
            <Link :href="explore()" class="hover:text-foreground">{{
                t('Explore')
            }}</Link>
            <span v-if="listing.category" class="mx-1">/</span>
            <Link
                v-if="listing.category"
                :href="explore({ query: { category: listing.category.slug } })"
                class="hover:text-foreground"
                >{{ t(listing.category.name) }}</Link
            >
        </nav>

        <div
            v-if="listing.status !== 'published'"
            class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100"
        >
            {{ t('Preview: this listing is not visible to the public yet.') }}
        </div>

        <AdvisoryBanner
            v-if="advisories.length"
            :advisories="advisories"
            class="mb-4"
        />

        <header class="mb-6">
            <p
                v-if="listing.category"
                class="flex items-center gap-1.5 text-sm font-medium tracking-wide uppercase"
                :style="{ color }"
            >
                <CategoryIcon :icon="listing.category.icon" class="size-4" />
                {{ t(listing.category.name) }}
            </p>
            <h1 class="mt-1 font-display text-3xl md:text-4xl">
                {{ listing.name }}
            </h1>
            <div
                class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground"
            >
                <span
                    v-if="listing.reviews_count > 0"
                    class="flex items-center gap-1"
                >
                    <Star class="size-4 fill-gold text-gold" />
                    {{ listing.rating_average.toFixed(1) }}
                    ({{
                        t(':count reviews', { count: listing.reviews_count })
                    }})
                </span>
                <span
                    v-if="listing.barangay || listing.address"
                    class="flex items-center gap-1"
                >
                    <MapPin class="size-4" />
                    {{ listing.address ?? `Brgy. ${listing.barangay}, Paoay` }}
                </span>
                <span class="flex items-center gap-1">
                    <Clock class="size-4" /> {{ visitLength }}
                </span>
                <span
                    v-if="listing.is_accessible"
                    class="flex items-center gap-1"
                >
                    <Accessibility class="size-4" />
                    {{ t('Wheelchair accessible') }}
                </span>
            </div>
        </header>

        <div class="grid gap-8 lg:grid-cols-[1fr_22rem]">
            <div class="min-w-0 space-y-8">
                <section v-if="photos.length" :aria-label="t('Photos')">
                    <figure class="overflow-hidden rounded-xl bg-muted">
                        <img
                            :src="photos[activePhoto].url"
                            :alt="photos[activePhoto].caption ?? listing.name"
                            class="aspect-[16/9] w-full object-cover"
                        />
                        <figcaption
                            v-if="photos[activePhoto].caption"
                            class="px-3 py-2 text-sm text-muted-foreground"
                        >
                            {{ photos[activePhoto].caption }}
                        </figcaption>
                    </figure>
                    <div
                        v-if="photos.length > 1"
                        class="mt-2 flex gap-2 overflow-x-auto"
                    >
                        <button
                            v-for="(photo, index) in photos"
                            :key="photo.id"
                            type="button"
                            class="shrink-0 overflow-hidden rounded-md border-2"
                            :class="
                                index === activePhoto
                                    ? 'border-primary'
                                    : 'border-transparent'
                            "
                            :aria-label="
                                t('Show photo :number', { number: index + 1 })
                            "
                            @click="activePhoto = index"
                        >
                            <img
                                loading="lazy"
                                :src="photo.thumbnail_url"
                                alt=""
                                class="h-16 w-24 object-cover"
                            />
                        </button>
                    </div>
                </section>
                <div
                    v-else
                    class="flex aspect-[16/7] items-center justify-center rounded-xl"
                    :style="{ backgroundColor: `${color}14`, color }"
                >
                    <CategoryIcon
                        :icon="listing.category?.icon"
                        class="size-16 opacity-60"
                    />
                </div>

                <section>
                    <p v-if="listing.summary" class="text-lg">
                        {{ listing.summary }}
                    </p>
                    <p
                        v-for="(paragraph, index) in paragraphs(
                            listing.description,
                        )"
                        :key="index"
                        class="mt-3 leading-relaxed text-muted-foreground"
                    >
                        {{ paragraph }}
                    </p>
                </section>

                <section v-if="listing.rates?.length">
                    <h2 class="mb-3 text-xl font-semibold">{{ t('Rates') }}</h2>
                    <ul class="divide-y rounded-xl border">
                        <li
                            v-for="rate in listing.rates"
                            :key="rate.id"
                            class="flex flex-wrap items-baseline justify-between gap-2 p-4"
                        >
                            <span>
                                <span class="font-medium">{{ rate.name }}</span>
                                <span
                                    v-if="rate.description"
                                    class="block text-sm text-muted-foreground"
                                    >{{ rate.description }}</span
                                >
                                <span
                                    v-if="rate.capacity"
                                    class="block text-sm text-muted-foreground"
                                    >{{
                                        t('Up to :count people', {
                                            count: rate.capacity,
                                        })
                                    }}</span
                                >
                            </span>
                            <span class="font-semibold">
                                {{ formatPeso(rate.price) }}
                                <span
                                    class="text-sm font-normal text-muted-foreground"
                                    >{{ t(rate.unit_label) }}</span
                                >
                            </span>
                        </li>
                    </ul>
                </section>

                <section
                    v-for="story in listing.heritage_stories"
                    :key="story.id"
                    class="rounded-xl border-l-4 border-gold bg-accent/40 p-5"
                >
                    <p
                        class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        {{ t('Heritage story') }}
                    </p>
                    <h2 class="mt-1 font-display text-2xl">
                        {{ story.title }}
                    </h2>
                    <p
                        v-for="(paragraph, index) in paragraphs(story.body)"
                        :key="index"
                        class="mt-3 leading-relaxed"
                    >
                        {{ paragraph }}
                    </p>
                    <p
                        v-if="story.source"
                        class="mt-3 text-xs text-muted-foreground"
                    >
                        {{ t('Source: :source', { source: story.source }) }}
                    </p>
                </section>

                <ReviewSection
                    v-if="listing.status === 'published'"
                    :listing-slug="listing.slug"
                    :reviews="reviews"
                    :average="listing.rating_average"
                    :count="listing.reviews_count"
                    :can-review="canReview"
                    :my-review="myReview"
                />
                <section v-if="props.events.length">
                    <h2 class="mb-3 text-xl font-semibold">
                        {{ t('Upcoming events here') }}
                    </h2>
                    <ul class="space-y-2">
                        <li v-for="event in props.events" :key="event.id">
                            <Link
                                :href="events.show(event.slug)"
                                class="flex items-center gap-3 rounded-lg border p-3 hover:border-primary"
                            >
                                <CalendarDays class="size-5 text-primary" />
                                <span>
                                    <span class="block font-medium">{{
                                        event.title
                                    }}</span>
                                    <span
                                        class="text-sm text-muted-foreground"
                                        >{{ formatDate(event.starts_at) }}</span
                                    >
                                </span>
                            </Link>
                        </li>
                    </ul>
                </section>
            </div>

            <aside class="space-y-6">
                <BookingRequestCard
                    v-if="
                        listing.status === 'published' &&
                        listing.is_bookable &&
                        availability &&
                        listing.rates?.length
                    "
                    :listing-id="listing.id"
                    :rates="listing.rates"
                    :availability="availability"
                    :trips="myTrips"
                />
                <AddToTripCard
                    v-if="listing.status === 'published'"
                    :listing-id="listing.id"
                    :trips="myTrips"
                />
                <div class="rounded-xl border p-4">
                    <h2 class="mb-2 font-semibold">{{ t('Prices') }}</h2>
                    <p
                        v-if="listing.is_free"
                        class="text-green-700 dark:text-green-400"
                    >
                        {{ t('Free entry') }}
                    </p>
                    <p v-else-if="listing.entrance_fee !== null">
                        {{
                            t('Entrance fee: :price', {
                                price: formatPeso(listing.entrance_fee),
                            })
                        }}
                    </p>
                    <p v-if="listing.price_min !== null">
                        {{ formatPeso(listing.price_min) }}
                        <template
                            v-if="
                                listing.price_max !== null &&
                                listing.price_max !== listing.price_min
                            "
                        >
                            – {{ formatPeso(listing.price_max) }}
                        </template>
                    </p>
                    <p
                        v-if="
                            listing.entrance_fee === null &&
                            listing.price_min === null &&
                            !listing.rates?.length
                        "
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('Ask the place for current prices.') }}
                    </p>
                    <p
                        v-if="paymentInstructions"
                        class="mt-3 flex gap-2 text-sm text-muted-foreground"
                    >
                        <Wallet class="mt-0.5 size-4 shrink-0" />
                        {{ paymentInstructions }}
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <h2 class="mb-2 font-semibold">{{ t('Opening hours') }}</h2>
                    <OpeningHoursTable :hours="listing.opening_hours" />
                </div>

                <div
                    v-if="
                        listing.contact_phone ||
                        listing.contact_email ||
                        listing.website ||
                        listing.facebook_url
                    "
                    class="space-y-2 rounded-xl border p-4 text-sm"
                >
                    <h2 class="mb-1 text-base font-semibold">
                        {{ t('Contact') }}
                    </h2>
                    <a
                        v-if="listing.contact_phone"
                        :href="`tel:${listing.contact_phone}`"
                        class="flex items-center gap-2 hover:text-primary"
                    >
                        <Phone class="size-4" /> {{ listing.contact_phone }}
                    </a>
                    <a
                        v-if="listing.contact_email"
                        :href="`mailto:${listing.contact_email}`"
                        class="flex items-center gap-2 hover:text-primary"
                    >
                        <Mail class="size-4" /> {{ listing.contact_email }}
                    </a>
                    <a
                        v-if="listing.website"
                        :href="listing.website"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-2 hover:text-primary"
                    >
                        <Globe class="size-4" /> {{ t('Website') }}
                    </a>
                    <a
                        v-if="listing.facebook_url"
                        :href="listing.facebook_url"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-2 hover:text-primary"
                    >
                        <ExternalLink class="size-4" /> {{ t('Facebook page') }}
                    </a>
                </div>

                <div
                    v-if="
                        listing.latitude !== null && listing.longitude !== null
                    "
                    class="overflow-hidden rounded-xl border"
                >
                    <div class="h-56">
                        <LeafletMap
                            :center="{
                                lat: listing.latitude,
                                lng: listing.longitude,
                            }"
                            :zoom="15"
                            :markers="[
                                {
                                    id: listing.id,
                                    lat: listing.latitude,
                                    lng: listing.longitude,
                                    title: listing.name,
                                    color,
                                },
                            ]"
                        />
                    </div>
                    <a
                        v-if="directionsUrl"
                        :href="directionsUrl"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center justify-center gap-2 border-t p-3 text-sm font-medium hover:bg-accent"
                    >
                        <Navigation class="size-4" /> {{ t('Get directions') }}
                    </a>
                </div>
            </aside>
        </div>

        <section v-if="nearby.length" class="mt-12">
            <h2 class="mb-4 text-xl font-semibold">{{ t('Nearby') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <ListingCard
                    v-for="other in nearby"
                    :key="other.id"
                    :listing="other"
                />
            </div>
        </section>
    </article>
</template>
