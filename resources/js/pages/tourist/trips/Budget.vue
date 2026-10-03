<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    Pencil,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTrans } from '@/composables/useTrans';
import { formatDate, formatPeso } from '@/lib/format';
import tourist from '@/routes/tourist';
import type { Option, Trip } from '@/types';

type Expense = {
    id: number;
    category: string;
    amount: string;
    spent_on: string;
    note: string | null;
    paid_by: number | null;
    payer: string | null;
    split_between: number[];
};

type Summary = {
    spent: number;
    budget: number | null;
    estimated: number;
    remaining: number | null;
    percent: number | null;
    by_category: { category: string; label: string; total: number }[];
    by_day: { date: string; total: number }[];
    balances: {
        user_id: number;
        name: string;
        paid: number;
        share: number;
        net: number;
    }[];
    settlements: { from: string; to: string; amount: number }[];
};

const props = defineProps<{
    trip: Trip;
    summary: Summary;
    expenses: Expense[];
    travellers: { id: number; name: string }[];
    categories: Option[];
    can: { update: boolean };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My trips', href: tourist.trips.index() }],
    },
});

const { t } = useTrans();
const editing = ref<Expense | null>(null);
const today = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Manila',
}).format(new Date());

const status = computed(() => {
    const percent = props.summary.percent;

    if (percent === null) {
        return null;
    }

    if (percent >= 100) {
        return { tone: 'critical', label: t('Over budget') };
    }

    if (percent >= 80) {
        return { tone: 'warning', label: t('Nearly at your budget') };
    }

    return { tone: 'good', label: t('Within budget') };
});

const statusClasses: Record<string, { bar: string; text: string }> = {
    good: { bar: 'bg-green-600', text: 'text-green-700 dark:text-green-400' },
    warning: {
        bar: 'bg-amber-500',
        text: 'text-amber-700 dark:text-amber-400',
    },
    critical: { bar: 'bg-red-600', text: 'text-red-700 dark:text-red-400' },
};

const maxCategory = computed(() =>
    Math.max(1, ...props.summary.by_category.map((row) => row.total)),
);
const maxDay = computed(() =>
    Math.max(1, ...props.summary.by_day.map((row) => row.total)),
);
const categoryLabel = (value: string) =>
    props.categories.find((category) => category.value === value)?.label ??
    value;

function remove(expense: Expense) {
    if (confirm(t('Delete this expense?'))) {
        router.delete(
            tourist.trips.expenses.destroy.url({
                trip: props.trip.id,
                expense: expense.id,
            }),
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head :title="t('Budget · :trip', { trip: trip.title })" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="t('Budget')"
                :description="trip.title"
            />
            <Button as-child variant="outline" size="sm">
                <Link :href="tourist.trips.show(trip.id)">{{
                    t('Back to itinerary')
                }}</Link>
            </Button>
        </div>

        <section class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border p-4 lg:col-span-2">
                <div
                    class="flex flex-wrap items-baseline justify-between gap-2"
                >
                    <p class="text-sm text-muted-foreground">
                        {{ t('Spent so far') }}
                    </p>
                    <p
                        v-if="status"
                        class="flex items-center gap-1 text-sm font-medium"
                        :class="statusClasses[status.tone].text"
                    >
                        <AlertTriangle
                            v-if="status.tone !== 'good'"
                            class="size-4"
                        />
                        <CheckCircle2 v-else class="size-4" />
                        {{ status.label }}
                    </p>
                </div>
                <p class="mt-1 text-3xl font-semibold tabular-nums">
                    {{ formatPeso(summary.spent) }}
                </p>
                <template
                    v-if="
                        summary.budget !== null &&
                        summary.percent !== null &&
                        status
                    "
                >
                    <div
                        class="mt-3 h-3 overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="Math.round(summary.percent)"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-label="t('Budget used')"
                    >
                        <div
                            class="h-full rounded-full"
                            :class="statusClasses[status.tone].bar"
                            :style="{
                                width: `${Math.min(100, summary.percent)}%`,
                            }"
                        />
                    </div>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{
                            t(':percent% of :budget', {
                                percent: summary.percent,
                                budget: formatPeso(summary.budget),
                            })
                        }}
                        ·
                        <span
                            v-if="
                                summary.remaining !== null &&
                                summary.remaining >= 0
                            "
                            >{{
                                t(':amount left', {
                                    amount: formatPeso(summary.remaining),
                                })
                            }}</span
                        >
                        <span
                            v-else-if="summary.remaining !== null"
                            class="text-red-700 dark:text-red-400"
                            >{{
                                t('Over by :amount', {
                                    amount: formatPeso(-summary.remaining),
                                })
                            }}</span
                        >
                    </p>
                </template>
                <p v-else class="mt-2 text-sm text-muted-foreground">
                    {{
                        t(
                            'Set a budget in the trip details to get alerts at 80% and 100%.',
                        )
                    }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">
                    {{ t('Planned fees from your itinerary') }}
                </p>
                <p class="mt-1 text-3xl font-semibold tabular-nums">
                    {{ formatPeso(summary.estimated) }}
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{
                        t(
                            'Entrance fees and starting prices for :count travellers.',
                            { count: trip.pax },
                        )
                    }}
                </p>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[1fr_24rem]">
            <div class="min-w-0 space-y-6">
                <section class="rounded-xl border p-4">
                    <h2 class="mb-4 font-semibold">
                        {{ t('Spending by category') }}
                    </h2>
                    <ul class="space-y-3">
                        <li
                            v-for="row in summary.by_category"
                            :key="row.category"
                            class="grid grid-cols-[8rem_1fr_6rem] items-center gap-3 text-sm"
                        >
                            <span class="truncate">{{ t(row.label) }}</span>
                            <span
                                class="h-5 rounded-r bg-muted/40"
                                :title="`${t(row.label)}: ${formatPeso(row.total)}`"
                            >
                                <span
                                    v-if="row.total > 0"
                                    class="block h-full rounded-r-[4px] bg-primary"
                                    :style="{
                                        width: `${Math.max(2, (row.total / maxCategory) * 100)}%`,
                                    }"
                                />
                            </span>
                            <span
                                class="text-right tabular-nums"
                                :class="
                                    row.total ? '' : 'text-muted-foreground'
                                "
                                >{{ formatPeso(row.total) }}</span
                            >
                        </li>
                    </ul>
                </section>

                <section
                    v-if="summary.by_day.length"
                    class="rounded-xl border p-4"
                >
                    <h2 class="mb-4 font-semibold">
                        {{ t('Spending by day') }}
                    </h2>
                    <div
                        class="flex h-40 items-end gap-2 border-b"
                        role="img"
                        :aria-label="t('Spending by day')"
                    >
                        <div
                            v-for="row in summary.by_day"
                            :key="row.date"
                            class="group relative flex h-full flex-1 flex-col justify-end"
                        >
                            <span
                                class="mb-1 text-center text-xs text-muted-foreground tabular-nums"
                                >{{ formatPeso(row.total) }}</span
                            >
                            <span
                                class="block rounded-t-[4px] bg-primary"
                                :style="{
                                    height: `${Math.max(2, (row.total / maxDay) * 80)}%`,
                                }"
                                :title="`${formatDate(row.date)}: ${formatPeso(row.total)}`"
                            />
                        </div>
                    </div>
                    <div class="mt-1 flex gap-2">
                        <span
                            v-for="row in summary.by_day"
                            :key="row.date"
                            class="flex-1 text-center text-xs text-muted-foreground"
                            >{{ formatDate(row.date) }}</span
                        >
                    </div>
                </section>

                <section class="rounded-xl border">
                    <h2 class="border-b p-4 font-semibold">
                        {{ t('Expenses') }}
                    </h2>
                    <p
                        v-if="expenses.length === 0"
                        class="p-6 text-center text-sm text-muted-foreground"
                    >
                        {{ t('No expenses logged yet.') }}
                    </p>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-muted/50 text-muted-foreground">
                                <tr>
                                    <th class="p-3 font-medium">
                                        {{ t('Date') }}
                                    </th>
                                    <th class="p-3 font-medium">
                                        {{ t('What') }}
                                    </th>
                                    <th class="p-3 font-medium">
                                        {{ t('Paid by') }}
                                    </th>
                                    <th class="p-3 text-right font-medium">
                                        {{ t('Amount') }}
                                    </th>
                                    <th class="p-3">
                                        <span class="sr-only">{{
                                            t('Actions')
                                        }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="expense in expenses"
                                    :key="expense.id"
                                >
                                    <td
                                        class="p-3 whitespace-nowrap text-muted-foreground"
                                    >
                                        {{ formatDate(expense.spent_on) }}
                                    </td>
                                    <td class="p-3">
                                        <p>
                                            {{
                                                t(
                                                    categoryLabel(
                                                        expense.category,
                                                    ),
                                                )
                                            }}
                                        </p>
                                        <p
                                            v-if="expense.note"
                                            class="text-muted-foreground"
                                        >
                                            {{ expense.note }}
                                        </p>
                                    </td>
                                    <td class="p-3 text-muted-foreground">
                                        {{ expense.payer ?? '—' }}
                                        <span
                                            v-if="
                                                expense.split_between.length > 1
                                            "
                                            class="block text-xs"
                                            >{{
                                                t('split :count ways', {
                                                    count: expense.split_between
                                                        .length,
                                                })
                                            }}</span
                                        >
                                    </td>
                                    <td
                                        class="p-3 text-right font-medium tabular-nums"
                                    >
                                        {{ formatPeso(expense.amount) }}
                                    </td>
                                    <td
                                        class="p-3 text-right whitespace-nowrap"
                                    >
                                        <template v-if="can.update">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-8"
                                                :aria-label="t('Edit')"
                                                @click="editing = expense"
                                                ><Pencil
                                            /></Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="size-8"
                                                :aria-label="t('Delete')"
                                                @click="remove(expense)"
                                                ><Trash2
                                            /></Button>
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <Form
                    v-if="can.update"
                    :key="editing?.id ?? 'new'"
                    v-bind="
                        editing
                            ? tourist.trips.expenses.update.form({
                                  trip: trip.id,
                                  expense: editing.id,
                              })
                            : tourist.trips.expenses.store.form(trip.id)
                    "
                    :options="{ preserveScroll: true }"
                    reset-on-success
                    class="space-y-3 rounded-xl border p-4"
                    v-slot="{ errors, processing }"
                    @success="editing = null"
                >
                    <h2 class="font-semibold">
                        {{ editing ? t('Edit expense') : t('Log an expense') }}
                    </h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="grid gap-1.5">
                            <Label for="amount">{{ t('Amount (₱)') }}</Label>
                            <Input
                                id="amount"
                                name="amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                inputmode="decimal"
                                required
                                :default-value="editing?.amount"
                            />
                            <InputError :message="errors.amount" />
                        </div>
                        <div class="grid gap-1.5">
                            <Label for="spent_on">{{ t('Date') }}</Label>
                            <Input
                                id="spent_on"
                                name="spent_on"
                                type="date"
                                required
                                :default-value="
                                    editing?.spent_on ??
                                    (today >= trip.start_date &&
                                    today <= trip.end_date
                                        ? today
                                        : trip.start_date)
                                "
                            />
                            <InputError :message="errors.spent_on" />
                        </div>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="category">{{ t('Category') }}</Label>
                        <NativeSelect
                            id="category"
                            name="category"
                            :default-value="editing?.category ?? 'food'"
                        >
                            <option
                                v-for="category in categories"
                                :key="category.value"
                                :value="category.value"
                            >
                                {{ t(category.label) }}
                            </option>
                        </NativeSelect>
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="note">{{ t('Note') }}</Label>
                        <Input
                            id="note"
                            name="note"
                            :placeholder="t('e.g. Bagnet lunch')"
                            :default-value="editing?.note ?? ''"
                        />
                    </div>
                    <template v-if="travellers.length > 1">
                        <div class="grid gap-1.5">
                            <Label for="paid_by">{{ t('Paid by') }}</Label>
                            <NativeSelect
                                id="paid_by"
                                name="paid_by"
                                :default-value="
                                    editing?.paid_by ?? travellers[0].id
                                "
                            >
                                <option
                                    v-for="person in travellers"
                                    :key="person.id"
                                    :value="person.id"
                                >
                                    {{ person.name }}
                                </option>
                            </NativeSelect>
                        </div>
                        <fieldset class="space-y-1.5">
                            <legend class="text-sm font-medium">
                                {{ t('Split between') }}
                            </legend>
                            <label
                                v-for="person in travellers"
                                :key="person.id"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    name="split_between[]"
                                    :value="person.id"
                                    class="accent-primary"
                                    :checked="
                                        editing
                                            ? editing.split_between.includes(
                                                  person.id,
                                              )
                                            : true
                                    "
                                />
                                {{ person.name }}
                            </label>
                        </fieldset>
                    </template>
                    <div class="flex gap-2">
                        <Button :disabled="processing">{{
                            editing ? t('Save expense') : t('Add expense')
                        }}</Button>
                        <Button
                            v-if="editing"
                            type="button"
                            variant="ghost"
                            @click="editing = null"
                            >{{ t('Cancel') }}</Button
                        >
                    </div>
                </Form>

                <section
                    v-if="travellers.length > 1"
                    class="space-y-3 rounded-xl border p-4"
                >
                    <h2 class="font-semibold">{{ t('Who owes whom') }}</h2>
                    <table class="w-full text-sm">
                        <thead class="text-muted-foreground">
                            <tr>
                                <th class="pb-1 text-left font-medium">
                                    {{ t('Traveller') }}
                                </th>
                                <th class="pb-1 text-right font-medium">
                                    {{ t('Paid') }}
                                </th>
                                <th class="pb-1 text-right font-medium">
                                    {{ t('Share') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in summary.balances"
                                :key="row.user_id"
                            >
                                <td class="py-1">{{ row.name }}</td>
                                <td class="py-1 text-right tabular-nums">
                                    {{ formatPeso(row.paid) }}
                                </td>
                                <td class="py-1 text-right tabular-nums">
                                    {{ formatPeso(row.share) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p
                        v-if="summary.settlements.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ t('Everyone is square.') }}
                    </p>
                    <ul v-else class="space-y-1.5 text-sm">
                        <li
                            v-for="(payment, index) in summary.settlements"
                            :key="index"
                            class="flex flex-wrap items-center gap-1.5 rounded-md bg-muted/60 p-2"
                        >
                            <span class="font-medium">{{ payment.from }}</span>
                            <ArrowRight
                                class="size-4 text-muted-foreground"
                                :aria-label="t('pays')"
                            />
                            <span class="font-medium">{{ payment.to }}</span>
                            <span class="ml-auto font-semibold tabular-nums">{{
                                formatPeso(payment.amount)
                            }}</span>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</template>
