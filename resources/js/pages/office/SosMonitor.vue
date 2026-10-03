<script setup lang="ts">
import { Head, router, usePoll } from '@inertiajs/vue3';
import { MapPin, Phone, Siren } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import LeafletMap from '@/components/guide/LeafletMap.vue';
import type { MapMarker } from '@/components/guide/LeafletMap.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { formatDateTime } from '@/lib/format';
import office from '@/routes/office';

type Alert = {
    id: number;
    status: 'open' | 'acknowledged' | 'resolved';
    status_label: string;
    latitude: number | null;
    longitude: number | null;
    accuracy_meters: number | null;
    message: string | null;
    contacts_notified: number;
    office_notes: string | null;
    created_at: string;
    acknowledged_at: string | null;
    resolved_at: string | null;
    map_url: string | null;
    user: { id: number; name: string; phone: string | null; email: string };
    responder: string | null;
    trip: { id: number; title: string } | null;
};

const props = defineProps<{
    active: Alert[];
    resolved: Alert[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'SOS monitor', href: office.sos.index() }],
    },
});

const { t } = useTrans();

// Check for new alerts every 15 seconds, even if the tab is in the background.
usePoll(15000, { only: ['active', 'resolved'] }, { keepAlive: true });

const markers = computed<MapMarker[]>(() =>
    props.active
        .filter((alert) => alert.latitude !== null)
        .map((alert) => ({
            id: alert.id,
            lat: alert.latitude!,
            lng: alert.longitude!,
            title: alert.user.name,
            color: alert.status === 'open' ? '#dc2626' : '#d97706',
            label: '!',
        })),
);

function update(alert: Alert, status: 'acknowledged' | 'resolved') {
    const notes =
        status === 'resolved'
            ? prompt(
                  t('What happened? These notes stay with the alert.'),
                  alert.office_notes ?? '',
              )
            : alert.office_notes;

    if (status === 'resolved' && notes === null) {
        return;
    }

    router.put(
        office.sos.update.url(alert.id),
        { status, office_notes: notes },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="t('SOS monitor')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('SOS monitor')"
            :description="
                t(
                    'Alerts from tourists who pressed SOS. This page refreshes every 15 seconds.',
                )
            "
        />

        <div class="grid gap-6 xl:grid-cols-[1fr_28rem]">
            <section class="space-y-3">
                <p
                    v-if="active.length === 0"
                    class="rounded-xl border border-dashed p-8 text-center text-muted-foreground"
                >
                    {{ t('No open alerts.') }}
                </p>
                <article
                    v-for="alert in active"
                    :key="alert.id"
                    class="space-y-3 rounded-xl border-2 p-4"
                    :class="
                        alert.status === 'open'
                            ? 'border-red-400 bg-red-50/50 dark:border-red-800 dark:bg-red-950/40'
                            : 'border-amber-300'
                    "
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-2"
                    >
                        <div>
                            <p
                                class="flex items-center gap-2 text-lg font-semibold"
                            >
                                <Siren class="size-5 text-red-600" />
                                {{ alert.user.name }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ formatDateTime(alert.created_at)
                                }}<template v-if="alert.trip">
                                    · {{ alert.trip.title }}</template
                                >
                            </p>
                        </div>
                        <Badge
                            :variant="
                                alert.status === 'open'
                                    ? 'destructive'
                                    : 'secondary'
                            "
                            >{{ t(alert.status_label) }}</Badge
                        >
                    </div>
                    <p
                        v-if="alert.message"
                        class="rounded-md bg-background p-2"
                    >
                        “{{ alert.message }}”
                    </p>
                    <div class="flex flex-wrap gap-3 text-sm">
                        <a
                            v-if="alert.user.phone"
                            :href="`tel:${alert.user.phone}`"
                            class="flex items-center gap-1 font-medium text-primary"
                            ><Phone class="size-4" /> {{ alert.user.phone }}</a
                        >
                        <a
                            v-if="alert.map_url"
                            :href="alert.map_url"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center gap-1 font-medium text-primary"
                        >
                            <MapPin class="size-4" /> {{ t('Open location')
                            }}<template v-if="alert.accuracy_meters">
                                (±{{ alert.accuracy_meters }} m)</template
                            >
                        </a>
                        <span v-else class="text-muted-foreground">{{
                            t('Location not shared')
                        }}</span>
                        <span class="text-muted-foreground">{{
                            t(':count contacts alerted', {
                                count: alert.contacts_notified,
                            })
                        }}</span>
                    </div>
                    <p
                        v-if="alert.responder"
                        class="text-xs text-muted-foreground"
                    >
                        {{ t('Responding: :name', { name: alert.responder }) }}
                    </p>
                    <div class="flex gap-2">
                        <Button
                            v-if="alert.status === 'open'"
                            size="sm"
                            @click="update(alert, 'acknowledged')"
                            >{{ t('I am responding') }}</Button
                        >
                        <Button
                            size="sm"
                            variant="outline"
                            @click="update(alert, 'resolved')"
                            >{{ t('Mark resolved') }}</Button
                        >
                    </div>
                </article>

                <details v-if="resolved.length" class="rounded-xl border p-4">
                    <summary class="cursor-pointer font-medium">
                        {{ t('Recently resolved') }}
                    </summary>
                    <ul class="mt-3 divide-y text-sm">
                        <li
                            v-for="alert in resolved"
                            :key="alert.id"
                            class="py-2"
                        >
                            <span class="font-medium">{{
                                alert.user.name
                            }}</span>
                            · {{ formatDateTime(alert.created_at) }}
                            <span
                                v-if="alert.office_notes"
                                class="block text-muted-foreground"
                                >{{ alert.office_notes }}</span
                            >
                        </li>
                    </ul>
                </details>
            </section>

            <div
                class="h-96 overflow-hidden rounded-xl border xl:sticky xl:top-4"
            >
                <LeafletMap
                    :center="{ lat: 18.0617, lng: 120.5222 }"
                    :zoom="12"
                    :markers="markers"
                />
            </div>
        </div>
    </div>
</template>
