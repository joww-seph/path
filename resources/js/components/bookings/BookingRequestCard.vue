<script setup lang="ts">
import { Form, Link, usePage } from '@inertiajs/vue3';
import { Ticket } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useTrans } from '@/composables/useTrans';
import { formatPeso } from '@/lib/format';
import { login } from '@/routes';
import tourist from '@/routes/tourist';
import type { ListingRate } from '@/types';

const props = defineProps<{
    listingId: number;
    rates: ListingRate[];
    availability: Record<string, number | null>;
    trips:
        | { id: number; title: string; start_date: string; day_count: number }[]
        | null;
}>();

const page = usePage();
const { t } = useTrans();

const today = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Manila',
}).format(new Date());
const rateId = ref(props.rates[0]?.id);
const date = ref(
    Object.entries(props.availability).find(([, slots]) => slots !== 0)?.[0] ??
        today,
);
const pax = ref(2);
const nights = ref(1);

const rate = computed(() =>
    props.rates.find((item) => item.id === Number(rateId.value)),
);
const slots = computed(() => props.availability[date.value]);

/**
 * Mirrors BookingService::quote so the tourist sees the price before sending the request.
 */
const quote = computed(() => {
    if (!rate.value) {
        return null;
    }

    const price = Number(rate.value.price);
    const groups = rate.value.capacity
        ? Math.ceil(pax.value / rate.value.capacity)
        : 1;

    switch (rate.value.unit) {
        case 'person':
        case 'item':
            return { quantity: pax.value, total: price * pax.value };
        case 'night':
            return {
                quantity: groups,
                total: price * groups * Math.max(1, nights.value),
            };
        case 'hour':
            return { quantity: 1, total: price };
        default:
            return { quantity: groups, total: price * groups };
    }
});

const tripForDate = computed(() =>
    props.trips?.find((trip) => {
        const end = new Date(`${trip.start_date}T00:00:00`);
        end.setDate(end.getDate() + trip.day_count - 1);

        return (
            trip.start_date <= date.value &&
            date.value <= new Intl.DateTimeFormat('en-CA').format(end)
        );
    }),
);
</script>

<template>
    <div class="rounded-xl border-2 border-primary/30 p-4">
        <h2 class="mb-3 flex items-center gap-2 font-semibold">
            <Ticket class="size-5 text-primary" /> {{ t('Book') }}
        </h2>

        <template v-if="!page.props.auth.user">
            <p class="mb-3 text-sm text-muted-foreground">
                {{
                    t(
                        'Log in to request a booking. The partner confirms it and you get a QR voucher.',
                    )
                }}
            </p>
            <Button as-child class="w-full"
                ><Link :href="login()">{{ t('Log in to book') }}</Link></Button
            >
        </template>

        <p v-else-if="trips === null" class="text-sm text-muted-foreground">
            {{ t('Bookings are made from tourist accounts.') }}
        </p>

        <Form
            v-else
            v-bind="tourist.bookings.store.form()"
            class="space-y-3"
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="listing_id" :value="listingId" />
            <div class="grid gap-1.5">
                <Label for="booking-rate">{{ t('Option') }}</Label>
                <NativeSelect
                    id="booking-rate"
                    v-model="rateId"
                    name="listing_rate_id"
                >
                    <option
                        v-for="item in rates"
                        :key="item.id"
                        :value="item.id"
                    >
                        {{ item.name }} · {{ formatPeso(item.price) }}
                        {{ t(item.unit_label) }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.listing_rate_id" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-1.5">
                    <Label for="booking-date">{{
                        rate?.unit === 'night' ? t('Check-in') : t('Date')
                    }}</Label>
                    <Input
                        id="booking-date"
                        v-model="date"
                        name="date"
                        type="date"
                        :min="today"
                        required
                    />
                </div>
                <div class="grid gap-1.5">
                    <Label for="booking-time">{{ t('Time (optional)') }}</Label>
                    <Input id="booking-time" name="time" type="time" />
                </div>
            </div>
            <p v-if="slots === 0" class="text-sm text-destructive">
                {{ t('Fully booked or closed on this date.') }}
            </p>
            <p
                v-else-if="slots !== undefined && slots !== null"
                class="text-sm text-muted-foreground"
            >
                {{ t(':count slots left', { count: slots }) }}
            </p>
            <InputError :message="errors.date" />
            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-1.5">
                    <Label for="booking-pax">{{ t('Guests') }}</Label>
                    <Input
                        id="booking-pax"
                        v-model.number="pax"
                        name="pax"
                        type="number"
                        min="1"
                        max="100"
                        required
                    />
                    <InputError :message="errors.pax" />
                </div>
                <div v-if="rate?.unit === 'night'" class="grid gap-1.5">
                    <Label for="booking-nights">{{ t('Nights') }}</Label>
                    <Input
                        id="booking-nights"
                        v-model.number="nights"
                        name="nights"
                        type="number"
                        min="1"
                        max="14"
                    />
                </div>
            </div>
            <div v-if="trips.length" class="grid gap-1.5">
                <Label for="booking-trip">{{ t('Add to trip') }}</Label>
                <NativeSelect
                    id="booking-trip"
                    name="trip_id"
                    :model-value="tripForDate?.id ?? ''"
                >
                    <option value="">{{ t('Not linked to a trip') }}</option>
                    <option
                        v-for="trip in trips"
                        :key="trip.id"
                        :value="trip.id"
                    >
                        {{ trip.title }}
                    </option>
                </NativeSelect>
            </div>
            <div class="grid gap-1.5">
                <Label for="booking-note">{{
                    t('Message to the partner (optional)')
                }}</Label>
                <Textarea
                    id="booking-note"
                    name="tourist_note"
                    rows="2"
                    :placeholder="t('e.g. pick-up at our inn, a child seat')"
                />
            </div>
            <div
                v-if="quote"
                class="flex items-baseline justify-between rounded-md bg-muted p-3"
            >
                <span class="text-sm text-muted-foreground">{{
                    t('Total to pay the partner')
                }}</span>
                <span class="text-lg font-semibold">{{
                    formatPeso(quote.total)
                }}</span>
            </div>
            <InputError :message="errors.booking" />
            <Button class="w-full" :disabled="processing || slots === 0">{{
                t('Request booking')
            }}</Button>
            <p class="text-xs text-muted-foreground">
                {{
                    t(
                        'No payment now. The partner has 48 hours to confirm, then you pay on site or as they instruct.',
                    )
                }}
            </p>
        </Form>
    </div>
</template>
