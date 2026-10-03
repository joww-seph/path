<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Clock, MapPin } from '@lucide/vue';
import LeafletMap from '@/components/guide/LeafletMap.vue';
import { useTrans } from '@/composables/useTrans';
import { formatDate, formatTime } from '@/lib/format';
import eventsRoutes from '@/routes/events';
import listingsRoutes from '@/routes/listings';
import type { PaoayEvent } from '@/types';

defineProps<{
    event: PaoayEvent;
}>();

const { t } = useTrans();
</script>

<template>
    <Head :title="event.title">
        <meta name="description" :content="event.description ?? event.title" />
        <meta property="og:title" :content="`${event.title} · PaTH`" />
    </Head>

    <article class="mx-auto max-w-3xl px-4 py-8 md:px-6">
        <Link
            :href="eventsRoutes.index()"
            class="text-sm text-muted-foreground hover:text-foreground"
        >
            ← {{ t('All events') }}
        </Link>
        <h1 class="mt-3 font-display text-3xl md:text-4xl">
            {{ event.title }}
        </h1>

        <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-3">
            <div class="flex items-center gap-2">
                <CalendarDays class="size-4 text-primary" />
                <dt class="sr-only">{{ t('Date') }}</dt>
                <dd>
                    {{ formatDate(event.starts_at) }}
                    <template
                        v-if="
                            event.ends_at &&
                            formatDate(event.ends_at) !==
                                formatDate(event.starts_at)
                        "
                    >
                        – {{ formatDate(event.ends_at) }}
                    </template>
                </dd>
            </div>
            <div class="flex items-center gap-2">
                <Clock class="size-4 text-primary" />
                <dt class="sr-only">{{ t('Time') }}</dt>
                <dd>
                    {{ formatTime(event.starts_at) }}
                    <template v-if="event.ends_at">
                        – {{ formatTime(event.ends_at) }}</template
                    >
                </dd>
            </div>
            <div class="flex items-center gap-2">
                <MapPin class="size-4 text-primary" />
                <dt class="sr-only">{{ t('Venue') }}</dt>
                <dd>
                    <Link
                        v-if="event.venue"
                        :href="listingsRoutes.show(event.venue.slug)"
                        class="hover:underline"
                    >
                        {{ event.venue.name }}
                    </Link>
                    <template v-else>{{ event.venue_name }}</template>
                </dd>
            </div>
        </dl>

        <p
            v-for="(paragraph, index) in (event.description ?? '').split(
                /\n{2,}/,
            )"
            :key="index"
            class="mt-6 leading-relaxed"
        >
            {{ paragraph }}
        </p>

        <div
            v-if="event.venue?.latitude && event.venue?.longitude"
            class="mt-8 h-64 overflow-hidden rounded-xl border"
        >
            <LeafletMap
                :center="{
                    lat: event.venue.latitude,
                    lng: event.venue.longitude,
                }"
                :zoom="15"
                :markers="[
                    {
                        id: event.venue.id,
                        lat: event.venue.latitude,
                        lng: event.venue.longitude,
                        title: event.venue.name,
                    },
                ]"
            />
        </div>
    </article>
</template>
