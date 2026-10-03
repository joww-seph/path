<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import StatCard from '@/components/StatCard.vue';
import { useTrans } from '@/composables/useTrans';
import { formatPeso } from '@/lib/format';
import partner from '@/routes/partner';

const props = defineProps<{
    byMonth: { month: string; bookings: number; earnings: number }[];
    totals: {
        requests: number;
        completed: number;
        earnings: number;
        acceptance_rate: number | null;
    };
    topRates: { name: string; bookings: number; revenue: number }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Reports', href: partner.reports() }],
    },
});

const { t } = useTrans();
const maxEarnings = computed(() =>
    Math.max(1, ...props.byMonth.map((row) => row.earnings)),
);

function monthLabel(month: string) {
    const [year, number] = month.split('-').map(Number);

    return new Date(year, number - 1, 1).toLocaleDateString('en-PH', {
        month: 'short',
    });
}
</script>

<template>
    <Head :title="t('Reports')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('Reports')"
            :description="t('The last 12 months of bookings through PaTH.')"
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard :label="t('Booking requests')" :value="totals.requests" />
            <StatCard
                :label="t('Completed bookings')"
                :value="totals.completed"
            />
            <StatCard
                :label="t('Earnings from completed bookings')"
                :value="formatPeso(totals.earnings)"
            />
            <StatCard
                :label="t('Requests accepted')"
                :value="
                    totals.acceptance_rate === null
                        ? '—'
                        : `${totals.acceptance_rate}%`
                "
            />
        </div>

        <section class="rounded-xl border p-4">
            <h2 class="mb-4 font-semibold">{{ t('Earnings by month') }}</h2>
            <div
                class="flex h-48 items-end gap-1.5 border-b sm:gap-3"
                role="img"
                :aria-label="t('Earnings by month')"
            >
                <div
                    v-for="row in byMonth"
                    :key="row.month"
                    class="flex h-full flex-1 flex-col justify-end"
                    :title="`${monthLabel(row.month)}: ${formatPeso(row.earnings)} · ${row.bookings}`"
                >
                    <span
                        v-if="row.earnings"
                        class="mb-1 hidden text-center text-[10px] text-muted-foreground tabular-nums sm:block"
                        >{{ formatPeso(row.earnings) }}</span
                    >
                    <span
                        class="block rounded-t-[4px] bg-primary"
                        :style="{
                            height: `${row.earnings ? Math.max(2, (row.earnings / maxEarnings) * 80) : 0}%`,
                        }"
                    />
                </div>
            </div>
            <div class="mt-1 flex gap-1.5 sm:gap-3">
                <span
                    v-for="row in byMonth"
                    :key="row.month"
                    class="flex-1 text-center text-xs text-muted-foreground"
                    >{{ monthLabel(row.month) }}</span
                >
            </div>
            <table class="mt-4 w-full text-sm">
                <caption class="sr-only">
                    {{
                        t('Bookings and earnings by month')
                    }}
                </caption>
                <thead class="text-muted-foreground">
                    <tr>
                        <th class="py-1 text-left font-medium">
                            {{ t('Month') }}
                        </th>
                        <th class="py-1 text-right font-medium">
                            {{ t('Bookings') }}
                        </th>
                        <th class="py-1 text-right font-medium">
                            {{ t('Earnings') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="row in byMonth.filter(
                            (item) => item.bookings || item.earnings,
                        )"
                        :key="row.month"
                    >
                        <td class="py-1">
                            {{ monthLabel(row.month) }}
                            {{ row.month.slice(0, 4) }}
                        </td>
                        <td class="py-1 text-right tabular-nums">
                            {{ row.bookings }}
                        </td>
                        <td class="py-1 text-right tabular-nums">
                            {{ formatPeso(row.earnings) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="rounded-xl border p-4">
            <h2 class="mb-3 font-semibold">{{ t('Top-selling rates') }}</h2>
            <p
                v-if="topRates.length === 0"
                class="text-sm text-muted-foreground"
            >
                {{ t('No confirmed bookings yet.') }}
            </p>
            <ol v-else class="divide-y text-sm">
                <li
                    v-for="(rate, index) in topRates"
                    :key="rate.name"
                    class="flex items-center justify-between gap-2 py-2"
                >
                    <span>{{ index + 1 }}. {{ rate.name }}</span>
                    <span class="text-muted-foreground"
                        >{{ t(':count bookings', { count: rate.bookings }) }} ·
                        {{ formatPeso(rate.revenue) }}</span
                    >
                </li>
            </ol>
        </section>
    </div>
</template>
