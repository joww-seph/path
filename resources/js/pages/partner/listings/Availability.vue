<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTrans } from '@/composables/useTrans';
import partner from '@/routes/partner';

type Block = {
    slots_total: number | null;
    slots_booked: number;
    is_closed: boolean;
};

const props = defineProps<{
    listing: {
        id: number;
        name: string;
        slug: string;
        default_daily_slots: number | null;
        is_bookable: boolean;
    };
    month: string;
    blocks: Record<string, Block>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My listings', href: partner.listings.index() }],
    },
});

const { t } = useTrans();
const selected = ref<Set<string>>(new Set());
const today = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Manila',
}).format(new Date());

const firstDay = computed(() => {
    const [year, month] = props.month.split('-').map(Number);

    return new Date(year, month - 1, 1);
});

const monthLabel = computed(() =>
    firstDay.value.toLocaleDateString('en-PH', {
        month: 'long',
        year: 'numeric',
    }),
);

const cells = computed(() => {
    const year = firstDay.value.getFullYear();
    const month = firstDay.value.getMonth();
    const days = new Date(year, month + 1, 0).getDate();
    const leading = (firstDay.value.getDay() + 6) % 7;
    const result: ({
        day: number;
        key: string;
        block: Block | undefined;
    } | null)[] = Array(leading).fill(null);

    for (let day = 1; day <= days; day++) {
        const key = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        result.push({ day, key, block: props.blocks[key] });
    }

    return result;
});

function capacity(block: Block | undefined) {
    return block?.slots_total ?? props.listing.default_daily_slots;
}

function toggle(key: string) {
    if (key < today) {
        return;
    }

    const next = new Set(selected.value);

    if (next.has(key)) {
        next.delete(key);
    } else {
        next.add(key);
    }

    selected.value = next;
}

function shiftMonth(delta: number) {
    const date = new Date(firstDay.value);
    date.setMonth(date.getMonth() + delta);
    selected.value = new Set();
    router.get(partner.listings.availability.url(props.listing.slug), {
        month: `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`,
    });
}
</script>

<template>
    <Head :title="t('Availability · :name', { name: listing.name })" />

    <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="t('Availability')"
                :description="listing.name"
            />
            <Button as-child variant="outline" size="sm"
                ><Link :href="partner.listings.edit(listing.slug)">{{
                    t('Back to listing')
                }}</Link></Button
            >
        </div>

        <Form
            v-bind="partner.listings.defaultSlots.form(listing.slug)"
            :options="{ preserveScroll: true }"
            class="flex flex-wrap items-end gap-3 rounded-xl border p-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-1.5">
                <Label for="default_daily_slots">{{
                    t('Slots per day')
                }}</Label>
                <Input
                    id="default_daily_slots"
                    name="default_daily_slots"
                    type="number"
                    min="0"
                    class="w-32"
                    :default-value="listing.default_daily_slots ?? ''"
                    :placeholder="t('No limit')"
                />
                <InputError :message="errors.default_daily_slots" />
            </div>
            <Button variant="secondary" :disabled="processing">{{
                t('Save')
            }}</Button>
            <p class="w-full text-sm text-muted-foreground">
                {{
                    t(
                        'How many bookings you can take on a normal day: rooms, vehicles or seats. Leave empty for no limit. Change single days below.',
                    )
                }}
            </p>
        </Form>

        <section>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ monthLabel }}</h2>
                <div class="flex gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        :aria-label="t('Previous month')"
                        @click="shiftMonth(-1)"
                        ><ChevronLeft
                    /></Button>
                    <Button
                        variant="outline"
                        size="icon"
                        :aria-label="t('Next month')"
                        @click="shiftMonth(1)"
                        ><ChevronRight
                    /></Button>
                </div>
            </div>
            <div class="grid grid-cols-7 gap-1 text-sm">
                <div
                    v-for="day in [
                        'Mon',
                        'Tue',
                        'Wed',
                        'Thu',
                        'Fri',
                        'Sat',
                        'Sun',
                    ]"
                    :key="day"
                    class="p-1 text-center text-xs text-muted-foreground"
                >
                    {{ t(day) }}
                </div>
                <template
                    v-for="(cell, index) in cells"
                    :key="cell?.key ?? `blank-${index}`"
                >
                    <div v-if="!cell" />
                    <button
                        v-else
                        type="button"
                        class="flex min-h-16 flex-col items-start rounded-md border p-1.5 text-left"
                        :class="[
                            cell.key < today
                                ? 'cursor-not-allowed opacity-40'
                                : 'hover:border-primary',
                            selected.has(cell.key)
                                ? 'border-primary ring-2 ring-primary/40'
                                : '',
                            cell.block?.is_closed ? 'bg-muted' : '',
                        ]"
                        :aria-pressed="selected.has(cell.key)"
                        :disabled="cell.key < today"
                        @click="toggle(cell.key)"
                    >
                        <span class="font-medium">{{ cell.day }}</span>
                        <span
                            v-if="cell.block?.is_closed"
                            class="text-xs text-muted-foreground"
                            >{{ t('Closed') }}</span
                        >
                        <span
                            v-else-if="capacity(cell.block) !== null"
                            class="text-xs text-muted-foreground"
                        >
                            {{ cell.block?.slots_booked ?? 0 }}/{{
                                capacity(cell.block)
                            }}
                        </span>
                        <span
                            v-else-if="cell.block?.slots_booked"
                            class="text-xs text-muted-foreground"
                            >{{
                                t(':count booked', {
                                    count: cell.block.slots_booked,
                                })
                            }}</span
                        >
                    </button>
                </template>
            </div>
            <p class="mt-2 text-xs text-muted-foreground">
                {{
                    t(
                        'Numbers show slots booked out of slots available. Tap days to change them.',
                    )
                }}
            </p>
        </section>

        <Form
            v-if="selected.size"
            v-bind="partner.listings.availability.update.form(listing.slug)"
            :options="{ preserveScroll: true }"
            class="sticky bottom-0 flex flex-wrap items-end gap-3 rounded-xl border bg-background p-4 shadow-lg"
            v-slot="{ errors, processing }"
            @success="selected = new Set()"
        >
            <input
                v-for="date in selected"
                :key="date"
                type="hidden"
                name="dates[]"
                :value="date"
            />
            <p class="w-full text-sm font-medium">
                {{ t(':count days selected', { count: selected.size }) }}
            </p>
            <div class="grid gap-1.5">
                <Label for="slots_total">{{ t('Slots on these days') }}</Label>
                <Input
                    id="slots_total"
                    name="slots_total"
                    type="number"
                    min="0"
                    class="w-32"
                    :placeholder="t('Use default')"
                />
            </div>
            <label class="flex items-center gap-2 pb-2 text-sm">
                <input type="hidden" name="is_closed" value="0" />
                <input
                    type="checkbox"
                    name="is_closed"
                    value="1"
                    class="accent-primary"
                />
                {{ t('Closed') }}
            </label>
            <Button :disabled="processing">{{ t('Apply') }}</Button>
            <Button
                type="button"
                variant="ghost"
                @click="selected = new Set()"
                >{{ t('Clear selection') }}</Button
            >
            <InputError
                class="w-full"
                :message="errors.slots_total ?? errors.dates"
            />
        </Form>
    </div>
</template>
