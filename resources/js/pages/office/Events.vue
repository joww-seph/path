<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useTrans } from '@/composables/useTrans';
import { formatDateTime } from '@/lib/format';
import eventsRoutes from '@/routes/events';
import office from '@/routes/office';
import type { Paginated, PaoayEvent } from '@/types';

defineProps<{
    events: Paginated<PaoayEvent>;
    venues: { id: number; name: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Events', href: office.events.index() }],
    },
});

const { t } = useTrans();
const editing = ref<PaoayEvent | null>(null);

/**
 * datetime-local inputs want "YYYY-MM-DDTHH:MM" in Manila time.
 */
function toLocalInput(value: string | null | undefined) {
    if (!value) {
        return '';
    }

    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(new Date(value));
    const get = (type: string) =>
        parts.find((part) => part.type === type)?.value;

    return `${get('year')}-${get('month')}-${get('day')}T${get('hour')}:${get('minute')}`;
}

function remove(event: PaoayEvent) {
    if (
        confirm(t('Delete ":title" from the calendar?', { title: event.title }))
    ) {
        router.delete(office.events.destroy.url(event.slug), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head :title="t('Events')" />

    <div class="grid flex-1 gap-6 p-4 md:p-6 xl:grid-cols-[1fr_26rem]">
        <div class="space-y-6">
            <Heading
                :title="t('Events calendar')"
                :description="
                    t('Festivals, feast days and activities shown to tourists.')
                "
            />

            <ul class="divide-y rounded-xl border">
                <li
                    v-if="events.data.length === 0"
                    class="p-6 text-center text-muted-foreground"
                >
                    {{ t('No events yet.') }}
                </li>
                <li
                    v-for="event in events.data"
                    :key="event.id"
                    class="flex items-center gap-3 p-4"
                >
                    <div class="min-w-0 flex-1">
                        <Link
                            :href="eventsRoutes.show(event.slug)"
                            class="font-medium hover:text-primary"
                            >{{ event.title }}</Link
                        >
                        <p class="text-sm text-muted-foreground">
                            {{ formatDateTime(event.starts_at) }} ·
                            {{ event.venue?.name ?? event.venue_name }}
                        </p>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="t('Edit')"
                        @click="editing = event"
                        ><Pencil
                    /></Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="t('Delete')"
                        @click="remove(event)"
                        ><Trash2
                    /></Button>
                </li>
            </ul>
            <Pagination :paginator="events" />
        </div>

        <Form
            :key="editing?.id ?? 'new'"
            v-bind="
                editing
                    ? office.events.update.form(editing.slug)
                    : office.events.store.form()
            "
            :options="{ preserveScroll: true }"
            reset-on-success
            class="space-y-4 self-start rounded-xl border p-4"
            v-slot="{ errors, processing }"
            @success="editing = null"
        >
            <h2 class="font-semibold">
                {{ editing ? t('Edit event') : t('Add an event') }}
            </h2>
            <div class="grid gap-1.5">
                <Label for="event-title">{{ t('Title') }}</Label>
                <Input
                    id="event-title"
                    name="title"
                    required
                    :default-value="editing?.title"
                />
                <InputError :message="errors.title" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-1.5">
                    <Label for="event-starts">{{ t('Starts') }}</Label>
                    <Input
                        id="event-starts"
                        name="starts_at"
                        type="datetime-local"
                        required
                        :default-value="toLocalInput(editing?.starts_at)"
                    />
                    <InputError :message="errors.starts_at" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="event-ends">{{ t('Ends') }}</Label>
                    <Input
                        id="event-ends"
                        name="ends_at"
                        type="datetime-local"
                        :default-value="toLocalInput(editing?.ends_at)"
                    />
                    <InputError :message="errors.ends_at" />
                </div>
            </div>
            <div class="grid gap-1.5">
                <Label for="event-venue">{{ t('Venue') }}</Label>
                <NativeSelect
                    id="event-venue"
                    name="venue_listing_id"
                    :default-value="editing?.venue_listing_id ?? ''"
                >
                    <option value="">
                        {{ t('Somewhere else (type below)') }}
                    </option>
                    <option
                        v-for="venue in venues"
                        :key="venue.id"
                        :value="venue.id"
                    >
                        {{ venue.name }}
                    </option>
                </NativeSelect>
            </div>
            <div class="grid gap-1.5">
                <Label for="event-venue-name">{{ t('Venue name') }}</Label>
                <Input
                    id="event-venue-name"
                    name="venue_name"
                    :placeholder="t('e.g. Town plaza')"
                    :default-value="editing?.venue_name ?? ''"
                />
                <InputError :message="errors.venue_name" />
            </div>
            <div class="grid gap-1.5">
                <Label for="event-description">{{ t('Description') }}</Label>
                <Textarea
                    id="event-description"
                    name="description"
                    rows="5"
                    :default-value="editing?.description ?? ''"
                />
                <InputError :message="errors.description" />
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_featured" value="0" />
                <input
                    type="checkbox"
                    name="is_featured"
                    value="1"
                    class="accent-primary"
                    :checked="editing?.is_featured ?? false"
                />
                {{ t('Feature on the events page') }}
            </label>
            <div class="flex gap-2">
                <Button :disabled="processing">{{
                    editing ? t('Save event') : t('Add event')
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
    </div>
</template>
