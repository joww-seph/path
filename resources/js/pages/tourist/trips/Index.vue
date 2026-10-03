<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { CalendarDays, MapPin, Plus, Users } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTrans } from '@/composables/useTrans';
import { formatDate } from '@/lib/format';
import tourist from '@/routes/tourist';
import type { ItineraryTemplate, Option, Trip } from '@/types';

const props = defineProps<{
    trips: Trip[];
    templates: ItineraryTemplate[];
    travelModes: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'My trips', href: tourist.trips.index() }],
    },
});

const { t } = useTrans();
const creating = ref(props.trips.length === 0);
const template = ref('');

const today = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Manila',
}).format(new Date());
const startDate = ref(today);
const endDate = ref(today);

function chooseTemplate(slug: string) {
    template.value = template.value === slug ? '' : slug;
    const chosen = props.templates.find((item) => item.slug === slug);

    if (chosen && template.value) {
        const end = new Date(`${startDate.value}T00:00:00`);
        end.setDate(end.getDate() + chosen.days - 1);
        endDate.value = new Intl.DateTimeFormat('en-CA').format(end);
    }

    creating.value = true;
}

const isPast = (trip: Trip) => trip.end_date < today;
</script>

<template>
    <Head :title="t('My trips')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="t('My trips')"
                :description="
                    t(
                        'Plan day by day, see travel times and share with your companions.',
                    )
                "
            />
            <Button v-if="!creating" @click="creating = true"
                ><Plus /> {{ t('New trip') }}</Button
            >
        </div>

        <section v-if="templates.length" class="space-y-3">
            <h2
                class="text-sm font-semibold tracking-wide text-muted-foreground uppercase"
            >
                {{ t('Start from a ready-made plan') }}
            </h2>
            <div class="grid gap-3 md:grid-cols-2">
                <button
                    v-for="item in templates"
                    :key="item.slug"
                    type="button"
                    class="rounded-xl border p-4 text-left transition-colors hover:border-primary"
                    :class="{
                        'border-primary bg-accent': template === item.slug,
                    }"
                    :aria-pressed="template === item.slug"
                    @click="chooseTemplate(item.slug)"
                >
                    <p class="font-semibold">{{ t(item.name) }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ t(item.summary ?? '') }}
                    </p>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{
                            t(':days days · :stops stops', {
                                days: item.days,
                                stops: item.items_count,
                            })
                        }}
                    </p>
                </button>
            </div>
        </section>

        <Form
            v-if="creating"
            v-bind="tourist.trips.store.form()"
            class="grid gap-4 rounded-xl border p-4 md:grid-cols-6"
            v-slot="{ errors, processing }"
        >
            <h2 class="font-semibold md:col-span-6">{{ t('New trip') }}</h2>
            <input type="hidden" name="template" :value="template" />
            <div class="grid gap-1.5 md:col-span-3">
                <Label for="title">{{ t('Trip name') }}</Label>
                <Input
                    id="title"
                    name="title"
                    required
                    :default-value="t('My Paoay trip')"
                />
                <InputError :message="errors.title" />
            </div>
            <div class="grid gap-1.5 md:col-span-1">
                <Label for="pax">{{ t('Travellers') }}</Label>
                <Input
                    id="pax"
                    name="pax"
                    type="number"
                    min="1"
                    max="50"
                    required
                    default-value="2"
                />
                <InputError :message="errors.pax" />
            </div>
            <div class="grid gap-1.5 md:col-span-2">
                <Label for="budget">{{ t('Budget (₱, optional)') }}</Label>
                <Input
                    id="budget"
                    name="budget"
                    type="number"
                    min="0"
                    step="100"
                />
                <InputError :message="errors.budget" />
            </div>
            <div class="grid gap-1.5 md:col-span-2">
                <Label for="start_date">{{ t('First day') }}</Label>
                <Input
                    id="start_date"
                    v-model="startDate"
                    name="start_date"
                    type="date"
                    :min="today"
                    required
                />
                <InputError :message="errors.start_date" />
            </div>
            <div class="grid gap-1.5 md:col-span-2">
                <Label for="end_date">{{ t('Last day') }}</Label>
                <Input
                    id="end_date"
                    v-model="endDate"
                    name="end_date"
                    type="date"
                    :min="startDate"
                    required
                />
                <InputError :message="errors.end_date" />
            </div>
            <div class="grid gap-1.5 md:col-span-2">
                <Label for="travel_mode">{{ t('Getting around by') }}</Label>
                <NativeSelect
                    id="travel_mode"
                    name="travel_mode"
                    default-value="car"
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
            <div class="flex gap-2 md:col-span-6">
                <Button :disabled="processing">
                    {{
                        template ? t('Create trip from plan') : t('Create trip')
                    }}
                </Button>
                <Button
                    v-if="trips.length"
                    type="button"
                    variant="ghost"
                    @click="creating = false"
                    >{{ t('Cancel') }}</Button
                >
            </div>
        </Form>

        <section
            v-if="trips.length"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <Link
                v-for="trip in trips"
                :key="trip.id"
                :href="tourist.trips.show(trip.id)"
                class="flex flex-col gap-2 rounded-xl border p-4 transition-colors hover:border-primary"
                :class="{ 'opacity-60': isPast(trip) }"
            >
                <div class="flex items-start justify-between gap-2">
                    <p class="font-semibold">{{ trip.title }}</p>
                    <span
                        v-if="isPast(trip)"
                        class="text-xs text-muted-foreground"
                        >{{ t('Past trip') }}</span
                    >
                </div>
                <p
                    class="flex items-center gap-1.5 text-sm text-muted-foreground"
                >
                    <CalendarDays class="size-4" />
                    {{ formatDate(trip.start_date) }}
                    <template v-if="trip.day_count > 1">
                        – {{ formatDate(trip.end_date) }}</template
                    >
                </p>
                <p
                    class="flex items-center gap-3 text-sm text-muted-foreground"
                >
                    <span class="flex items-center gap-1"
                        ><MapPin class="size-4" />
                        {{
                            t(':count stops', { count: trip.items_count ?? 0 })
                        }}</span
                    >
                    <span class="flex items-center gap-1"
                        ><Users class="size-4" /> {{ trip.pax }}</span
                    >
                </p>
                <p
                    v-if="trip.role !== 'owner'"
                    class="text-xs text-muted-foreground"
                >
                    {{ t('Shared by :name', { name: trip.owner?.name ?? '' }) }}
                </p>
            </Link>
        </section>
    </div>
</template>
