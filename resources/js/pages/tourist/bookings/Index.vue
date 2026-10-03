<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, Ticket } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import BookingStatusBadge from '@/components/bookings/BookingStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { formatClock, formatDate, formatPeso } from '@/lib/format';
import { explore } from '@/routes';
import tourist from '@/routes/tourist';
import type { Booking } from '@/types';

const props = defineProps<{
    bookings: Booking[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My bookings', href: tourist.bookings.index() }],
    },
});

const { t } = useTrans();

const active = computed(() =>
    props.bookings.filter((booking) =>
        ['pending', 'confirmed'].includes(booking.status),
    ),
);
const past = computed(() =>
    props.bookings.filter(
        (booking) => !['pending', 'confirmed'].includes(booking.status),
    ),
);
</script>

<template>
    <Head :title="t('My bookings')" />

    <div class="flex max-w-4xl flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('My bookings')"
            :description="
                t('Rooms, rides and tours you requested through PaTH.')
            "
        />

        <div
            v-if="bookings.length === 0"
            class="rounded-xl border border-dashed p-8 text-center"
        >
            <Ticket class="mx-auto mb-2 size-6 text-muted-foreground" />
            <p class="font-medium">{{ t('No bookings yet.') }}</p>
            <Button as-child variant="link"
                ><Link :href="explore({ query: { bookable: 1 } })">{{
                    t('Find places you can book')
                }}</Link></Button
            >
        </div>

        <section
            v-for="[title, list] in [
                [t('Upcoming'), active],
                [t('History'), past],
            ] as const"
            v-show="list.length"
            :key="title"
            class="space-y-3"
        >
            <h2
                class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
            >
                {{ title }}
            </h2>
            <ul class="divide-y rounded-xl border">
                <li v-for="booking in list" :key="booking.id">
                    <Link
                        :href="tourist.bookings.show(booking.code)"
                        class="flex flex-wrap items-center gap-3 p-4 hover:bg-accent/50"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">
                                {{ booking.listing?.name }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ booking.rate_name }} ·
                                {{ t(':count guests', { count: booking.pax }) }}
                            </p>
                            <p
                                class="flex items-center gap-1 text-sm text-muted-foreground"
                            >
                                <CalendarDays class="size-3.5" />
                                {{ formatDate(booking.date) }}
                                <template v-if="booking.time">
                                    · {{ formatClock(booking.time) }}</template
                                >
                                ·
                                <span class="font-mono">{{
                                    booking.code
                                }}</span>
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <BookingStatusBadge
                                :status="booking.status"
                                :label="booking.status_label"
                            />
                            <span class="font-semibold">{{
                                formatPeso(booking.total_amount)
                            }}</span>
                        </div>
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
