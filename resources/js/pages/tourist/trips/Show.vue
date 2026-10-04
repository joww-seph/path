<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CloudDownload,
    CloudRain,
    Trophy,
    CalendarDays,
    Copy,
    FileDown,
    Link2,
    Plus,
    Settings2,
    Share2,
    Sparkles,
    Trash2,
    Users,
    Wallet,
    Wand2,
} from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import draggable from 'vuedraggable';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import CategoryIcon from '@/components/guide/CategoryIcon.vue';
import LeafletMap from '@/components/guide/LeafletMap.vue';
import type { MapMarker } from '@/components/guide/LeafletMap.vue';
import ItineraryItemCard from '@/components/trips/ItineraryItemCard.vue';
import TravelLeg from '@/components/trips/TravelLeg.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useTrans } from '@/composables/useTrans';
import { formatDate, formatPeso } from '@/lib/format';
import { getOfflineTrip, saveTripOffline } from '@/offline/store';
import { explore } from '@/routes';
import listingsRoutes from '@/routes/listings';
import tourist from '@/routes/tourist';
import type {
    DayForecast,
    ItineraryDay,
    ItineraryItem,
    ListingCard,
    Option,
    PlannerWarning,
    Trip,
    TripMember,
} from '@/types';

const props = defineProps<{
    trip: Trip;
    days: ItineraryDay[];
    warnings: PlannerWarning[];
    estimatedCost: number;
    members: TripMember[];
    suggestions: ListingCard[];
    travelModes: Option[];
    can: { update: boolean; manage: boolean };
    weather: Record<string, DayForecast>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My trips', href: tourist.trips.index() }],
    },
});

const { t } = useTrans();
const page = usePage();

const selectedDay = ref(1);
const savingOffline = ref(false);
const savedOfflineAt = ref<string | null>(null);

onMounted(async () => {
    savedOfflineAt.value =
        (await getOfflineTrip(props.trip.id))?.saved_at ?? null;
});

async function makeAvailableOffline() {
    savingOffline.value = true;

    try {
        const saved = await saveTripOffline(props.trip.id);
        savedOfflineAt.value = saved.saved_at;
        toast.success(
            t(
                'Saved on this phone. Open it any time from /offline, even without signal.',
            ),
        );
    } catch {
        toast.error(
            t(
                'Could not save the trip for offline use. Check your connection and try again.',
            ),
        );
    } finally {
        savingOffline.value = false;
    }
}
const editingTrip = ref(false);
const sharing = ref(false);
const editingItem = ref<ItineraryItem | null>(null);
const addingCustom = ref(false);

// Local copy of each day's stops so drag and drop feels instant; refreshed from the server after saving.
const lists = ref<Record<number, ItineraryItem[]>>({});

watch(
    () => props.days,
    (days) => {
        lists.value = Object.fromEntries(
            days.map((day) => [day.number, [...day.items]]),
        );

        if (!days.some((day) => day.number === selectedDay.value)) {
            selectedDay.value = 1;
        }
    },
    { immediate: true, deep: true },
);

const currentDay = computed(
    () =>
        props.days.find((day) => day.number === selectedDay.value) ??
        props.days[0],
);

const dayWarnings = (day: number) =>
    props.warnings.filter(
        (warning) => warning.day === day && warning.item_id === null,
    );
const itemWarnings = (id: number) =>
    props.warnings.filter((warning) => warning.item_id === id);
const warningCount = (day: number) =>
    props.warnings.filter((warning) => warning.day === day).length;

const markers = computed<MapMarker[]>(() =>
    (lists.value[selectedDay.value] ?? [])
        .map((item, index) => ({ item, index }))
        .filter(({ item }) => item.latitude !== null && item.longitude !== null)
        .map(({ item, index }) => ({
            id: item.id,
            lat: item.latitude!,
            lng: item.longitude!,
            title: item.title,
            label: index + 1,
            color: item.listing?.category?.color ?? '#475569',
        })),
);

const budgetLeft = computed(() =>
    props.trip.budget === null
        ? null
        : Number(props.trip.budget) - props.estimatedCost,
);

function weekday(date: string) {
    return new Date(`${date}T00:00:00+08:00`).toLocaleDateString('en-PH', {
        weekday: 'short',
        timeZone: 'Asia/Manila',
    });
}

function saveOrder() {
    const days = Object.fromEntries(
        Object.entries(lists.value).map(([day, items]) => [
            day,
            items.map((item) => item.id),
        ]),
    );

    router.post(
        tourist.trips.reorder.url(props.trip.id),
        { days },
        { preserveScroll: true, preserveState: true },
    );
}

function toggleDone(item: ItineraryItem) {
    router.patch(
        tourist.trips.items.update.url({ trip: props.trip.id, item: item.id }),
        { is_done: !item.is_done },
        { preserveScroll: true, preserveState: true },
    );
}

function removeItem(item: ItineraryItem) {
    if (confirm(t('Remove :name from your trip?', { name: item.title }))) {
        router.delete(
            tourist.trips.items.destroy.url({
                trip: props.trip.id,
                item: item.id,
            }),
            {
                preserveScroll: true,
            },
        );
    }
}

function addSuggestion(listing: ListingCard) {
    router.post(
        tourist.trips.items.store.url(props.trip.id),
        { listing_id: listing.id, day_number: selectedDay.value },
        { preserveScroll: true },
    );
}

function arrange(mode: 'day' | 'all') {
    const message =
        mode === 'day'
            ? t('Reorder day :day to cut travel time?', {
                  day: selectedDay.value,
              })
            : t(
                  'Spread all stops across your days and reorder them? This replaces your current order.',
              );

    if (confirm(message)) {
        router.post(
            tourist.trips.arrange.url(props.trip.id),
            { mode, day: selectedDay.value },
            { preserveScroll: true },
        );
    }
}

function toggleSharing() {
    if (props.trip.is_shared) {
        router.delete(tourist.trips.share.destroy.url(props.trip.id), {
            preserveScroll: true,
        });
    } else {
        router.post(
            tourist.trips.share.store.url(props.trip.id),
            {},
            { preserveScroll: true },
        );
    }
}

function copyShareLink() {
    if (props.trip.share_url) {
        void navigator.clipboard?.writeText(props.trip.share_url);
    }
}

function removeMember(member: TripMember) {
    if (confirm(t('Remove :name from this trip?', { name: member.name }))) {
        router.delete(
            tourist.trips.members.destroy.url({
                trip: props.trip.id,
                member: member.id,
            }),
            { preserveScroll: true },
        );
    }
}

function changeMemberRole(member: TripMember, role: string) {
    router.patch(
        tourist.trips.members.update.url({
            trip: props.trip.id,
            member: member.id,
        }),
        { role },
        { preserveScroll: true },
    );
}

function leaveTrip() {
    if (
        confirm(
            t('Leave :trip? You will no longer see it.', {
                trip: props.trip.title,
            }),
        )
    ) {
        router.delete(
            tourist.trips.members.destroy.url({
                trip: props.trip.id,
                member: page.props.auth.user.id,
            }),
        );
    }
}

function deleteTrip() {
    if (
        confirm(
            t('Delete :trip? This cannot be undone.', {
                trip: props.trip.title,
            }),
        )
    ) {
        router.delete(tourist.trips.destroy.url(props.trip.id));
    }
}
</script>

<template>
    <Head :title="trip.title" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="trip.title"
                :description="
                    t(':start – :end · :count travellers', {
                        start: formatDate(trip.start_date),
                        end: formatDate(trip.end_date),
                        count: trip.pax,
                    })
                "
            />
            <div class="flex flex-wrap gap-2">
                <Button
                    v-if="can.update"
                    variant="outline"
                    size="sm"
                    @click="editingTrip = true"
                    ><Settings2 /> {{ t('Trip details') }}</Button
                >
                <Button
                    v-if="can.manage"
                    variant="outline"
                    size="sm"
                    @click="sharing = true"
                    ><Share2 /> {{ t('Share') }}</Button
                >
                <Button as-child variant="outline" size="sm">
                    <Link :href="tourist.trips.budget(trip.id)"
                        ><Wallet /> {{ t('Budget') }}</Link
                    >
                </Button>
                <Button as-child variant="outline" size="sm">
                    <Link :href="tourist.trips.recap(trip.id)"
                        ><Trophy /> {{ t('Recap') }}</Link
                    >
                </Button>
                <Button as-child variant="outline" size="sm">
                    <a :href="tourist.trips.pdf.url(trip.id)"
                        ><FileDown /> {{ t('PDF') }}</a
                    >
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="savingOffline"
                    @click="makeAvailableOffline"
                >
                    <CloudDownload />
                    {{
                        savedOfflineAt
                            ? t('Update offline copy')
                            : t('Make available offline')
                    }}
                </Button>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border p-3">
                <p class="text-xs text-muted-foreground">
                    {{ t('Estimated entrance fees and starting prices') }}
                </p>
                <p class="text-lg font-semibold">
                    {{ formatPeso(estimatedCost) }}
                </p>
            </div>
            <div class="rounded-xl border p-3">
                <p class="text-xs text-muted-foreground">{{ t('Budget') }}</p>
                <p class="text-lg font-semibold">
                    {{ trip.budget ? formatPeso(trip.budget) : '—' }}
                </p>
                <p
                    v-if="budgetLeft !== null"
                    class="text-xs"
                    :class="
                        budgetLeft < 0
                            ? 'text-destructive'
                            : 'text-muted-foreground'
                    "
                >
                    {{
                        budgetLeft < 0
                            ? t('Over by :amount', {
                                  amount: formatPeso(-budgetLeft),
                              })
                            : t(
                                  ':amount left for food, transport and shopping',
                                  { amount: formatPeso(budgetLeft) },
                              )
                    }}
                </p>
            </div>
            <div class="rounded-xl border p-3">
                <p class="text-xs text-muted-foreground">
                    {{ t('Plan checks') }}
                </p>
                <p
                    class="flex items-center gap-1.5 text-lg font-semibold"
                    :class="
                        warnings.length
                            ? 'text-amber-700 dark:text-amber-400'
                            : 'text-green-700 dark:text-green-400'
                    "
                >
                    <AlertTriangle v-if="warnings.length" class="size-5" />
                    {{
                        warnings.length
                            ? t(':count things to check', {
                                  count: warnings.length,
                              })
                            : t('Looks good')
                    }}
                </p>
            </div>
        </div>

        <nav class="flex gap-2 overflow-x-auto pb-1" :aria-label="t('Days')">
            <button
                v-for="day in days"
                :key="day.number"
                type="button"
                class="flex shrink-0 flex-col items-start rounded-lg border px-3 py-2 text-left text-sm"
                :class="[
                    day.number === selectedDay
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'hover:border-primary',
                    { 'border-dashed opacity-70': !day.in_trip },
                ]"
                :aria-current="day.number === selectedDay ? 'true' : undefined"
                @click="selectedDay = day.number"
            >
                <span class="font-semibold">{{
                    t('Day :day', { day: day.number })
                }}</span>
                <span class="text-xs opacity-80"
                    >{{ weekday(day.date) }} ·
                    {{
                        t(':count stops', {
                            count: (lists[day.number] ?? []).length,
                        })
                    }}</span
                >
                <span
                    v-if="warningCount(day.number)"
                    class="text-xs font-medium"
                    :class="
                        day.number === selectedDay
                            ? ''
                            : 'text-amber-700 dark:text-amber-400'
                    "
                >
                    ⚠ {{ warningCount(day.number) }}
                </span>
                <span
                    v-if="weather[day.date]"
                    class="text-xs tabular-nums opacity-80"
                    >{{ weather[day.date].min }}–{{ weather[day.date].max }}°C ·
                    {{ weather[day.date].rain_chance }}% {{ t('rain') }}</span
                >
            </button>
        </nav>

        <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
            <section class="min-w-0 space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="flex items-center gap-2 text-lg font-semibold">
                        <CalendarDays class="size-5 text-primary" />
                        {{
                            new Date(
                                `${currentDay.date}T00:00:00+08:00`,
                            ).toLocaleDateString('en-PH', {
                                weekday: 'long',
                                month: 'long',
                                day: 'numeric',
                                timeZone: 'Asia/Manila',
                            })
                        }}
                    </h2>
                    <div v-if="can.update" class="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="(lists[selectedDay] ?? []).length < 3"
                            @click="arrange('day')"
                        >
                            <Wand2 /> {{ t('Best order for this day') }}
                        </Button>
                        <Button
                            v-if="trip.day_count > 1"
                            variant="outline"
                            size="sm"
                            @click="arrange('all')"
                        >
                            <Sparkles /> {{ t('Plan all days for me') }}
                        </Button>
                    </div>
                </div>

                <p
                    v-if="weather[currentDay.date]"
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <CloudRain class="size-4 shrink-0" />
                    {{
                        t(
                            ':description, :min–:max°C, :chance% chance of rain',
                            {
                                description:
                                    weather[currentDay.date].description,
                                min: weather[currentDay.date].min,
                                max: weather[currentDay.date].max,
                                chance: weather[currentDay.date].rain_chance,
                            },
                        )
                    }}
                </p>

                <p
                    v-for="warning in dayWarnings(selectedDay)"
                    :key="warning.type"
                    class="flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100"
                >
                    <AlertTriangle class="mt-0.5 size-4 shrink-0" />
                    {{ warning.message }}
                </p>

                <template v-for="day in days" :key="day.number">
                    <draggable
                        v-show="day.number === selectedDay"
                        v-model="lists[day.number]"
                        item-key="id"
                        group="itinerary"
                        handle=".drag-handle"
                        :disabled="!can.update"
                        :animation="150"
                        ghost-class="opacity-40"
                        class="min-h-24 space-y-0"
                        @end="saveOrder"
                    >
                        <template #item="{ element, index }">
                            <div>
                                <TravelLeg
                                    v-if="index > 0"
                                    :minutes="
                                        element.travel_minutes_from_previous
                                    "
                                    :km="element.distance_km_from_previous"
                                    :mode="trip.travel_mode"
                                />
                                <ItineraryItemCard
                                    :item="element"
                                    :index="index"
                                    :warnings="itemWarnings(element.id)"
                                    :editable="can.update"
                                    @edit="editingItem = element"
                                    @remove="removeItem(element)"
                                    @toggle-done="toggleDone(element)"
                                />
                            </div>
                        </template>
                        <template #header>
                            <p
                                v-if="(lists[day.number] ?? []).length === 0"
                                class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                            >
                                {{
                                    t(
                                        'Nothing planned for this day yet. Add a suggestion, a custom stop, or find places in Explore.',
                                    )
                                }}
                            </p>
                        </template>
                    </draggable>
                </template>

                <div v-if="can.update" class="flex flex-wrap gap-2 pt-2">
                    <Button as-child variant="secondary" size="sm">
                        <Link :href="explore()"
                            ><Plus /> {{ t('Find places to add') }}</Link
                        >
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="addingCustom = !addingCustom"
                        ><Plus /> {{ t('Custom stop') }}</Button
                    >
                </div>

                <Form
                    v-if="addingCustom && can.update"
                    v-bind="tourist.trips.items.store.form(trip.id)"
                    :options="{ preserveScroll: true }"
                    reset-on-success
                    class="grid gap-3 rounded-lg border p-4 sm:grid-cols-4"
                    v-slot="{ errors, processing }"
                    @success="addingCustom = false"
                >
                    <input
                        type="hidden"
                        name="day_number"
                        :value="selectedDay"
                    />
                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="custom_title">{{ t('What is it?') }}</Label>
                        <Input
                            id="custom_title"
                            name="custom_title"
                            required
                            :placeholder="t('e.g. Lunch at Lola\'s house')"
                        />
                        <InputError :message="errors.custom_title" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="custom_duration">{{ t('Minutes') }}</Label>
                        <Input
                            id="custom_duration"
                            name="duration_minutes"
                            type="number"
                            min="5"
                            max="720"
                            default-value="60"
                        />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="custom_time">{{
                            t('At a set time?')
                        }}</Label>
                        <Input
                            id="custom_time"
                            name="fixed_start_time"
                            type="time"
                        />
                    </div>
                    <div class="grid gap-1.5 sm:col-span-4">
                        <Label for="custom_notes">{{ t('Notes') }}</Label>
                        <Input id="custom_notes" name="notes" />
                    </div>
                    <div class="sm:col-span-4">
                        <Button :disabled="processing" size="sm">{{
                            t('Add to day :day', { day: selectedDay })
                        }}</Button>
                    </div>
                </Form>
            </section>

            <aside class="space-y-6">
                <div class="h-72 overflow-hidden rounded-xl border xl:h-80">
                    <LeafletMap
                        :key="selectedDay"
                        :center="
                            markers[0]
                                ? { lat: markers[0].lat, lng: markers[0].lng }
                                : { lat: 18.0617, lng: 120.5222 }
                        "
                        :zoom="13"
                        :markers="markers"
                        route
                    />
                </div>

                <section
                    v-if="can.update && suggestions.length"
                    class="space-y-3"
                >
                    <h2 class="flex items-center gap-2 font-semibold">
                        <Sparkles class="size-4 text-primary" />
                        {{ t('Suggested for you') }}
                    </h2>
                    <ul class="space-y-2">
                        <li
                            v-for="listing in suggestions"
                            :key="listing.id"
                            class="flex items-center gap-3 rounded-lg border p-2"
                        >
                            <img
                                loading="lazy"
                                v-if="listing.photo"
                                :src="listing.photo"
                                alt=""
                                class="size-12 rounded object-cover"
                            />
                            <span
                                v-else
                                class="flex size-12 items-center justify-center rounded"
                                :style="{
                                    backgroundColor: `${listing.category?.color}1a`,
                                    color: listing.category?.color,
                                }"
                            >
                                <CategoryIcon
                                    :icon="listing.category?.icon"
                                    class="size-5"
                                />
                            </span>
                            <span class="min-w-0 flex-1">
                                <Link
                                    :href="listingsRoutes.show(listing.slug)"
                                    class="block truncate text-sm font-medium hover:text-primary"
                                    >{{ listing.name }}</Link
                                >
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                    >{{ t(listing.category?.name ?? '') }}</span
                                >
                            </span>
                            <Button
                                size="sm"
                                variant="outline"
                                :aria-label="
                                    t('Add :name to day :day', {
                                        name: listing.name,
                                        day: selectedDay,
                                    })
                                "
                                @click="addSuggestion(listing)"
                            >
                                <Plus />
                            </Button>
                        </li>
                    </ul>
                </section>

                <section
                    v-if="members.length || trip.role !== 'owner'"
                    class="space-y-2 text-sm"
                >
                    <h2 class="flex items-center gap-2 font-semibold">
                        <Users class="size-4" /> {{ t('Travelling with') }}
                    </h2>
                    <p v-if="trip.owner && trip.role !== 'owner'">
                        {{ trip.owner.name }} · {{ t('Owner') }}
                    </p>
                    <p v-for="member in members" :key="member.id">
                        {{ member.name }} ·
                        {{
                            member.role === 'editor'
                                ? t('Can edit')
                                : t('Can view')
                        }}
                    </p>
                </section>

                <Button
                    v-if="can.manage"
                    variant="ghost"
                    class="text-destructive"
                    @click="deleteTrip"
                    ><Trash2 /> {{ t('Delete trip') }}</Button
                >
                <Button
                    v-else
                    variant="ghost"
                    class="text-destructive"
                    @click="leaveTrip"
                    >{{ t('Leave trip') }}</Button
                >
            </aside>
        </div>
    </div>

    <Dialog v-model:open="editingTrip">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('Trip details') }}</DialogTitle>
            </DialogHeader>
            <Form
                v-bind="tourist.trips.update.form(trip.id)"
                :options="{ preserveScroll: true }"
                class="grid gap-4 sm:grid-cols-2"
                v-slot="{ errors, processing }"
                @success="editingTrip = false"
            >
                <div class="grid gap-1.5 sm:col-span-2">
                    <Label for="edit-title">{{ t('Trip name') }}</Label>
                    <Input
                        id="edit-title"
                        name="title"
                        required
                        :default-value="trip.title"
                    />
                    <InputError :message="errors.title" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="edit-start">{{ t('First day') }}</Label>
                    <Input
                        id="edit-start"
                        name="start_date"
                        type="date"
                        required
                        :default-value="trip.start_date"
                    />
                    <InputError :message="errors.start_date" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="edit-end">{{ t('Last day') }}</Label>
                    <Input
                        id="edit-end"
                        name="end_date"
                        type="date"
                        required
                        :default-value="trip.end_date"
                    />
                    <InputError :message="errors.end_date" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="edit-pax">{{ t('Travellers') }}</Label>
                    <Input
                        id="edit-pax"
                        name="pax"
                        type="number"
                        min="1"
                        max="50"
                        required
                        :default-value="trip.pax"
                    />
                </div>
                <div class="grid gap-1.5">
                    <Label for="edit-budget">{{ t('Budget (₱)') }}</Label>
                    <Input
                        id="edit-budget"
                        name="budget"
                        type="number"
                        min="0"
                        step="100"
                        :default-value="trip.budget ?? ''"
                    />
                    <InputError :message="errors.budget" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="edit-start-time">{{
                        t('Start each day at')
                    }}</Label>
                    <Input
                        id="edit-start-time"
                        name="day_starts_at"
                        type="time"
                        :default-value="trip.day_starts_at"
                    />
                </div>
                <div class="grid gap-1.5">
                    <Label for="edit-mode">{{ t('Getting around by') }}</Label>
                    <NativeSelect
                        id="edit-mode"
                        name="travel_mode"
                        :default-value="trip.travel_mode"
                    >
                        <option
                            v-for="mode in travelModes"
                            :key="mode.value"
                            :value="mode.value"
                        >
                            {{ t(mode.label) }}
                        </option>
                    </NativeSelect>
                </div>
                <div class="grid gap-1.5 sm:col-span-2">
                    <Label for="edit-notes">{{ t('Notes') }}</Label>
                    <Textarea
                        id="edit-notes"
                        name="notes"
                        rows="3"
                        :default-value="trip.notes ?? ''"
                    />
                </div>
                <div class="sm:col-span-2">
                    <Button :disabled="processing">{{ t('Save') }}</Button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>

    <Dialog
        :open="editingItem !== null"
        @update:open="
            (open) => {
                if (!open) editingItem = null;
            }
        "
    >
        <DialogContent v-if="editingItem">
            <DialogHeader>
                <DialogTitle>{{ editingItem.title }}</DialogTitle>
                <DialogDescription>{{
                    t(
                        'Change how long you stay, pin it to a time, or move it to another day.',
                    )
                }}</DialogDescription>
            </DialogHeader>
            <Form
                :key="editingItem.id"
                v-bind="
                    tourist.trips.items.update.form({
                        trip: trip.id,
                        item: editingItem.id,
                    })
                "
                :options="{ preserveScroll: true }"
                class="grid gap-4 sm:grid-cols-2"
                v-slot="{ errors, processing }"
                @success="editingItem = null"
            >
                <div class="grid gap-1.5">
                    <Label for="item-duration">{{ t('Minutes there') }}</Label>
                    <Input
                        id="item-duration"
                        name="duration_minutes"
                        type="number"
                        min="5"
                        max="720"
                        :default-value="
                            editingItem.duration_minutes ??
                            editingItem.visit_minutes
                        "
                    />
                    <InputError :message="errors.duration_minutes" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="item-time">{{
                        t('Arrive at a set time (optional)')
                    }}</Label>
                    <Input
                        id="item-time"
                        name="fixed_start_time"
                        type="time"
                        :default-value="editingItem.fixed_start_time ?? ''"
                    />
                </div>
                <div class="grid gap-1.5">
                    <Label for="item-day">{{ t('Day') }}</Label>
                    <NativeSelect
                        id="item-day"
                        name="day_number"
                        :default-value="editingItem.day_number"
                    >
                        <option
                            v-for="day in days.filter((d) => d.in_trip)"
                            :key="day.number"
                            :value="day.number"
                        >
                            {{ t('Day :day', { day: day.number }) }} ·
                            {{ formatDate(day.date) }}
                        </option>
                    </NativeSelect>
                </div>
                <div class="grid gap-1.5 sm:col-span-2">
                    <Label for="item-notes">{{ t('Notes') }}</Label>
                    <Textarea
                        id="item-notes"
                        name="notes"
                        rows="3"
                        :default-value="editingItem.notes ?? ''"
                    />
                </div>
                <div class="sm:col-span-2">
                    <Button :disabled="processing">{{ t('Save stop') }}</Button>
                </div>
            </Form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="sharing">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ t('Share this trip') }}</DialogTitle>
                <DialogDescription>{{
                    t(
                        'Invite companions to plan with you, or share a view-only link.',
                    )
                }}</DialogDescription>
            </DialogHeader>

            <section class="space-y-3">
                <h3 class="flex items-center gap-2 text-sm font-semibold">
                    <Link2 class="size-4" /> {{ t('View-only link') }}
                </h3>
                <div v-if="trip.is_shared && trip.share_url" class="flex gap-2">
                    <Input
                        :model-value="trip.share_url"
                        readonly
                        :aria-label="t('Share link')"
                    />
                    <Button
                        variant="outline"
                        size="icon"
                        :aria-label="t('Copy link')"
                        @click="copyShareLink"
                        ><Copy
                    /></Button>
                </div>
                <Button variant="outline" size="sm" @click="toggleSharing">
                    {{ trip.is_shared ? t('Turn off link') : t('Create link') }}
                </Button>
            </section>

            <section class="space-y-3">
                <h3 class="flex items-center gap-2 text-sm font-semibold">
                    <Users class="size-4" /> {{ t('Companions') }}
                </h3>
                <ul
                    v-if="members.length"
                    class="divide-y rounded-lg border text-sm"
                >
                    <li
                        v-for="member in members"
                        :key="member.id"
                        class="flex items-center gap-2 p-2"
                    >
                        <span class="min-w-0 flex-1 truncate"
                            >{{ member.name }}
                            <span class="text-muted-foreground"
                                >· {{ member.email }}</span
                            ></span
                        >
                        <NativeSelect
                            class="h-8 w-auto"
                            :model-value="member.role"
                            :aria-label="
                                t('Role for :name', { name: member.name })
                            "
                            @change="
                                changeMemberRole(
                                    member,
                                    ($event.target as HTMLSelectElement).value,
                                )
                            "
                        >
                            <option value="editor">{{ t('Can edit') }}</option>
                            <option value="viewer">{{ t('Can view') }}</option>
                        </NativeSelect>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8"
                            :aria-label="t('Remove')"
                            @click="removeMember(member)"
                            ><Trash2
                        /></Button>
                    </li>
                </ul>
                <Form
                    v-bind="tourist.trips.members.store.form(trip.id)"
                    :options="{ preserveScroll: true }"
                    reset-on-success
                    class="flex flex-wrap gap-2"
                    v-slot="{ errors, processing }"
                >
                    <Input
                        name="email"
                        type="email"
                        required
                        class="min-w-48 flex-1"
                        :placeholder="t('Companion\'s email')"
                        :aria-label="t('Companion\'s email')"
                    />
                    <NativeSelect
                        name="role"
                        class="w-auto"
                        default-value="editor"
                        :aria-label="t('Role')"
                    >
                        <option value="editor">{{ t('Can edit') }}</option>
                        <option value="viewer">{{ t('Can view') }}</option>
                    </NativeSelect>
                    <Button :disabled="processing">{{ t('Invite') }}</Button>
                    <InputError class="w-full" :message="errors.email" />
                </Form>
            </section>
        </DialogContent>
    </Dialog>
</template>
