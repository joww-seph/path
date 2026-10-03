<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Phone } from '@lucide/vue';
import { useTrans } from '@/composables/useTrans';

defineProps<{
    hotlines: {
        id: number;
        name: string;
        phone: string;
        description: string | null;
        type: string;
        type_label: string;
    }[];
}>();

const { t } = useTrans();
</script>

<template>
    <Head :title="t('Emergency hotlines')">
        <meta
            name="description"
            content="Emergency, rescue, hospital and tourism office hotlines for Paoay, Ilocos Norte."
        />
    </Head>

    <div class="mx-auto max-w-2xl px-4 py-8 md:px-6">
        <h1 class="font-display text-3xl">{{ t('Emergency hotlines') }}</h1>
        <p class="mt-2 text-muted-foreground">
            {{
                t(
                    'Tap a number to call. Save your trip offline to keep these on your phone without signal.',
                )
            }}
        </p>

        <a
            href="tel:911"
            class="mt-6 flex items-center justify-center gap-3 rounded-2xl bg-red-700 p-5 text-xl font-bold text-white hover:bg-red-800"
        >
            <Phone class="size-6" /> {{ t('Call 911') }}
        </a>

        <ul class="mt-6 divide-y rounded-xl border">
            <li v-for="hotline in hotlines" :key="hotline.id">
                <a
                    :href="`tel:${hotline.phone}`"
                    class="flex items-center justify-between gap-3 p-4 hover:bg-accent/50"
                >
                    <span>
                        <span class="block font-medium">{{
                            hotline.name
                        }}</span>
                        <span class="block text-sm text-muted-foreground"
                            >{{ t(hotline.type_label)
                            }}<template v-if="hotline.description">
                                · {{ hotline.description }}</template
                            ></span
                        >
                    </span>
                    <span
                        class="flex shrink-0 items-center gap-1 text-lg font-semibold text-primary"
                        ><Phone class="size-4" /> {{ hotline.phone }}</span
                    >
                </a>
            </li>
        </ul>
    </div>
</template>
