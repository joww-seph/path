<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import PrivacyConsent from '@/components/PrivacyConsent.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { useTrans } from '@/composables/useTrans';
import { login, register } from '@/routes';
import partner from '@/routes/partner';
import type { Option } from '@/types';

defineProps<{
    businessTypes: Option[];
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Register your business',
        description:
            'Resorts, restaurants, tour and 4x4 operators, drivers and shops in Paoay can join PaTH. The tourism office verifies every partner.',
    },
});

const { t } = useTrans();
</script>

<template>
    <Head :title="t('Register your business')" />

    <Form
        v-bind="partner.register.store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <fieldset class="grid gap-4">
            <legend class="mb-2 text-sm font-semibold">
                {{ t('About you') }}
            </legend>
            <div class="grid gap-2">
                <Label for="name">{{ t('Your full name') }}</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    autocomplete="name"
                    v-focus
                />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="email">{{ t('Email address') }}</Label>
                <Input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                />
                <InputError :message="errors.email" />
            </div>
            <div class="grid gap-2">
                <Label for="phone">{{ t('Mobile number') }}</Label>
                <Input
                    id="phone"
                    name="phone"
                    type="tel"
                    required
                    autocomplete="tel"
                    placeholder="09XX XXX XXXX"
                />
                <InputError :message="errors.phone" />
            </div>
        </fieldset>

        <fieldset class="grid gap-4">
            <legend class="mb-2 text-sm font-semibold">
                {{ t('Your business') }}
            </legend>
            <div class="grid gap-2">
                <Label for="business_name">{{ t('Business name') }}</Label>
                <Input id="business_name" name="business_name" required />
                <InputError :message="errors.business_name" />
            </div>
            <div class="grid gap-2">
                <Label for="business_type">{{ t('Type of business') }}</Label>
                <NativeSelect id="business_type" name="business_type" required>
                    <option value="" disabled selected>
                        {{ t('Choose one') }}
                    </option>
                    <option
                        v-for="type in businessTypes"
                        :key="type.value"
                        :value="type.value"
                    >
                        {{ t(type.label) }}
                    </option>
                </NativeSelect>
                <InputError :message="errors.business_type" />
            </div>
            <div class="grid gap-2">
                <Label for="permit_no">{{
                    t("Mayor's or business permit number")
                }}</Label>
                <Input id="permit_no" name="permit_no" required />
                <InputError :message="errors.permit_no" />
            </div>
            <div class="grid gap-2">
                <Label for="address">{{ t('Address in Paoay') }}</Label>
                <Input
                    id="address"
                    name="address"
                    required
                    :placeholder="t('Street, barangay')"
                />
                <InputError :message="errors.address" />
            </div>
            <div class="grid gap-2">
                <Label for="contact_phone">{{
                    t('Business contact number (if different)')
                }}</Label>
                <Input id="contact_phone" name="contact_phone" type="tel" />
                <InputError :message="errors.contact_phone" />
            </div>
        </fieldset>

        <fieldset class="grid gap-4">
            <legend class="mb-2 text-sm font-semibold">
                {{ t('Password') }}
            </legend>
            <div class="grid gap-2">
                <Label for="password">{{ t('Password') }}</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>
            <div class="grid gap-2">
                <Label for="password_confirmation">{{
                    t('Confirm password')
                }}</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    :passwordrules="passwordRules"
                />
            </div>
        </fieldset>

        <PrivacyConsent :error="errors.privacy_consent" />

        <Button type="submit" class="w-full" :disabled="processing">
            <Spinner v-if="processing" />
            {{ t('Submit for verification') }}
        </Button>

        <div class="text-center text-sm text-muted-foreground">
            {{ t('Already a partner?') }}
            <TextLink :href="login()">{{ t('Log in') }}</TextLink>
            <span class="mx-1">·</span>
            <TextLink :href="register()">{{
                t('Sign up as a tourist')
            }}</TextLink>
        </div>
    </Form>
</template>
