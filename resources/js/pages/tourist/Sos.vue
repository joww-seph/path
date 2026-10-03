<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Phone, Siren } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTrans } from '@/composables/useTrans';
import { formatDateTime } from '@/lib/format';
import tourist from '@/routes/tourist';

defineProps<{
    contacts: {
        id: number;
        name: string;
        relationship: string | null;
        phone: string;
    }[];
    hotlines: {
        id: number;
        name: string;
        type: string;
        phone: string;
        description: string | null;
    }[];
    recentAlert: {
        id: number;
        status: string;
        contacts_notified: number;
        created_at: string;
    } | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'SOS', href: tourist.sos() }],
    },
});

const { t } = useTrans();
const message = ref('');
const sending = ref(false);
const status = ref<string | null>(null);

function send(position: GeolocationPosition | null) {
    router.post(
        tourist.sos.store.url(),
        {
            latitude: position?.coords.latitude ?? null,
            longitude: position?.coords.longitude ?? null,
            accuracy_meters: position
                ? Math.round(position.coords.accuracy)
                : null,
            message: message.value || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                sending.value = false;
                status.value = null;
            },
        },
    );
}

/**
 * Ask for the location (only now, when SOS is pressed), and send the alert either way.
 */
function raise() {
    if (
        !confirm(
            t('Send an SOS to your emergency contacts and the tourism office?'),
        )
    ) {
        return;
    }

    sending.value = true;
    status.value = t('Getting your location…');

    if (!('geolocation' in navigator)) {
        send(null);

        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => send(position),
        () => send(null),
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 30000 },
    );
}
</script>

<template>
    <Head :title="t('SOS')" />

    <div class="mx-auto flex w-full max-w-xl flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('Emergency help')"
            :description="
                t(
                    'If someone is hurt or in danger, call 911 first. The SOS button alerts your contacts and the tourism office with your location.',
                )
            "
        />

        <a
            href="tel:911"
            class="flex items-center justify-center gap-3 rounded-2xl bg-red-700 p-5 text-xl font-bold text-white shadow-lg hover:bg-red-800"
        >
            <Phone class="size-7" /> {{ t('Call 911') }}
        </a>

        <section
            class="space-y-3 rounded-2xl border-2 border-red-300 p-5 dark:border-red-800"
        >
            <div class="grid gap-1.5">
                <Label for="sos-message">{{
                    t('What is happening? (optional)')
                }}</Label>
                <Textarea
                    id="sos-message"
                    v-model="message"
                    rows="2"
                    maxlength="500"
                    :placeholder="
                        t('e.g. Lost near the dunes, phone battery low')
                    "
                />
            </div>
            <Button
                class="h-20 w-full bg-red-600 text-xl font-bold text-white hover:bg-red-700"
                :disabled="sending"
                @click="raise"
            >
                <Siren class="size-8" />
                {{ sending ? (status ?? t('Sending…')) : t('Send SOS') }}
            </Button>
            <p class="text-xs text-muted-foreground">
                {{
                    t(
                        'Your location is shared only when you press SOS and allow it. Contacts get a text message with a map link.',
                    )
                }}
            </p>
            <p v-if="recentAlert" class="text-sm">
                {{
                    t('Last SOS: :time, :count contacts alerted.', {
                        time: formatDateTime(recentAlert.created_at),
                        count: recentAlert.contacts_notified,
                    })
                }}
            </p>
        </section>

        <section class="rounded-xl border">
            <div class="flex items-center justify-between border-b p-3">
                <h2 class="font-semibold">
                    {{ t('Your emergency contacts') }}
                </h2>
                <Link
                    :href="tourist.emergencyContacts.index()"
                    class="text-sm text-primary hover:underline"
                    >{{ t('Manage') }}</Link
                >
            </div>
            <p
                v-if="contacts.length === 0"
                class="p-3 text-sm text-destructive"
            >
                {{
                    t(
                        'You have no emergency contacts. Add one so they are alerted when you press SOS.',
                    )
                }}
            </p>
            <a
                v-for="contact in contacts"
                :key="contact.id"
                :href="`tel:${contact.phone}`"
                class="flex items-center justify-between gap-2 border-b p-3 last:border-0 hover:bg-accent/50"
            >
                <span
                    >{{ contact.name }}
                    <span
                        v-if="contact.relationship"
                        class="text-muted-foreground"
                        >· {{ contact.relationship }}</span
                    ></span
                >
                <span class="flex items-center gap-1 font-semibold text-primary"
                    ><Phone class="size-4" /> {{ contact.phone }}</span
                >
            </a>
        </section>

        <section class="rounded-xl border">
            <h2 class="border-b p-3 font-semibold">{{ t('Hotlines') }}</h2>
            <a
                v-for="hotline in hotlines"
                :key="hotline.id"
                :href="`tel:${hotline.phone}`"
                class="flex items-center justify-between gap-2 border-b p-3 last:border-0 hover:bg-accent/50"
            >
                <span>
                    {{ hotline.name }}
                    <span
                        v-if="hotline.description"
                        class="block text-xs text-muted-foreground"
                        >{{ hotline.description }}</span
                    >
                </span>
                <span
                    class="flex shrink-0 items-center gap-1 font-semibold text-primary"
                    ><Phone class="size-4" /> {{ hotline.phone }}</span
                >
            </a>
        </section>
    </div>
</template>
