<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, ChevronLeft, ChevronRight, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { formatDate, formatTime } from '@/lib/format';
import eventsRoutes from '@/routes/events';
import type { PaoayEvent } from '@/types';

const props = defineProps<{
    month: string;
    events: PaoayEvent[];
    upcoming: PaoayEvent[];
}>();

const { t } = useTrans();

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

function shiftMonth(delta: number) {
    const date = new Date(firstDay.value);
    date.setMonth(date.getMonth() + delta);
    const value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    router.get(
        eventsRoutes.index.url(),
        { month: value },
        { preserveScroll: true },
    );
}

/**
 * The calendar grid: leading blanks so the 1st lands on its weekday, then each day of the month.
 */
const cells = computed(() => {
    const year = firstDay.value.getFullYear();
    const month = firstDay.value.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const leading = (firstDay.value.getDay() + 6) % 7;

    const days: ({ day: number; key: string; events: PaoayEvent[] } | null)[] =
        Array(leading).fill(null);

    for (let day = 1; day <= daysInMonth; day++) {
        const key = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        days.push({
            day,
            key,
            events: props.events.filter((event) => {
                const start = manilaDate(event.starts_at);
                const end = manilaDate(event.ends_at ?? event.starts_at);

                return start <= key && key <= end;
            }),
        });
    }

    return days;
});

function manilaDate(value: string) {
    return new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Manila' }).format(
        new Date(value),
    );
}

const todayKey = manilaDate(new Date().toISOString());
</script>

<template>
    <Head :title="t('Events in Paoay')">
        <meta
            name="description"
            content="Festivals, feast days and local events in Paoay, Ilocos Norte."
        />
    </Head>

    <div class="mx-auto max-w-7xl px-4 py-8 md:px-6">
        <h1 class="font-display text-3xl md:text-4xl">
            {{ t('Events in Paoay') }}
        </h1>
        <p class="mt-2 text-muted-foreground">
            {{
                t(
                    'Festivals, feast days and local activities. Plan around them, or plan for them.',
                )
            }}
        </p>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_20rem]">
            <section>
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">{{ monthLabel }}</h2>
                    <div class="flex gap-1">
                        <Button
                            variant="outline"
                            size="icon"
                            :aria-label="t('Previous month')"
                            @click="shiftMonth(-1)"
                        >
                            <ChevronLeft />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            :aria-label="t('Next month')"
                            @click="shiftMonth(1)"
                        >
                            <ChevronRight />
                        </Button>
                    </div>
                </div>

                <div
                    class="grid grid-cols-7 overflow-hidden rounded-xl border text-sm"
                >
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
                        class="border-b bg-muted/50 p-2 text-center text-xs font-medium text-muted-foreground"
                    >
                        {{ t(day) }}
                    </div>
                    <div
                        v-for="(cell, index) in cells"
                        :key="cell?.key ?? `blank-${index}`"
                        class="min-h-20 border-r border-b p-1.5 [&:nth-child(7n)]:border-r-0"
                        :class="{ 'bg-muted/30': !cell }"
                    >
                        <template v-if="cell">
                            <span
                                class="inline-flex size-6 items-center justify-center rounded-full text-xs"
                                :class="{
                                    'bg-primary font-semibold text-primary-foreground':
                                        cell.key === todayKey,
                                }"
                                >{{ cell.day }}</span
                            >
                            <Link
                                v-for="event in cell.events"
                                :key="event.id"
                                :href="eventsRoutes.show(event.slug)"
                                class="mt-1 block truncate rounded bg-accent px-1.5 py-0.5 text-xs font-medium text-accent-foreground hover:bg-primary hover:text-primary-foreground"
                                :title="event.title"
                                >{{ event.title }}</Link
                            >
                        </template>
                    </div>
                </div>
                <p
                    v-if="events.length === 0"
                    class="mt-3 text-sm text-muted-foreground"
                >
                    {{ t('No events listed for this month yet.') }}
                </p>
            </section>

            <aside>
                <h2 class="mb-3 text-xl font-semibold">{{ t('Coming up') }}</h2>
                <p
                    v-if="upcoming.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ t('No upcoming events yet.') }}
                </p>
                <ul class="space-y-3">
                    <li v-for="event in upcoming" :key="event.id">
                        <Link
                            :href="eventsRoutes.show(event.slug)"
                            class="block rounded-xl border p-4 hover:border-primary"
                        >
                            <p
                                class="flex items-center gap-1.5 text-sm text-primary"
                            >
                                <CalendarDays class="size-4" />
                                {{ formatDate(event.starts_at) }}
                                <span
                                    v-if="
                                        event.ends_at &&
                                        formatDate(event.ends_at) !==
                                            formatDate(event.starts_at)
                                    "
                                >
                                    – {{ formatDate(event.ends_at) }}
                                </span>
                            </p>
                            <p class="mt-1 font-semibold">{{ event.title }}</p>
                            <p
                                class="mt-1 flex items-center gap-1 text-sm text-muted-foreground"
                            >
                                <MapPin class="size-3.5" />
                                {{ event.venue?.name ?? event.venue_name }} ·
                                {{ formatTime(event.starts_at) }}
                            </p>
                        </Link>
                    </li>
                </ul>
            </aside>
        </div>
    </div>
</template>
