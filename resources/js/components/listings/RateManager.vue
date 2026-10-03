<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTrans } from '@/composables/useTrans';
import { formatPeso } from '@/lib/format';
import listingsRoutes from '@/routes/listings';
import type { ListingRate, Option } from '@/types';

const props = defineProps<{
    listingSlug: string;
    rates: ListingRate[];
    rateUnits: Option[];
}>();

const { t } = useTrans();
const editing = ref<ListingRate | null>(null);

function remove(rate: ListingRate) {
    if (confirm(t('Delete the rate ":name"?', { name: rate.name }))) {
        router.delete(
            listingsRoutes.rates.destroy.url({
                listing: props.listingSlug,
                rate: rate.id,
            }),
            {
                preserveScroll: true,
            },
        );
    }
}
</script>

<template>
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold">{{ t('Rates') }}</h2>
            <p class="text-sm text-muted-foreground">
                {{
                    t(
                        'The prices tourists choose from when they book, such as a room type or a 4x4 package.',
                    )
                }}
            </p>
        </div>

        <ul v-if="rates.length" class="divide-y rounded-lg border">
            <li
                v-for="rate in rates"
                :key="rate.id"
                class="flex items-center gap-3 p-3"
                :class="{ 'opacity-50': !rate.is_active }"
            >
                <div class="min-w-0 flex-1">
                    <p class="font-medium">{{ rate.name }}</p>
                    <p class="text-sm text-muted-foreground">
                        {{ formatPeso(rate.price) }} {{ t(rate.unit_label) }}
                        <span v-if="rate.capacity">
                            ·
                            {{
                                t('up to :count people', {
                                    count: rate.capacity,
                                })
                            }}</span
                        >
                        <span v-if="!rate.is_active"> · {{ t('hidden') }}</span>
                    </p>
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :aria-label="t('Edit')"
                    @click="editing = rate"
                >
                    <Pencil />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :aria-label="t('Delete')"
                    @click="remove(rate)"
                >
                    <Trash2 />
                </Button>
            </li>
        </ul>

        <Form
            :key="editing?.id ?? 'new'"
            v-bind="
                editing
                    ? listingsRoutes.rates.update.form({
                          listing: listingSlug,
                          rate: editing.id,
                      })
                    : listingsRoutes.rates.store.form(listingSlug)
            "
            :options="{ preserveScroll: true }"
            reset-on-success
            class="grid gap-3 rounded-lg border p-4 sm:grid-cols-6"
            v-slot="{ errors, processing }"
            @success="editing = null"
        >
            <p class="font-medium sm:col-span-6">
                {{ editing ? t('Edit rate') : t('Add a rate') }}
            </p>
            <div class="grid gap-1.5 sm:col-span-3">
                <Label for="rate-name">{{ t('Name') }}</Label>
                <Input
                    id="rate-name"
                    name="name"
                    required
                    :placeholder="t('e.g. 4x4 ride, up to 5 people')"
                    :default-value="editing?.name"
                />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-1.5 sm:col-span-1">
                <Label for="rate-price">{{ t('Price (₱)') }}</Label>
                <Input
                    id="rate-price"
                    name="price"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                    :default-value="editing?.price"
                />
                <InputError :message="errors.price" />
            </div>
            <div class="grid gap-1.5 sm:col-span-1">
                <Label for="rate-unit">{{ t('Charged') }}</Label>
                <NativeSelect
                    id="rate-unit"
                    name="unit"
                    :default-value="editing?.unit ?? 'person'"
                >
                    <option
                        v-for="unit in rateUnits"
                        :key="unit.value"
                        :value="unit.value"
                    >
                        {{ t(unit.label) }}
                    </option>
                </NativeSelect>
            </div>
            <div class="grid gap-1.5 sm:col-span-1">
                <Label for="rate-capacity">{{ t('Max people') }}</Label>
                <Input
                    id="rate-capacity"
                    name="capacity"
                    type="number"
                    min="1"
                    :default-value="editing?.capacity ?? ''"
                />
                <InputError :message="errors.capacity" />
            </div>
            <div class="grid gap-1.5 sm:col-span-4">
                <Label for="rate-description">{{
                    t('Description (optional)')
                }}</Label>
                <Input
                    id="rate-description"
                    name="description"
                    :default-value="editing?.description ?? ''"
                />
            </div>
            <label
                class="flex items-center gap-2 self-end pb-2 text-sm sm:col-span-2"
            >
                <input type="hidden" name="is_active" value="0" />
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="accent-primary"
                    :checked="editing?.is_active ?? true"
                />
                {{ t('Show to tourists') }}
            </label>
            <div class="flex gap-2 sm:col-span-6">
                <Button :disabled="processing" variant="secondary">{{
                    editing ? t('Save rate') : t('Add rate')
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
    </section>
</template>
