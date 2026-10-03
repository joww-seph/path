<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { CalendarDays, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import BookingStatusBadge from '@/components/bookings/BookingStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { formatClock, formatDate, formatPeso } from '@/lib/format';
import partner from '@/routes/partner';
import type { Booking } from '@/types';

defineProps<{
    booking: Booking;
    token: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Bookings', href: partner.bookings.index() },
            { title: 'Check in', href: partner.checkIn.scanner() },
        ],
    },
});

const { t } = useTrans();
</script>

<template>
    <Head :title="t('Check in :code', { code: booking.code })" />

    <div class="mx-auto flex w-full max-w-md flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="booking.tourist?.name ?? ''"
                :description="booking.code"
            />
            <BookingStatusBadge
                :status="booking.status"
                :label="booking.status_label"
            />
        </div>

        <dl class="grid gap-2 rounded-xl border p-4 text-sm">
            <div>
                <dt class="text-muted-foreground">{{ t('Booked') }}</dt>
                <dd class="font-medium">
                    {{ booking.rate_name }} · {{ booking.listing?.name }}
                </dd>
            </div>
            <div class="flex items-center gap-2">
                <CalendarDays class="size-4" />
                <dd>
                    {{ formatDate(booking.date)
                    }}<template v-if="booking.time">
                        · {{ formatClock(booking.time) }}</template
                    >
                </dd>
            </div>
            <div class="flex items-center gap-2">
                <Users class="size-4" />
                <dd>{{ t(':count guests', { count: booking.pax }) }}</dd>
            </div>
            <div class="flex justify-between border-t pt-2">
                <dt class="text-muted-foreground">{{ t('To collect') }}</dt>
                <dd class="text-lg font-semibold">
                    {{ formatPeso(booking.total_amount) }}
                </dd>
            </div>
        </dl>

        <Form
            v-if="booking.status === 'confirmed'"
            v-bind="partner.checkIn.store.form()"
            v-slot="{ errors, processing }"
            class="space-y-2"
        >
            <input type="hidden" name="token" :value="token" />
            <Button class="w-full" size="lg" :disabled="processing">{{
                t('Confirm arrival')
            }}</Button>
            <InputError :message="errors.code" />
        </Form>
        <p v-else class="rounded-lg bg-muted p-3 text-sm">
            {{
                t('This booking cannot be checked in because it is :status.', {
                    status: t(booking.status_label).toLowerCase(),
                })
            }}
        </p>
    </div>
</template>
