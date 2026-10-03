<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import type { FormComponentProps } from '@inertiajs/core';
import { computed, reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import LeafletMap from '@/components/guide/LeafletMap.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useTrans } from '@/composables/useTrans';
import type { Barangay, Category, LatLng, ListingDetail } from '@/types';

const props = defineProps<{
    action: { action: string; method: FormComponentProps['method'] };
    listing: ListingDetail | null;
    categories: Category[];
    barangays: Barangay[];
    center: LatLng;
    canFeature: boolean;
    submitLabel: string;
}>();

const { t } = useTrans();

const days = [
    ['mon', 'Monday'],
    ['tue', 'Tuesday'],
    ['wed', 'Wednesday'],
    ['thu', 'Thursday'],
    ['fri', 'Friday'],
    ['sat', 'Saturday'],
    ['sun', 'Sunday'],
] as const;

const hoursKnown = ref(
    props.listing ? props.listing.opening_hours !== null : true,
);

const hours = reactive(
    Object.fromEntries(
        days.map(([key]) => {
            const existing = props.listing?.opening_hours?.[key];
            const isNew =
                !props.listing || props.listing.opening_hours === null;

            return [
                key,
                {
                    closed: isNew ? false : !existing,
                    open: existing?.open ?? '08:00',
                    close: existing?.close ?? '17:00',
                },
            ];
        }),
    ) as Record<string, { closed: boolean; open: string; close: string }>,
);

const pin = ref<LatLng | null>(
    props.listing?.latitude != null && props.listing?.longitude != null
        ? { lat: props.listing.latitude, lng: props.listing.longitude }
        : null,
);

function copyMondayToAll() {
    for (const [key] of days) {
        hours[key] = { ...hours.mon };
    }
}

const selectedCategory = ref(
    props.listing?.category_id ?? props.categories[0]?.id,
);
const bookableSuggested = computed(() =>
    ['lodging', 'activity', 'tour', 'transport'].includes(
        props.categories.find(
            (category) => category.id === Number(selectedCategory.value),
        )?.slug ?? '',
    ),
);
</script>

<template>
    <Form
        v-bind="action"
        class="space-y-8"
        :options="{ preserveScroll: true }"
        v-slot="{ errors, processing }"
    >
        <section class="space-y-4">
            <h2 class="text-lg font-semibold">{{ t('Basic details') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="name">{{ t('Name') }}</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        :default-value="listing?.name"
                    />
                    <InputError :message="errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="category_id">{{ t('Category') }}</Label>
                    <NativeSelect
                        id="category_id"
                        v-model="selectedCategory"
                        name="category_id"
                    >
                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ t(category.name) }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.category_id" />
                </div>
                <div class="grid gap-2">
                    <Label for="visit_minutes">{{
                        t('Typical visit length (minutes)')
                    }}</Label>
                    <Input
                        id="visit_minutes"
                        name="visit_minutes"
                        type="number"
                        min="10"
                        max="1440"
                        step="5"
                        required
                        :default-value="listing?.visit_minutes ?? 60"
                    />
                    <InputError :message="errors.visit_minutes" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="summary">{{ t('Short summary') }}</Label>
                    <Input
                        id="summary"
                        name="summary"
                        maxlength="300"
                        :placeholder="
                            t(
                                'One sentence shown on cards and in search results',
                            )
                        "
                        :default-value="listing?.summary ?? ''"
                    />
                    <InputError :message="errors.summary" />
                </div>
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="description">{{ t('Description') }}</Label>
                    <Textarea
                        id="description"
                        name="description"
                        rows="6"
                        :default-value="listing?.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold">{{ t('Location') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="barangay">{{ t('Barangay') }}</Label>
                    <NativeSelect
                        id="barangay"
                        name="barangay"
                        :default-value="listing?.barangay_slug ?? ''"
                    >
                        <option value="">{{ t('Not set') }}</option>
                        <option
                            v-for="barangay in barangays"
                            :key="barangay.slug"
                            :value="barangay.slug"
                        >
                            {{ barangay.name }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.barangay" />
                </div>
                <div class="grid gap-2">
                    <Label for="address">{{ t('Address') }}</Label>
                    <Input
                        id="address"
                        name="address"
                        :default-value="listing?.address ?? ''"
                    />
                    <InputError :message="errors.address" />
                </div>
            </div>
            <div>
                <p class="mb-2 text-sm text-muted-foreground">
                    {{
                        t(
                            'Click the map to drop a pin where visitors should go. Drag it to adjust.',
                        )
                    }}
                </p>
                <div class="h-72 overflow-hidden rounded-xl border">
                    <LeafletMap
                        :center="pin ?? center"
                        :zoom="pin ? 15 : 13"
                        picker
                        v-model:pin="pin"
                        :fit-markers="false"
                    />
                </div>
                <input type="hidden" name="latitude" :value="pin?.lat ?? ''" />
                <input type="hidden" name="longitude" :value="pin?.lng ?? ''" />
                <div
                    class="mt-2 flex items-center gap-3 text-sm text-muted-foreground"
                >
                    <span v-if="pin">{{ pin.lat }}, {{ pin.lng }}</span>
                    <span v-else>{{ t('No pin yet.') }}</span>
                    <Button
                        v-if="pin"
                        type="button"
                        variant="link"
                        size="sm"
                        class="h-auto p-0"
                        @click="pin = null"
                    >
                        {{ t('Remove pin') }}
                    </Button>
                </div>
                <InputError :message="errors.latitude ?? errors.longitude" />
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-semibold">{{ t('Opening hours') }}</h2>
                <label class="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        class="accent-primary"
                        :checked="!hoursKnown"
                        @change="hoursKnown = !hoursKnown"
                    />
                    {{ t('Always open, or hours unknown') }}
                </label>
            </div>
            <input
                type="hidden"
                name="hours_known"
                :value="hoursKnown ? 1 : 0"
            />
            <div v-if="hoursKnown" class="space-y-2">
                <div
                    v-for="[key, label] in days"
                    :key="key"
                    class="grid grid-cols-[6rem_1fr] items-center gap-3 sm:grid-cols-[7rem_auto_1fr]"
                >
                    <span class="text-sm font-medium">{{ t(label) }}</span>
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="hours[key].closed"
                            type="checkbox"
                            class="accent-primary"
                        />
                        <input
                            type="hidden"
                            :name="`hours[${key}][closed]`"
                            :value="hours[key].closed ? 1 : 0"
                        />
                        {{ t('Closed') }}
                    </label>
                    <div
                        v-if="!hours[key].closed"
                        class="col-span-2 flex items-center gap-2 sm:col-span-1"
                    >
                        <Input
                            v-model="hours[key].open"
                            type="time"
                            :name="`hours[${key}][open]`"
                            class="w-32"
                            :aria-label="t('Opens')"
                        />
                        <span>–</span>
                        <Input
                            v-model="hours[key].close"
                            type="time"
                            :name="`hours[${key}][close]`"
                            class="w-32"
                            :aria-label="t('Closes')"
                        />
                    </div>
                    <InputError
                        class="col-span-full"
                        :message="errors[`hours.${key}`]"
                    />
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="copyMondayToAll"
                >
                    {{ t("Copy Monday's hours to every day") }}
                </Button>
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold">{{ t('Prices') }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="entrance_fee">{{
                        t('Entrance fee (₱)')
                    }}</Label>
                    <Input
                        id="entrance_fee"
                        name="entrance_fee"
                        type="number"
                        min="0"
                        step="0.01"
                        :placeholder="t('None')"
                        :default-value="listing?.entrance_fee ?? ''"
                    />
                    <InputError :message="errors.entrance_fee" />
                </div>
                <div class="grid gap-2">
                    <Label for="price_min">{{ t('Prices from (₱)') }}</Label>
                    <Input
                        id="price_min"
                        name="price_min"
                        type="number"
                        min="0"
                        step="0.01"
                        :default-value="listing?.price_min ?? ''"
                    />
                    <InputError :message="errors.price_min" />
                </div>
                <div class="grid gap-2">
                    <Label for="price_max">{{ t('Prices up to (₱)') }}</Label>
                    <Input
                        id="price_max"
                        name="price_max"
                        type="number"
                        min="0"
                        step="0.01"
                        :default-value="listing?.price_max ?? ''"
                    />
                    <InputError :message="errors.price_max" />
                </div>
            </div>
            <p class="text-sm text-muted-foreground">
                {{
                    t(
                        'Enter 0 as the entrance fee for places that are free to enter.',
                    )
                }}
            </p>
        </section>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold">{{ t('Contact') }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="contact_phone">{{ t('Phone') }}</Label>
                    <Input
                        id="contact_phone"
                        name="contact_phone"
                        type="tel"
                        :default-value="listing?.contact_phone ?? ''"
                    />
                    <InputError :message="errors.contact_phone" />
                </div>
                <div class="grid gap-2">
                    <Label for="contact_email">{{ t('Email') }}</Label>
                    <Input
                        id="contact_email"
                        name="contact_email"
                        type="email"
                        :default-value="listing?.contact_email ?? ''"
                    />
                    <InputError :message="errors.contact_email" />
                </div>
                <div class="grid gap-2">
                    <Label for="website">{{ t('Website') }}</Label>
                    <Input
                        id="website"
                        name="website"
                        type="url"
                        placeholder="https://"
                        :default-value="listing?.website ?? ''"
                    />
                    <InputError :message="errors.website" />
                </div>
                <div class="grid gap-2">
                    <Label for="facebook_url">{{ t('Facebook page') }}</Label>
                    <Input
                        id="facebook_url"
                        name="facebook_url"
                        type="url"
                        placeholder="https://facebook.com/…"
                        :default-value="listing?.facebook_url ?? ''"
                    />
                    <InputError :message="errors.facebook_url" />
                </div>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-semibold">{{ t('Options') }}</h2>
            <label class="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    name="is_accessible"
                    value="1"
                    class="accent-primary"
                    :checked="listing?.is_accessible ?? false"
                />
                {{ t('Wheelchair accessible (step-free entrance and paths)') }}
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    name="is_bookable"
                    value="1"
                    class="accent-primary"
                    :checked="listing?.is_bookable ?? bookableSuggested"
                />
                {{ t('Tourists can request bookings through PaTH') }}
            </label>
            <template v-if="canFeature">
                <label class="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        name="is_featured"
                        value="1"
                        class="accent-primary"
                        :checked="listing?.is_featured ?? false"
                    />
                    {{ t('Feature as a must-see on the explore page') }}
                </label>
            </template>
        </section>

        <div
            class="sticky bottom-0 -mx-4 flex gap-2 border-t bg-background/95 px-4 py-3 backdrop-blur md:-mx-6 md:px-6"
        >
            <Button :disabled="processing">{{ submitLabel }}</Button>
            <slot name="actions" />
        </div>
    </Form>
</template>
