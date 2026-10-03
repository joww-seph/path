<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import VerificationBadge from '@/components/VerificationBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { useTrans } from '@/composables/useTrans';
import partner from '@/routes/partner';
import type { Business, Option } from '@/types';

defineProps<{
    business: Business;
    businessTypes: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Business profile', href: partner.business.edit() },
        ],
    },
});

const { t } = useTrans();
</script>

<template>
    <Head :title="t('Business profile')" />

    <div class="max-w-2xl p-4 md:p-6">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="t('Business profile')"
                :description="
                    t('Tourists and the tourism office see these details.')
                "
            />
            <VerificationBadge :status="business.verification_status" />
        </div>

        <Form
            v-bind="partner.business.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">{{ t('Business name') }}</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    :default-value="business.name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="type">{{ t('Type of business') }}</Label>
                    <NativeSelect
                        id="type"
                        name="type"
                        :default-value="business.type"
                    >
                        <option
                            v-for="type in businessTypes"
                            :key="type.value"
                            :value="type.value"
                        >
                            {{ t(type.label) }}
                        </option>
                    </NativeSelect>
                    <InputError :message="errors.type" />
                </div>
                <div class="grid gap-2">
                    <Label for="permit_no">{{
                        t('Business permit number')
                    }}</Label>
                    <Input
                        id="permit_no"
                        name="permit_no"
                        required
                        :default-value="business.permit_no ?? ''"
                    />
                    <InputError :message="errors.permit_no" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="contact_phone">{{ t('Contact number') }}</Label>
                    <Input
                        id="contact_phone"
                        name="contact_phone"
                        type="tel"
                        :default-value="business.contact_phone ?? ''"
                    />
                    <InputError :message="errors.contact_phone" />
                </div>
                <div class="grid gap-2">
                    <Label for="contact_email">{{ t('Contact email') }}</Label>
                    <Input
                        id="contact_email"
                        name="contact_email"
                        type="email"
                        :default-value="business.contact_email ?? ''"
                    />
                    <InputError :message="errors.contact_email" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="address">{{ t('Address') }}</Label>
                <Input
                    id="address"
                    name="address"
                    required
                    :default-value="business.address ?? ''"
                />
                <InputError :message="errors.address" />
            </div>

            <div class="grid gap-2">
                <Label for="description">{{ t('About the business') }}</Label>
                <Textarea
                    id="description"
                    name="description"
                    rows="4"
                    :default-value="business.description ?? ''"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-2">
                <Label for="payment_instructions">{{
                    t('Payment instructions')
                }}</Label>
                <Textarea
                    id="payment_instructions"
                    name="payment_instructions"
                    rows="3"
                    :placeholder="
                        t(
                            'e.g. Pay on arrival, or GCash 09XX XXX XXXX (Juan Dela Cruz)',
                        )
                    "
                    :default-value="business.payment_instructions ?? ''"
                />
                <p class="text-sm text-muted-foreground">
                    {{
                        t(
                            'PaTH does not take payments. Tourists see this after you confirm a booking.',
                        )
                    }}
                </p>
                <InputError :message="errors.payment_instructions" />
            </div>

            <Button :disabled="processing">{{ t('Save') }}</Button>
        </Form>
    </div>
</template>
