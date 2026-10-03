<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTrans } from '@/composables/useTrans';
import { formatDate, formatPeso } from '@/lib/format';
import listingsRoutes from '@/routes/listings';
import office from '@/routes/office';

type DailyRow = {
    date: string;
    tourists: number;
    visits: number;
    bookings: number;
};

const props = defineProps<{
    range: { from: string; to: string };
    totals: {
        trips: number;
        travellers: number;
        visits: number;
        bookings: number;
        booking_value: number;
    };
    daily: DailyRow[];
    peakDates: DailyRow[];
    topSites: { name: string; slug: string; visits: number; planned: number }[];
    origins: { origin: string; total: number }[];
    bookingsByStatus: { status: string; label: string; total: number }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Visitor analytics', href: office.analytics() }],
    },
});

const { t } = useTrans();

type Measure = 'tourists' | 'visits' | 'bookings';

const measures: { value: Measure; label: string }[] = [
    { value: 'tourists', label: 'Tourists in Paoay' },
    { value: 'visits', label: 'Site visits' },
    { value: 'bookings', label: 'Bookings' },
];

const measure = ref<Measure>('tourists');
const showTable = ref(false);
const hovered = ref<number | null>(null);

const peak = computed(() =>
    Math.max(1, ...props.daily.map((row) => row[measure.value])),
);

const hoveredRow = computed(() =>
    hovered.value === null ? null : props.daily[hovered.value],
);

const largestSite = computed(() =>
    Math.max(
        1,
        ...props.topSites.map((row) => Math.max(row.visits, row.planned)),
    ),
);

const largestOrigin = computed(() =>
    Math.max(1, ...props.origins.map((row) => row.total)),
);

const today = new Date().toLocaleDateString('en-CA', {
    timeZone: 'Asia/Manila',
});

function shortDate(date: string) {
    return new Date(`${date}T00:00:00+08:00`).toLocaleDateString('en-PH', {
        month: 'short',
        day: 'numeric',
        timeZone: 'Asia/Manila',
    });
}

// Label roughly six evenly spaced days under the chart so the axis never crowds.
const axisLabels = computed(() => {
    const step = Math.max(1, Math.ceil(props.daily.length / 6));

    return props.daily
        .map((row, index) => ({ index, date: row.date }))
        .filter(({ index }) => index % step === 0);
});

const exportUrl = computed(() =>
    office.analytics.export.url({
        query: { from: props.range.from, to: props.range.to },
    }),
);
</script>

<template>
    <Head :title="t('Visitor analytics')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="t('Visitor analytics')"
                :description="
                    t(
                        'Planned trips, site visits and bookings from PaTH users, :from to :to.',
                        {
                            from: formatDate(range.from),
                            to: formatDate(range.to),
                        },
                    )
                "
            />
            <Form
                v-bind="office.analytics.form()"
                class="flex flex-wrap items-end gap-2"
                :options="{ preserveScroll: true }"
            >
                <div class="grid gap-1">
                    <Label for="from" class="text-xs">{{ t('From') }}</Label>
                    <Input
                        id="from"
                        name="from"
                        type="date"
                        :default-value="range.from"
                        class="h-9"
                    />
                </div>
                <div class="grid gap-1">
                    <Label for="to" class="text-xs">{{ t('To') }}</Label>
                    <Input
                        id="to"
                        name="to"
                        type="date"
                        :default-value="range.to"
                        class="h-9"
                    />
                </div>
                <Button size="sm" class="h-9">{{ t('Apply') }}</Button>
                <Button as-child size="sm" variant="outline" class="h-9">
                    <a :href="exportUrl"><Download /> {{ t('CSV') }}</a>
                </Button>
            </Form>
        </div>

        <dl class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            <div class="rounded-xl border p-4">
                <dt class="text-xs text-muted-foreground">
                    {{ t('Trips planned') }}
                </dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">
                    {{ totals.trips }}
                </dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-xs text-muted-foreground">
                    {{ t('Travellers') }}
                </dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">
                    {{ totals.travellers }}
                </dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-xs text-muted-foreground">
                    {{ t('Site visits') }}
                </dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">
                    {{ totals.visits }}
                </dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-xs text-muted-foreground">
                    {{ t('Confirmed bookings') }}
                </dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">
                    {{ totals.bookings }}
                </dd>
            </div>
            <div class="col-span-2 rounded-xl border p-4 lg:col-span-1">
                <dt class="text-xs text-muted-foreground">
                    {{ t('Completed booking value') }}
                </dt>
                <dd class="mt-1 text-2xl font-semibold tabular-nums">
                    {{ formatPeso(totals.booking_value) }}
                </dd>
            </div>
        </dl>

        <section class="space-y-3 rounded-xl border p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold">
                    {{ t('Day by day') }}:
                    {{
                        t(
                            measures.find((item) => item.value === measure)!
                                .label,
                        )
                    }}
                </h2>
                <div class="flex flex-wrap gap-1" role="group">
                    <Button
                        v-for="item in measures"
                        :key="item.value"
                        size="sm"
                        :variant="
                            measure === item.value ? 'default' : 'outline'
                        "
                        :aria-pressed="measure === item.value"
                        @click="measure = item.value"
                        >{{ t(item.label) }}</Button
                    >
                    <Button
                        size="sm"
                        variant="ghost"
                        :aria-pressed="showTable"
                        @click="showTable = !showTable"
                        >{{
                            showTable ? t('Show chart') : t('Show table')
                        }}</Button
                    >
                </div>
            </div>

            <div v-if="!showTable" class="relative">
                <div
                    class="flex h-48 items-end gap-px border-b border-border"
                    @mouseleave="hovered = null"
                >
                    <div
                        v-for="(row, index) in daily"
                        :key="row.date"
                        class="flex h-full min-w-0 flex-1 items-end"
                        @mouseenter="hovered = index"
                    >
                        <div
                            class="w-full rounded-t-[4px] transition-colors"
                            :class="[
                                hovered === index
                                    ? 'bg-primary'
                                    : 'bg-primary/70',
                                row.date === today ? 'bg-gold!' : '',
                            ]"
                            :style="{
                                height: row[measure]
                                    ? `${Math.max(2, (row[measure] / peak) * 100)}%`
                                    : '0',
                            }"
                        />
                    </div>
                </div>
                <div
                    v-if="hoveredRow"
                    class="pointer-events-none absolute top-0 z-10 rounded-md border bg-popover px-2 py-1 text-xs shadow"
                    :style="{
                        left: `min(calc(${((hovered! + 0.5) / daily.length) * 100}% - 4rem), calc(100% - 8rem))`,
                    }"
                    role="status"
                >
                    <p class="font-medium">
                        {{ formatDate(hoveredRow.date) }}
                    </p>
                    <p class="tabular-nums">
                        {{ t('Tourists') }}: {{ hoveredRow.tourists }}
                    </p>
                    <p class="tabular-nums">
                        {{ t('Visits') }}: {{ hoveredRow.visits }}
                    </p>
                    <p class="tabular-nums">
                        {{ t('Bookings') }}: {{ hoveredRow.bookings }}
                    </p>
                </div>
                <div class="relative mt-1 h-4 text-xs text-muted-foreground">
                    <span
                        v-for="label in axisLabels"
                        :key="label.date"
                        class="absolute whitespace-nowrap"
                        :style="{
                            left: `${(label.index / daily.length) * 100}%`,
                        }"
                        >{{ shortDate(label.date) }}</span
                    >
                </div>
                <p class="mt-2 text-xs text-muted-foreground">
                    {{
                        t(
                            'Highest: :count on one day. Today is shown in gold.',
                            {
                                count: peak,
                            },
                        )
                    }}
                </p>
            </div>

            <div v-else class="max-h-96 overflow-auto">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-background">
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="py-2 font-medium">{{ t('Date') }}</th>
                            <th class="py-2 text-right font-medium">
                                {{ t('Tourists') }}
                            </th>
                            <th class="py-2 text-right font-medium">
                                {{ t('Visits') }}
                            </th>
                            <th class="py-2 text-right font-medium">
                                {{ t('Bookings') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in daily"
                            :key="row.date"
                            class="border-b last:border-0"
                        >
                            <td class="py-1.5">{{ formatDate(row.date) }}</td>
                            <td class="py-1.5 text-right tabular-nums">
                                {{ row.tourists }}
                            </td>
                            <td class="py-1.5 text-right tabular-nums">
                                {{ row.visits }}
                            </td>
                            <td class="py-1.5 text-right tabular-nums">
                                {{ row.bookings }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="space-y-3 rounded-xl border p-4">
                <h2 class="font-semibold">{{ t('Most visited sites') }}</h2>
                <p
                    v-if="topSites.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No visits in this period yet.') }}
                </p>
                <ol v-else class="space-y-3">
                    <li v-for="site in topSites" :key="site.slug">
                        <div class="flex justify-between gap-2 text-sm">
                            <Link
                                :href="listingsRoutes.show(site.slug)"
                                class="truncate font-medium hover:text-primary"
                                >{{ site.name }}</Link
                            >
                            <span
                                class="shrink-0 text-muted-foreground tabular-nums"
                                >{{
                                    t(':visits visits · :planned planned', {
                                        visits: site.visits,
                                        planned: site.planned,
                                    })
                                }}</span
                            >
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full bg-primary"
                                :style="{
                                    width: `${(site.visits / largestSite) * 100}%`,
                                }"
                            />
                        </div>
                    </li>
                </ol>
            </section>

            <section class="space-y-3 rounded-xl border p-4">
                <h2 class="font-semibold">
                    {{ t('Where visitors come from') }}
                </h2>
                <p
                    v-if="origins.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No visitor profiles in this period yet.') }}
                </p>
                <ol v-else class="space-y-3">
                    <li v-for="row in origins.slice(0, 10)" :key="row.origin">
                        <div class="flex justify-between gap-2 text-sm">
                            <span class="truncate">{{ row.origin }}</span>
                            <span class="font-medium tabular-nums">{{
                                row.total
                            }}</span>
                        </div>
                        <div class="mt-1 h-2 rounded-full bg-muted">
                            <div
                                class="h-2 rounded-full bg-primary"
                                :style="{
                                    width: `${(row.total / largestOrigin) * 100}%`,
                                }"
                            />
                        </div>
                    </li>
                </ol>
            </section>

            <section class="space-y-3 rounded-xl border p-4">
                <h2 class="font-semibold">{{ t('Busiest days') }}</h2>
                <p
                    v-if="peakDates.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No trips planned in this period yet.') }}
                </p>
                <ol v-else class="divide-y text-sm">
                    <li
                        v-for="row in peakDates"
                        :key="row.date"
                        class="flex justify-between py-2"
                    >
                        <span>{{ formatDate(row.date) }}</span>
                        <span class="tabular-nums">{{
                            t(':count tourists', { count: row.tourists })
                        }}</span>
                    </li>
                </ol>
            </section>

            <section class="space-y-3 rounded-xl border p-4">
                <h2 class="font-semibold">{{ t('Bookings by status') }}</h2>
                <p
                    v-if="bookingsByStatus.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No bookings in this period yet.') }}
                </p>
                <ul v-else class="divide-y text-sm">
                    <li
                        v-for="row in bookingsByStatus"
                        :key="row.status"
                        class="flex justify-between py-2"
                    >
                        <span>{{ t(row.label) }}</span>
                        <span class="font-medium tabular-nums">{{
                            row.total
                        }}</span>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
