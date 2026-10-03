<script setup lang="ts">
import { Form, Link, usePage } from '@inertiajs/vue3';
import { BadgeCheck } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTrans } from '@/composables/useTrans';
import { code, verify } from '@/routes/phone';

const page = usePage();
const { t } = useTrans();
const user = computed(() => page.props.auth.user);
const codeSent = ref(false);
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('Mobile number verification')"
            :description="
                t('We text booking updates and SOS alerts to this number.')
            "
        />

        <p
            v-if="user.phone_verified_at"
            class="flex items-center gap-2 text-sm text-green-700 dark:text-green-400"
        >
            <BadgeCheck class="size-4" />
            {{ t(':phone is verified.', { phone: user.phone ?? '' }) }}
        </p>

        <template v-else>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <span class="text-muted-foreground">{{
                    t(':phone is not verified yet.', {
                        phone: user.phone ?? '',
                    })
                }}</span>
                <Link
                    :href="code()"
                    as="button"
                    preserve-scroll
                    class="font-medium text-foreground underline underline-offset-4"
                    @success="codeSent = true"
                    >{{
                        codeSent ? t('Send a new code') : t('Send code')
                    }}</Link
                >
            </div>
            <InputError
                :message="page.props.errors?.phone as string | undefined"
            />

            <Form
                v-if="codeSent"
                v-bind="verify.form()"
                :options="{ preserveScroll: true }"
                reset-on-success
                class="flex flex-wrap items-end gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="code">{{ t('6-digit code') }}</Label>
                    <Input
                        id="code"
                        name="code"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        class="w-36 tracking-widest"
                        required
                    />
                    <InputError :message="errors.code" />
                </div>
                <Button :disabled="processing">{{ t('Verify') }}</Button>
            </Form>
        </template>
    </div>
</template>
