<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useTrans } from '@/composables/useTrans';
import { privacy } from '@/routes';

defineProps<{ error?: string }>();

const { t } = useTrans();
</script>

<template>
    <div class="grid gap-2">
        <Label for="privacy_consent" class="flex items-start gap-3 font-normal">
            <Checkbox
                id="privacy_consent"
                name="privacy_consent"
                required
                class="mt-0.5"
            />
            <span>
                {{ t('I have read the') }}
                <TextLink :href="privacy()" target="_blank">{{
                    t('privacy notice')
                }}</TextLink>
                {{
                    t(
                        'and agree that PaTH may use my personal data as it describes.',
                    )
                }}
            </span>
        </Label>
        <InputError :message="error" />
    </div>
</template>
