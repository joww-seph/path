<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import VerificationBadge from '@/components/VerificationBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTrans } from '@/composables/useTrans';
import partner from '@/routes/partner';
import type { Business } from '@/types';

defineProps<{
    business: Business | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: partner.dashboard() }],
    },
});

const { t } = useTrans();
</script>

<template>
    <Head :title="t('Partner dashboard')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="business?.name ?? t('Partner dashboard')"
            :description="t('Manage your business on PaTH.')"
        />

        <Card v-if="business" class="max-w-2xl">
            <CardHeader>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle>{{ t('Verification') }}</CardTitle>
                    <VerificationBadge :status="business.verification_status" />
                </div>
                <CardDescription
                    v-if="business.verification_status === 'pending'"
                >
                    {{
                        t(
                            'The Paoay Municipal Tourism Office is checking your business permit. You can prepare your profile while you wait; listings go live once you are verified.',
                        )
                    }}
                </CardDescription>
                <CardDescription
                    v-else-if="business.verification_status === 'approved'"
                >
                    {{
                        t(
                            'Your business is verified. Tourists can now find and book your listings.',
                        )
                    }}
                </CardDescription>
                <CardDescription v-else>
                    {{
                        t(
                            'Your application was not approved. Update your business profile and it will be reviewed again.',
                        )
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <p
                    v-if="business.verification_note"
                    class="rounded-md bg-muted p-3 text-sm"
                >
                    <span class="font-medium">{{
                        t('Note from the office:')
                    }}</span>
                    {{ business.verification_note }}
                </p>
                <Button as-child variant="outline">
                    <Link :href="partner.business.edit()">{{
                        t('Edit business profile')
                    }}</Link>
                </Button>
            </CardContent>
        </Card>
    </div>
</template>
