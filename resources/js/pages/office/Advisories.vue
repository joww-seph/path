<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useTrans } from '@/composables/useTrans';
import { formatDateTime } from '@/lib/format';
import office from '@/routes/office';
import type { AdvisorySeverity, Option, Paginated } from '@/types';

type OfficeAdvisory = {
    id: number;
    title: string;
    body: string;
    severity: AdvisorySeverity;
    starts_at: string;
    ends_at: string | null;
    notified_at: string | null;
    listing_ids: number[];
    listings: string[];
};

defineProps<{
    advisories: Paginated<OfficeAdvisory>;
    listings: { id: number; name: string }[];
    severities: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Advisories', href: office.advisories.index() }],
    },
});

const { t } = useTrans();
const editing = ref<OfficeAdvisory | null>(null);

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

const isActive = (advisory: OfficeAdvisory) =>
    !advisory.ends_at || new Date(advisory.ends_at) >= new Date();

function remove(advisory: OfficeAdvisory) {
    if (
        confirm(t('Delete the advisory ":title"?', { title: advisory.title }))
    ) {
        router.delete(office.advisories.destroy.url(advisory.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head :title="t('Advisories')" />

    <div class="grid flex-1 gap-6 p-4 md:p-6 xl:grid-cols-[1fr_28rem]">
        <div class="space-y-6">
            <Heading
                :title="t('Advisories')"
                :description="
                    t(
                        'Closures, crowd and weather warnings. Tourists travelling on those dates are notified.',
                    )
                "
            />

            <ul class="space-y-3">
                <li
                    v-if="advisories.data.length === 0"
                    class="rounded-xl border border-dashed p-6 text-center text-muted-foreground"
                >
                    {{ t('No advisories yet.') }}
                </li>
                <li
                    v-for="advisory in advisories.data"
                    :key="advisory.id"
                    class="rounded-xl border p-4"
                    :class="{ 'opacity-60': !isActive(advisory) }"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-2"
                    >
                        <div>
                            <p class="font-semibold">{{ advisory.title }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ formatDateTime(advisory.starts_at) }} –
                                {{
                                    advisory.ends_at
                                        ? formatDateTime(advisory.ends_at)
                                        : t('until removed')
                                }}
                            </p>
                        </div>
                        <Badge
                            :variant="
                                advisory.severity === 'danger'
                                    ? 'destructive'
                                    : advisory.severity === 'warning'
                                      ? 'default'
                                      : 'secondary'
                            "
                        >
                            {{
                                t(
                                    severities.find(
                                        (option) =>
                                            option.value === advisory.severity,
                                    )?.label ?? advisory.severity,
                                )
                            }}
                        </Badge>
                    </div>
                    <p class="mt-2 text-sm">{{ advisory.body }}</p>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{
                            advisory.listings.length
                                ? t('Affects: :places', {
                                      places: advisory.listings.join(', '),
                                  })
                                : t('Applies to the whole town')
                        }}
                        <template v-if="advisory.notified_at">
                            · {{ t('Travellers notified') }}</template
                        >
                    </p>
                    <div class="mt-2 flex gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="t('Edit')"
                            @click="editing = advisory"
                            ><Pencil
                        /></Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="t('Delete')"
                            @click="remove(advisory)"
                            ><Trash2
                        /></Button>
                    </div>
                </li>
            </ul>
            <Pagination :paginator="advisories" />
        </div>

        <Form
            :key="editing?.id ?? 'new'"
            v-bind="
                editing
                    ? office.advisories.update.form(editing.id)
                    : office.advisories.store.form()
            "
            :options="{ preserveScroll: true }"
            reset-on-success
            class="space-y-4 self-start rounded-xl border p-4"
            v-slot="{ errors, processing }"
            @success="editing = null"
        >
            <h2 class="font-semibold">
                {{ editing ? t('Edit advisory') : t('Post an advisory') }}
            </h2>
            <div class="grid gap-1.5">
                <Label for="advisory-title">{{ t('Title') }}</Label>
                <Input
                    id="advisory-title"
                    name="title"
                    required
                    :default-value="editing?.title"
                    :placeholder="t('e.g. Heavy traffic near Paoay Church')"
                />
                <InputError :message="errors.title" />
            </div>
            <div class="grid gap-1.5">
                <Label for="advisory-body">{{ t('Message') }}</Label>
                <Textarea
                    id="advisory-body"
                    name="body"
                    rows="4"
                    required
                    :default-value="editing?.body"
                />
                <InputError :message="errors.body" />
            </div>
            <div class="grid gap-1.5">
                <Label for="advisory-severity">{{ t('Severity') }}</Label>
                <NativeSelect
                    id="advisory-severity"
                    name="severity"
                    :default-value="editing?.severity ?? 'warning'"
                >
                    <option
                        v-for="option in severities"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ t(option.label) }}
                    </option>
                </NativeSelect>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-1.5">
                    <Label for="advisory-starts">{{ t('From') }}</Label>
                    <Input
                        id="advisory-starts"
                        name="starts_at"
                        type="datetime-local"
                        required
                        :default-value="toLocalInput(editing?.starts_at)"
                    />
                    <InputError :message="errors.starts_at" />
                </div>
                <div class="grid gap-1.5">
                    <Label for="advisory-ends">{{ t('Until') }}</Label>
                    <Input
                        id="advisory-ends"
                        name="ends_at"
                        type="datetime-local"
                        :default-value="toLocalInput(editing?.ends_at)"
                    />
                    <InputError :message="errors.ends_at" />
                </div>
            </div>
            <fieldset class="space-y-1.5">
                <legend class="text-sm font-medium">
                    {{ t('Affected places (leave empty for the whole town)') }}
                </legend>
                <div
                    class="max-h-48 space-y-1 overflow-y-auto rounded-md border p-2"
                >
                    <label
                        v-for="listing in listings"
                        :key="listing.id"
                        class="flex items-center gap-2 text-sm"
                    >
                        <input
                            type="checkbox"
                            name="listing_ids[]"
                            :value="listing.id"
                            class="accent-primary"
                            :checked="
                                editing?.listing_ids.includes(listing.id) ??
                                false
                            "
                        />
                        {{ listing.name }}
                    </label>
                </div>
            </fieldset>
            <div class="flex gap-2">
                <Button :disabled="processing">{{
                    editing ? t('Save advisory') : t('Publish advisory')
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
