<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CalendarDays, Mail, Phone, QrCode, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/Pagination.vue';
import BookingStatusBadge from '@/components/bookings/BookingStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import {
    formatClock,
    formatDate,
    formatDateTime,
    formatPeso,
} from '@/lib/format';
import partner from '@/routes/partner';
import type { Booking, BookingStatus, ResourcePage } from '@/types';

defineProps<{
    bookings: ResourcePage<Booking>;
    status: BookingStatus;
    counts: Partial<Record<BookingStatus, number>>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Bookings', href: partner.bookings.index() }],
    },
});

const page = usePage();
const { t } = useTrans();

const tabs: { value: BookingStatus; label: string }[] = [
    { value: 'pending', label: 'Requests' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'completed', label: 'Completed' },
    { value: 'declined', label: 'Declined' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'expired', label: 'Expired' },
    { value: 'no_show', label: 'No-show' },
];

function confirm(booking: Booking) {
    router.post(
        partner.bookings.confirm.url(booking.code),
        {},
        { preserveScroll: true },
    );
}

function decline(booking: Booking) {
    const reason = prompt(
        t('Why can you not take booking :code? The tourist will see this.', {
            code: booking.code,
        }),
    );

    if (reason) {
        router.post(
            partner.bookings.decline.url(booking.code),
            { reason },
            { preserveScroll: true },
        );
    }
}

function cancel(booking: Booking) {
    const reason = prompt(
        t('Why are you cancelling booking :code? The tourist will see this.', {
            code: booking.code,
        }),
    );

    if (reason) {
        router.post(
            partner.bookings.cancel.url(booking.code),
            { reason },
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head :title="t('Bookings')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="t('Bookings')"
                :description="
                    t('Answer requests within 48 hours, or they expire.')
                "
            />
            <Button as-child
                ><Link :href="partner.checkIn.scanner()"
                    ><QrCode /> {{ t('Check in a guest') }}</Link
                ></Button
            >
        </div>

        <nav
            class="flex gap-1 overflow-x-auto border-b"
            :aria-label="t('Booking status')"
        >
            <Link
                v-for="tab in tabs"
                :key="tab.value"
                :href="partner.bookings.index({ query: { status: tab.value } })"
                class="-mb-px shrink-0 border-b-2 px-3 py-2 text-sm font-medium"
                :class="
                    status === tab.value
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground'
                "
            >
                {{ t(tab.label) }}
                <span
                    v-if="counts[tab.value]"
                    class="ml-1 rounded-full bg-muted px-1.5 text-xs"
                    >{{ counts[tab.value] }}</span
                >
            </Link>
        </nav>

        <InputError
            :message="page.props.errors?.booking as string | undefined"
        />

        <p
            v-if="bookings.data.length === 0"
            class="rounded-xl border border-dashed p-8 text-center text-muted-foreground"
        >
            {{ t('No bookings here.') }}
        </p>

        <ul class="grid gap-4 lg:grid-cols-2">
            <li
                v-for="booking in bookings.data"
                :key="booking.id"
                class="flex flex-col gap-3 rounded-xl border p-4"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-mono text-sm text-muted-foreground">
                            {{ booking.code }}
                        </p>
                        <p class="font-semibold">{{ booking.rate_name }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ booking.listing?.name }}
                        </p>
                    </div>
                    <BookingStatusBadge
                        :status="booking.status"
                        :label="booking.status_label"
                    />
                </div>
                <dl class="grid gap-1 text-sm">
                    <div class="flex items-center gap-2">
                        <CalendarDays class="size-4 text-muted-foreground" />
                        <dt class="sr-only">{{ t('Date') }}</dt>
                        <dd>
                            {{ formatDate(booking.date)
                            }}<template v-if="booking.time">
                                · {{ formatClock(booking.time) }}</template
                            ><template v-if="booking.nights > 1">
                                ·
                                {{
                                    t(':count nights', {
                                        count: booking.nights,
                                    })
                                }}</template
                            >
                        </dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <Users class="size-4 text-muted-foreground" />
                        <dt class="sr-only">{{ t('Guest') }}</dt>
                        <dd>
                            {{ booking.tourist?.name }} ·
                            {{ t(':count guests', { count: booking.pax }) }}
                        </dd>
                    </div>
                    <div
                        v-if="booking.tourist?.phone"
                        class="flex items-center gap-2"
                    >
                        <Phone class="size-4 text-muted-foreground" />
                        <dt class="sr-only">{{ t('Phone') }}</dt>
                        <dd>
                            <a
                                :href="`tel:${booking.tourist.phone}`"
                                class="text-primary"
                                >{{ booking.tourist.phone }}</a
                            >
                        </dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <Mail class="size-4 text-muted-foreground" />
                        <dt class="sr-only">{{ t('Email') }}</dt>
                        <dd>{{ booking.tourist?.email }}</dd>
                    </div>
                </dl>
                <p
                    v-if="booking.tourist_note"
                    class="rounded-md bg-muted p-2 text-sm"
                >
                    “{{ booking.tourist_note }}”
                </p>
                <p
                    v-if="booking.partner_note && booking.status !== 'pending'"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('Note: :note', { note: booking.partner_note }) }}
                </p>
                <div
                    class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t pt-3"
                >
                    <span class="text-lg font-semibold">{{
                        formatPeso(booking.total_amount)
                    }}</span>
                    <span
                        v-if="booking.status === 'pending'"
                        class="text-xs text-muted-foreground"
                        >{{
                            t('Answer by :time', {
                                time: formatDateTime(booking.expires_at),
                            })
                        }}</span
                    >
                    <div class="flex gap-2">
                        <template v-if="booking.status === 'pending'">
                            <Button size="sm" @click="confirm(booking)">{{
                                t('Accept')
                            }}</Button>
                            <Button
                                size="sm"
                                variant="outline"
                                @click="decline(booking)"
                                >{{ t('Decline') }}</Button
                            >
                        </template>
                        <Button
                            v-else-if="
                                booking.status === 'confirmed' &&
                                booking.is_cancellable
                            "
                            size="sm"
                            variant="ghost"
                            class="text-destructive"
                            @click="cancel(booking)"
                        >
                            {{ t('Cancel') }}
                        </Button>
                    </div>
                </div>
            </li>
        </ul>

        <Pagination :paginator="bookings" />
    </div>
</template>
