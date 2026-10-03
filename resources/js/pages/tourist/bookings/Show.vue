<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarDays,
    Clock,
    FileDown,
    MapPin,
    Navigation,
    Phone,
    Users,
    Wallet,
} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import BookingStatusBadge from '@/components/bookings/BookingStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import {
    formatClock,
    formatDate,
    formatDateTime,
    formatPeso,
} from '@/lib/format';
import listingsRoutes from '@/routes/listings';
import tourist from '@/routes/tourist';
import type { Booking } from '@/types';

const props = defineProps<{
    booking: Booking;
    qr: string | null;
    paymentInstructions: string | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My bookings', href: tourist.bookings.index() }],
    },
});

const { t } = useTrans();

function cancel() {
    const reason = prompt(
        t(
            'Cancel this booking? You can add a short reason for the partner (optional).',
        ),
    );

    if (reason !== null) {
        router.post(
            tourist.bookings.cancel.url(props.booking.code),
            { reason: reason || null },
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head :title="t('Booking :code', { code: booking.code })" />

    <div class="mx-auto flex w-full max-w-xl flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="booking.listing?.name ?? ''"
                :description="booking.rate_name"
            />
            <BookingStatusBadge
                :status="booking.status"
                :label="booking.status_label"
            />
        </div>

        <section class="rounded-2xl border bg-card p-5 text-center shadow-sm">
            <p class="text-xs tracking-widest text-muted-foreground uppercase">
                {{ t('Booking code') }}
            </p>
            <p class="mt-1 font-mono text-3xl font-bold tracking-widest">
                {{ booking.code }}
            </p>

            <div
                v-if="qr"
                class="mx-auto mt-4 w-56 rounded-lg bg-white p-3"
                role="img"
                :aria-label="
                    t('QR code for booking :code', { code: booking.code })
                "
                v-html="qr"
            />
            <p v-if="qr" class="mt-2 text-sm text-muted-foreground">
                {{ t('Show this QR code when you arrive.') }}
            </p>

            <p
                v-else-if="booking.status === 'pending'"
                class="mt-4 text-sm text-muted-foreground"
            >
                {{
                    t(
                        'Waiting for the partner to confirm. They have until :time.',
                        { time: formatDateTime(booking.expires_at) },
                    )
                }}
            </p>
            <p
                v-else-if="booking.status === 'completed'"
                class="mt-4 text-sm text-muted-foreground"
            >
                {{
                    t('Checked in :time. Thank you for booking through PaTH!', {
                        time: formatDateTime(booking.checked_in_at),
                    })
                }}
            </p>
            <p v-else-if="booking.partner_note" class="mt-4 text-sm">
                {{ booking.partner_note }}
            </p>
        </section>

        <dl class="grid gap-3 rounded-xl border p-4 text-sm">
            <div class="flex items-center gap-2">
                <CalendarDays class="size-4 text-muted-foreground" />
                <dt class="sr-only">{{ t('Date') }}</dt>
                <dd>
                    {{ formatDate(booking.date)
                    }}<template v-if="booking.nights > 1">
                        ·
                        {{
                            t(':count nights', { count: booking.nights })
                        }}</template
                    >
                </dd>
            </div>
            <div v-if="booking.time" class="flex items-center gap-2">
                <Clock class="size-4 text-muted-foreground" />
                <dt class="sr-only">{{ t('Time') }}</dt>
                <dd>{{ formatClock(booking.time) }}</dd>
            </div>
            <div class="flex items-center gap-2">
                <Users class="size-4 text-muted-foreground" />
                <dt class="sr-only">{{ t('Guests') }}</dt>
                <dd>{{ t(':count guests', { count: booking.pax }) }}</dd>
            </div>
            <div
                v-if="booking.listing?.address"
                class="flex items-center gap-2"
            >
                <MapPin class="size-4 text-muted-foreground" />
                <dt class="sr-only">{{ t('Address') }}</dt>
                <dd>{{ booking.listing.address }}</dd>
            </div>
            <div
                v-if="booking.listing?.contact_phone"
                class="flex items-center gap-2"
            >
                <Phone class="size-4 text-muted-foreground" />
                <dt class="sr-only">{{ t('Phone') }}</dt>
                <dd>
                    <a
                        :href="`tel:${booking.listing.contact_phone}`"
                        class="text-primary"
                        >{{ booking.listing.contact_phone }}</a
                    >
                </dd>
            </div>
            <div class="flex items-center justify-between border-t pt-3">
                <dt class="text-muted-foreground">
                    {{ t('Total to pay the partner') }}
                </dt>
                <dd class="text-lg font-semibold">
                    {{ formatPeso(booking.total_amount) }}
                </dd>
            </div>
        </dl>

        <p
            v-if="paymentInstructions"
            class="flex gap-2 rounded-xl border-l-4 border-gold bg-accent/50 p-4 text-sm"
        >
            <Wallet class="mt-0.5 size-4 shrink-0" />
            <span
                ><span class="font-medium">{{ t('How to pay:') }}</span>
                {{ paymentInstructions }}</span
            >
        </p>

        <p v-if="booking.tourist_note" class="text-sm text-muted-foreground">
            {{ t('Your message: :note', { note: booking.tourist_note }) }}
        </p>

        <div class="flex flex-wrap gap-2">
            <Button v-if="qr" as-child variant="outline">
                <a :href="tourist.bookings.pdf.url(booking.code)"
                    ><FileDown /> {{ t('Download voucher') }}</a
                >
            </Button>
            <Button v-if="booking.listing?.latitude" as-child variant="outline">
                <a
                    :href="`https://www.openstreetmap.org/directions?to=${booking.listing.latitude}%2C${booking.listing.longitude}`"
                    target="_blank"
                    rel="noopener"
                    ><Navigation /> {{ t('Directions') }}</a
                >
            </Button>
            <Button v-if="booking.listing" as-child variant="ghost">
                <Link :href="listingsRoutes.show(booking.listing.slug)">{{
                    t('View listing')
                }}</Link>
            </Button>
            <Button
                v-if="booking.is_cancellable"
                variant="ghost"
                class="text-destructive"
                @click="cancel"
                >{{ t('Cancel booking') }}</Button
            >
        </div>
    </div>
</template>
