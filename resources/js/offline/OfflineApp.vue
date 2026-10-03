<script setup lang="ts">
import {
    CheckCircle2,
    Circle,
    CloudOff,
    Phone,
    RefreshCw,
    Siren,
    Wallet,
    Wifi,
} from '@lucide/vue';
import { computed, onMounted, reactive, ref } from 'vue';
import { useOnline } from '@/composables/useOnline';
import { formatClock, formatDate, formatPeso } from '@/lib/format';
import type { OfflineTrip } from '@/offline/store';
import {
    flushOutbox,
    listOfflineTrips,
    outboxCount,
    queue,
    updateOfflineTrip,
} from '@/offline/store';

const { online } = useOnline();
const trips = ref<OfflineTrip[]>([]);
const selectedId = ref<number | null>(null);
const selectedDay = ref(1);
const pending = ref(0);
const loading = ref(true);
const syncMessage = ref<string | null>(null);
const tab = ref<'plan' | 'spend' | 'help'>('plan');

const expense = reactive({ amount: '', category: 'food', note: '' });

const categories = [
    ['food', 'Food'],
    ['transport', 'Transport'],
    ['activities', 'Activities and fees'],
    ['pasalubong', 'Pasalubong'],
    ['lodging', 'Lodging'],
    ['others', 'Others'],
];

const current = computed(
    () => trips.value.find((trip) => trip.trip.id === selectedId.value) ?? null,
);
const day = computed(
    () =>
        current.value?.days.find((item) => item.number === selectedDay.value) ??
        null,
);
const spent = computed(() =>
    (current.value?.expenses ?? []).reduce(
        (total, item) => total + Number(item.amount),
        0,
    ),
);

async function load() {
    trips.value = await listOfflineTrips();
    pending.value = await outboxCount();

    if (selectedId.value === null && trips.value.length > 0) {
        const today = new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Asia/Manila',
        }).format(new Date());
        const ongoing = trips.value.find(
            (trip) =>
                trip.trip.start_date <= today && today <= trip.trip.end_date,
        );
        selectedId.value = (ongoing ?? trips.value[0]).trip.id;

        if (ongoing) {
            const start = new Date(
                `${ongoing.trip.start_date}T00:00:00+08:00`,
            ).getTime();
            selectedDay.value =
                Math.floor((Date.now() - start) / 86_400_000) + 1;
        }
    }

    loading.value = false;
}

async function toggleDone(itemId: number, isDone: boolean) {
    if (!current.value) {
        return;
    }

    const tripId = current.value.trip.id;
    await queue({
        type: 'item.update',
        trip_id: tripId,
        data: { item_id: itemId, is_done: !isDone },
    });
    await updateOfflineTrip(tripId, (trip) => {
        trip.days
            .flatMap((d) => d.items)
            .forEach((item) => {
                if (item.id === itemId) {
                    item.is_done = !isDone;
                }
            });
    });
    await load();
}

async function logExpense() {
    if (!current.value || !(Number(expense.amount) > 0)) {
        return;
    }

    const tripId = current.value.trip.id;
    const clientUuid = crypto.randomUUID();
    const spentOn = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Manila',
    }).format(new Date());

    await queue({
        type: 'expense.create',
        trip_id: tripId,
        data: {
            client_uuid: clientUuid,
            amount: Number(expense.amount),
            category: expense.category,
            note: expense.note || null,
            spent_on: spentOn,
        },
    });
    await updateOfflineTrip(tripId, (trip) => {
        trip.expenses.unshift({
            id: clientUuid,
            amount: Number(expense.amount),
            category: expense.category,
            note: expense.note || null,
            spent_on: spentOn,
            pending: true,
        });
    });

    expense.amount = '';
    expense.note = '';
    await load();
}

async function sync() {
    syncMessage.value = 'Syncing…';

    try {
        const result = await flushOutbox();
        syncMessage.value = result.conflicts
            ? `Synced. ${result.conflicts} change(s) were older than edits made by your companions and were skipped.`
            : 'All changes are synced.';
    } catch {
        syncMessage.value =
            'Could not sync yet. Your changes are safe on this phone.';
    }

    await load();
}

onMounted(async () => {
    await load();
    window.addEventListener('online', () => void sync());
});
</script>

<template>
    <div class="mx-auto flex min-h-dvh max-w-3xl flex-col">
        <header
            class="sticky top-0 z-10 border-b bg-background/95 px-4 py-3 backdrop-blur"
        >
            <div class="flex items-center gap-2">
                <span
                    class="flex size-8 items-center justify-center rounded-md bg-primary font-bold text-primary-foreground"
                    >P</span
                >
                <div class="flex-1 leading-tight">
                    <p class="font-semibold">PaTH · Saved trips</p>
                    <p class="text-xs text-muted-foreground">
                        Works without signal
                    </p>
                </div>
                <a
                    v-if="online"
                    href="/dashboard"
                    class="rounded-md border px-3 py-1.5 text-sm font-medium"
                    >Open PaTH</a
                >
            </div>
            <div
                class="mt-2 flex items-center gap-2 rounded-md px-3 py-2 text-sm"
                :class="
                    online
                        ? 'bg-green-50 text-green-900 dark:bg-green-950 dark:text-green-100'
                        : 'bg-amber-50 text-amber-900 dark:bg-amber-950 dark:text-amber-100'
                "
                role="status"
            >
                <Wifi v-if="online" class="size-4" />
                <CloudOff v-else class="size-4" />
                <span class="flex-1">
                    {{
                        online
                            ? 'You are back online.'
                            : 'You are offline. Showing what you saved on this phone.'
                    }}
                    <template v-if="pending">
                        {{ pending }} change(s) waiting to sync.</template
                    >
                </span>
                <button
                    v-if="online && pending"
                    type="button"
                    class="flex items-center gap-1 font-medium underline"
                    @click="sync"
                >
                    <RefreshCw class="size-3.5" /> Sync now
                </button>
            </div>
            <p v-if="syncMessage" class="mt-1 text-xs text-muted-foreground">
                {{ syncMessage }}
            </p>
        </header>

        <main class="flex-1 px-4 py-4">
            <p v-if="loading" class="text-muted-foreground">
                Loading saved trips…
            </p>

            <div
                v-else-if="trips.length === 0"
                class="rounded-xl border border-dashed p-6 text-center"
            >
                <p class="font-medium">No trips saved on this phone yet.</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    When you have signal, open a trip in PaTH and tap “Make
                    available offline”.
                </p>
            </div>

            <template v-else-if="current">
                <label v-if="trips.length > 1" class="mb-3 block text-sm">
                    <span class="sr-only">Trip</span>
                    <select
                        v-model="selectedId"
                        class="h-9 w-full rounded-md border bg-transparent px-3"
                    >
                        <option
                            v-for="trip in trips"
                            :key="trip.trip.id"
                            :value="trip.trip.id"
                        >
                            {{ trip.trip.title }}
                        </option>
                    </select>
                </label>

                <h1 class="font-display text-2xl">{{ current.trip.title }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ formatDate(current.trip.start_date) }} –
                    {{ formatDate(current.trip.end_date) }} · Saved
                    {{ formatDate(current.saved_at) }}
                </p>

                <nav
                    class="mt-4 grid grid-cols-3 gap-1 rounded-lg bg-muted p-1 text-sm"
                    aria-label="Sections"
                >
                    <button
                        v-for="[key, label] in [
                            ['plan', 'Itinerary'],
                            ['spend', 'Spending'],
                            ['help', 'Help & SOS'],
                        ]"
                        :key="key"
                        type="button"
                        class="rounded-md py-1.5 font-medium"
                        :class="
                            tab === key
                                ? 'bg-background shadow-sm'
                                : 'text-muted-foreground'
                        "
                        @click="tab = key as typeof tab"
                    >
                        {{ label }}
                    </button>
                </nav>

                <section v-if="tab === 'plan'" class="mt-4">
                    <div class="flex gap-2 overflow-x-auto pb-1">
                        <button
                            v-for="item in current.days"
                            :key="item.number"
                            type="button"
                            class="shrink-0 rounded-md border px-3 py-1.5 text-sm"
                            :class="
                                item.number === selectedDay
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : ''
                            "
                            @click="selectedDay = item.number"
                        >
                            Day {{ item.number }} · {{ formatDate(item.date) }}
                        </button>
                    </div>

                    <p
                        v-if="!day || day.items.length === 0"
                        class="mt-4 text-sm text-muted-foreground"
                    >
                        Nothing planned for this day.
                    </p>
                    <ol v-else class="mt-4 space-y-2">
                        <li
                            v-for="(item, index) in day.items"
                            :key="item.id"
                            class="flex gap-3 rounded-lg border p-3"
                            :class="{ 'opacity-60': item.is_done }"
                        >
                            <div class="w-16 shrink-0 text-sm">
                                <p class="font-semibold">
                                    {{ formatClock(item.start_time) }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ item.visit_minutes }} min
                                </p>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="font-medium"
                                    :class="{ 'line-through': item.is_done }"
                                >
                                    {{ index + 1 }}. {{ item.title }}
                                </p>
                                <p
                                    v-if="item.listing?.address"
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ item.listing.address }}
                                </p>
                                <a
                                    v-if="item.listing?.contact_phone"
                                    :href="`tel:${item.listing.contact_phone}`"
                                    class="text-sm text-primary"
                                    >Call {{ item.listing.contact_phone }}</a
                                >
                                <p
                                    v-if="item.notes"
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ item.notes }}
                                </p>
                            </div>
                            <button
                                v-if="current.can_update"
                                type="button"
                                class="self-start"
                                :aria-label="
                                    item.is_done
                                        ? 'Mark as not done'
                                        : 'Mark as done'
                                "
                                :aria-pressed="item.is_done"
                                @click="toggleDone(item.id, item.is_done)"
                            >
                                <CheckCircle2
                                    v-if="item.is_done"
                                    class="size-6 text-green-600"
                                />
                                <Circle
                                    v-else
                                    class="size-6 text-muted-foreground"
                                />
                            </button>
                        </li>
                    </ol>
                </section>

                <section v-else-if="tab === 'spend'" class="mt-4 space-y-4">
                    <div class="rounded-xl border p-4">
                        <p
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <Wallet class="size-4" /> Spent
                        </p>
                        <p class="text-2xl font-semibold">
                            {{ formatPeso(spent) }}
                        </p>
                        <p
                            v-if="current.budget.budget"
                            class="text-sm text-muted-foreground"
                        >
                            of {{ formatPeso(current.budget.budget) }} budget
                        </p>
                    </div>
                    <form
                        v-if="current.can_update"
                        class="grid gap-2 rounded-xl border p-4"
                        @submit.prevent="logExpense"
                    >
                        <p class="font-medium">Log an expense</p>
                        <input
                            v-model="expense.amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            inputmode="decimal"
                            required
                            placeholder="Amount (₱)"
                            class="h-10 rounded-md border bg-transparent px-3"
                            aria-label="Amount"
                        />
                        <select
                            v-model="expense.category"
                            class="h-10 rounded-md border bg-transparent px-3"
                            aria-label="Category"
                        >
                            <option
                                v-for="[value, label] in categories"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </option>
                        </select>
                        <input
                            v-model="expense.note"
                            placeholder="Note (optional)"
                            class="h-10 rounded-md border bg-transparent px-3"
                            aria-label="Note"
                        />
                        <button
                            type="submit"
                            class="h-10 rounded-md bg-primary font-medium text-primary-foreground"
                        >
                            Save expense
                        </button>
                        <p class="text-xs text-muted-foreground">
                            It is shared equally with your companions and syncs
                            when you are back online.
                        </p>
                    </form>
                    <ul class="divide-y rounded-xl border text-sm">
                        <li
                            v-for="item in current.expenses"
                            :key="item.id"
                            class="flex justify-between gap-2 p-3"
                        >
                            <span>
                                {{
                                    categories.find(
                                        ([value]) => value === item.category,
                                    )?.[1] ?? item.category
                                }}
                                <span
                                    v-if="item.note"
                                    class="text-muted-foreground"
                                >
                                    · {{ item.note }}</span
                                >
                                <span
                                    v-if="item.pending"
                                    class="block text-xs text-amber-700 dark:text-amber-400"
                                    >Waiting to sync</span
                                >
                            </span>
                            <span class="font-medium">{{
                                formatPeso(item.amount)
                            }}</span>
                        </li>
                    </ul>
                </section>

                <section v-else class="mt-4 space-y-4">
                    <a
                        href="tel:911"
                        class="flex items-center justify-center gap-2 rounded-xl bg-red-600 p-4 text-lg font-semibold text-white"
                    >
                        <Siren class="size-6" /> Call 911
                    </a>
                    <div class="rounded-xl border">
                        <h2 class="border-b p-3 font-semibold">Hotlines</h2>
                        <a
                            v-for="hotline in current.hotlines"
                            :key="hotline.phone"
                            :href="`tel:${hotline.phone}`"
                            class="flex items-center justify-between gap-2 border-b p-3 last:border-0"
                        >
                            <span>{{ hotline.name }}</span>
                            <span
                                class="flex items-center gap-1 font-semibold text-primary"
                                ><Phone class="size-4" />
                                {{ hotline.phone }}</span
                            >
                        </a>
                    </div>
                    <div
                        v-if="current.emergency_contacts.length"
                        class="rounded-xl border"
                    >
                        <h2 class="border-b p-3 font-semibold">
                            Your emergency contacts
                        </h2>
                        <a
                            v-for="contact in current.emergency_contacts"
                            :key="contact.phone"
                            :href="`tel:${contact.phone}`"
                            class="flex items-center justify-between gap-2 border-b p-3 last:border-0"
                        >
                            <span
                                >{{ contact.name }}
                                <span
                                    v-if="contact.relationship"
                                    class="text-muted-foreground"
                                    >· {{ contact.relationship }}</span
                                ></span
                            >
                            <span
                                class="flex items-center gap-1 font-semibold text-primary"
                                ><Phone class="size-4" />
                                {{ contact.phone }}</span
                            >
                        </a>
                    </div>
                </section>
            </template>
        </main>
    </div>
</template>
