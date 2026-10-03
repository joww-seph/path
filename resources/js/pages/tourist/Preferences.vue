<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTrans } from '@/composables/useTrans';
import tourist from '@/routes/tourist';
import type { TouristProfile } from '@/types';

const props = defineProps<{
    profile: TouristProfile | null;
    interestOptions: string[];
    accessibilityOptions: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Travel preferences',
                href: tourist.preferences.edit(),
            },
        ],
    },
});

const { t } = useTrans();

const interestLabels: Record<string, string> = {
    heritage: 'Heritage and churches',
    nature: 'Lakes and nature',
    adventure: 'Dunes and adventure',
    food: 'Ilocano food',
    beach: 'Beaches',
    shopping: 'Pasalubong shopping',
    photography: 'Photography',
    faith: 'Pilgrimage and faith',
};

const accessibilityLabels: Record<string, string> = {
    wheelchair: 'Wheelchair user',
    limited_walking: 'Limited walking',
    visual: 'Low vision or blind',
    hearing: 'Hard of hearing or deaf',
    senior: 'Travelling with seniors',
    small_children: 'Travelling with small children',
};

const selectedInterests = new Set(props.profile?.interests ?? []);
const selectedNeeds = new Set(props.profile?.accessibility_needs ?? []);
</script>

<template>
    <Head :title="t('Travel preferences')" />

    <div class="max-w-2xl p-4 md:p-6">
        <Heading
            :title="t('Travel preferences')"
            :description="
                t(
                    'PaTH uses these to suggest places and build itineraries that suit you.',
                )
            "
        />

        <Form
            v-bind="tourist.preferences.update.form()"
            class="space-y-8"
            v-slot="{ errors, processing }"
        >
            <fieldset class="space-y-3">
                <legend class="text-sm font-medium">
                    {{ t('What do you enjoy?') }}
                </legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="option in interestOptions"
                        :key="option"
                        class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm has-checked:border-primary has-checked:bg-accent"
                    >
                        <input
                            type="checkbox"
                            name="interests[]"
                            :value="option"
                            :checked="selectedInterests.has(option)"
                            class="accent-primary"
                        />
                        {{ t(interestLabels[option] ?? option) }}
                    </label>
                </div>
                <InputError :message="errors.interests" />
            </fieldset>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="group_size">{{ t('Group size') }}</Label>
                    <Input
                        id="group_size"
                        name="group_size"
                        type="number"
                        min="1"
                        max="50"
                        required
                        :default-value="profile?.group_size ?? 1"
                    />
                    <InputError :message="errors.group_size" />
                </div>
                <div class="grid gap-2">
                    <Label for="budget_min">{{ t('Budget from (₱)') }}</Label>
                    <Input
                        id="budget_min"
                        name="budget_min"
                        type="number"
                        min="0"
                        step="100"
                        :default-value="profile?.budget_min ?? ''"
                    />
                    <InputError :message="errors.budget_min" />
                </div>
                <div class="grid gap-2">
                    <Label for="budget_max">{{ t('Budget up to (₱)') }}</Label>
                    <Input
                        id="budget_max"
                        name="budget_max"
                        type="number"
                        min="0"
                        step="100"
                        :default-value="profile?.budget_max ?? ''"
                    />
                    <InputError :message="errors.budget_max" />
                </div>
            </div>

            <fieldset class="space-y-3">
                <legend class="text-sm font-medium">
                    {{ t('Accessibility needs') }}
                </legend>
                <p class="text-sm text-muted-foreground">
                    {{
                        t(
                            'We use this to point out accessible places and warn about long walks or steps.',
                        )
                    }}
                </p>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="option in accessibilityOptions"
                        :key="option"
                        class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm has-checked:border-primary has-checked:bg-accent"
                    >
                        <input
                            type="checkbox"
                            name="accessibility_needs[]"
                            :value="option"
                            :checked="selectedNeeds.has(option)"
                            class="accent-primary"
                        />
                        {{ t(accessibilityLabels[option] ?? option) }}
                    </label>
                </div>
            </fieldset>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="home_province">{{
                        t('Where are you from?')
                    }}</Label>
                    <Input
                        id="home_province"
                        name="home_province"
                        :placeholder="t('Province or city')"
                        :default-value="profile?.home_province ?? ''"
                    />
                    <InputError :message="errors.home_province" />
                </div>
                <div class="grid gap-2">
                    <Label for="home_country">{{ t('Country code') }}</Label>
                    <Input
                        id="home_country"
                        name="home_country"
                        maxlength="2"
                        class="uppercase"
                        :default-value="profile?.home_country ?? 'PH'"
                    />
                    <InputError :message="errors.home_country" />
                </div>
            </div>

            <Button :disabled="processing">{{ t('Save preferences') }}</Button>
        </Form>
    </div>
</template>
