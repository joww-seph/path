<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CheckCircle2, Circle } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTrans } from '@/composables/useTrans';
import { edit as editProfile } from '@/routes/profile';
import tourist from '@/routes/tourist';

const props = defineProps<{
    checklist: {
        preferences: boolean;
        emergencyContacts: boolean;
        phoneVerified: boolean;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: tourist.dashboard() }],
    },
});

const page = usePage();
const { t } = useTrans();

const steps = computed(() => [
    {
        done: props.checklist.preferences,
        title: t('Set your travel preferences'),
        description: t(
            'Interests, group size and budget help PaTH suggest the right places.',
        ),
        href: tourist.preferences.edit(),
    },
    {
        done: props.checklist.emergencyContacts,
        title: t('Add an emergency contact'),
        description: t(
            'They get your location if you press the SOS button during your trip.',
        ),
        href: tourist.emergencyContacts.index(),
    },
    {
        done: props.checklist.phoneVerified,
        title: t('Verify your mobile number'),
        description: t('Used for booking updates and SOS text messages.'),
        href: editProfile(),
    },
]);
</script>

<template>
    <Head :title="t('Dashboard')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="
                t('Welcome, :name!', {
                    name: page.props.auth.user.name.split(' ')[0],
                })
            "
            :description="t('Here is what to do before your trip to Paoay.')"
        />

        <Card>
            <CardHeader>
                <CardTitle>{{ t('Get ready for your trip') }}</CardTitle>
                <CardDescription>
                    {{ t('Finish these steps before you travel.') }}
                </CardDescription>
            </CardHeader>
            <CardContent>
                <ul class="divide-y">
                    <li v-for="step in steps" :key="step.title">
                        <Link
                            :href="step.href"
                            class="flex items-start gap-3 py-3 hover:text-primary"
                        >
                            <CheckCircle2
                                v-if="step.done"
                                class="mt-0.5 size-5 shrink-0 text-green-600"
                            />
                            <Circle
                                v-else
                                class="mt-0.5 size-5 shrink-0 text-muted-foreground"
                            />
                            <span>
                                <span
                                    class="block font-medium"
                                    :class="{
                                        'text-muted-foreground line-through':
                                            step.done,
                                    }"
                                    >{{ step.title }}</span
                                >
                                <span
                                    class="block text-sm text-muted-foreground"
                                    >{{ step.description }}</span
                                >
                            </span>
                        </Link>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
