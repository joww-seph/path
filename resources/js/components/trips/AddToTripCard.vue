<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { CalendarPlus } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTrans } from '@/composables/useTrans';
import { formatDate } from '@/lib/format';
import { login } from '@/routes';
import tourist from '@/routes/tourist';

type TripOption = {
    id: number;
    title: string;
    start_date: string;
    day_count: number;
};

const props = defineProps<{
    listingId: number;
    trips: TripOption[] | null;
}>();

const page = usePage();
const { t } = useTrans();

const tripId = ref<number | null>(props.trips?.[0]?.id ?? null);
const day = ref(1);
const adding = ref(false);

const selectedTrip = computed(() =>
    props.trips?.find((trip) => trip.id === Number(tripId.value)),
);

function dayLabel(trip: TripOption, number: number) {
    const date = new Date(`${trip.start_date}T00:00:00+08:00`);
    date.setDate(date.getDate() + number - 1);

    return t('Day :day · :date', {
        day: number,
        date: formatDate(date.toISOString()),
    });
}

function add() {
    if (!selectedTrip.value) {
        return;
    }

    adding.value = true;
    router.post(
        tourist.trips.items.store.url(selectedTrip.value.id),
        { listing_id: props.listingId, day_number: day.value },
        { preserveScroll: true, onFinish: () => (adding.value = false) },
    );
}
</script>

<template>
    <div class="rounded-xl border bg-accent/30 p-4">
        <h2 class="mb-2 flex items-center gap-2 font-semibold">
            <CalendarPlus class="size-5 text-primary" />
            {{ t('Add to my trip') }}
        </h2>

        <template v-if="!page.props.auth.user">
            <p class="mb-3 text-sm text-muted-foreground">
                {{
                    t(
                        'Plan your days in Paoay and see travel times between stops.',
                    )
                }}
            </p>
            <Button as-child class="w-full"
                ><Link :href="login()">{{
                    t('Log in to plan a trip')
                }}</Link></Button
            >
        </template>

        <p v-else-if="trips === null" class="text-sm text-muted-foreground">
            {{ t('Trip planning is for tourist accounts.') }}
        </p>

        <template v-else-if="trips.length === 0">
            <p class="mb-3 text-sm text-muted-foreground">
                {{ t('You have no upcoming trips yet.') }}
            </p>
            <Button as-child class="w-full"
                ><Link :href="tourist.trips.index()">{{
                    t('Start a trip')
                }}</Link></Button
            >
        </template>

        <form v-else class="space-y-3" @submit.prevent="add">
            <div class="grid gap-1.5">
                <Label for="add-trip">{{ t('Trip') }}</Label>
                <NativeSelect id="add-trip" v-model="tripId" @change="day = 1">
                    <option
                        v-for="trip in trips"
                        :key="trip.id"
                        :value="trip.id"
                    >
                        {{ trip.title }}
                    </option>
                </NativeSelect>
            </div>
            <div v-if="selectedTrip" class="grid gap-1.5">
                <Label for="add-day">{{ t('Day') }}</Label>
                <NativeSelect id="add-day" v-model="day">
                    <option
                        v-for="number in selectedTrip.day_count"
                        :key="number"
                        :value="number"
                    >
                        {{ dayLabel(selectedTrip, number) }}
                    </option>
                </NativeSelect>
            </div>
            <Button type="submit" class="w-full" :disabled="adding">{{
                t('Add to trip')
            }}</Button>
        </form>
    </div>
</template>
