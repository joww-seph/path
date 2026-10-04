<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTrans } from '@/composables/useTrans';
import type { Role } from '@/types';

type Guide = 'tourist' | 'partner' | 'office';

type Section = { title: string; steps: string[] };

const page = usePage();
const { t } = useTrans();

const guides: {
    value: Guide;
    label: string;
    intro: string;
    sections: Section[];
}[] = [
    {
        value: 'tourist',
        label: 'Tourists',
        intro: 'Plan your Paoay trip, book local services and stay safe, even without signal.',
        sections: [
            {
                title: 'Plan a trip',
                steps: [
                    'Go to My trips and create a trip, or start from a ready-made template.',
                    'Add places from Explore, the map, or the suggestions in your planner.',
                    'Drag stops to reorder them, or tap "Best order for this day" to cut travel time.',
                    'Check the warnings: closed days, tight schedules, weather and advisories.',
                    'Share the trip with companions, or send a view-only link.',
                ],
            },
            {
                title: 'Book and check in',
                steps: [
                    'Open a place and choose a rate, date and number of guests.',
                    'The business has 48 hours to accept. You get a notification either way.',
                    'Once confirmed, your QR voucher is in My bookings. Show it when you arrive.',
                    'Pay the business directly as shown on the voucher.',
                ],
            },
            {
                title: 'Track your budget',
                steps: [
                    'Open Budget on your trip and record what you spend.',
                    'Split an expense between travellers; PaTH shows who owes whom.',
                    'You are notified at 80% and 100% of your budget.',
                ],
            },
            {
                title: 'Use PaTH offline',
                steps: [
                    'Before leaving, open your trip and tap "Make available offline".',
                    'At the dunes or the lake without signal, open the app: your itinerary, vouchers, hotlines and contacts are saved.',
                    'Expenses you add offline sync when you are back online.',
                ],
            },
            {
                title: 'Stay safe',
                steps: [
                    'Add emergency contacts under Emergency contacts.',
                    'In an emergency, call 911 first. Then press SOS to text your contacts your location and alert the tourism office.',
                    'The Hotlines page lists police, rescue, hospital and tourism office numbers.',
                ],
            },
            {
                title: 'After your trip',
                steps: [
                    'Tick off stops as you visit them; they appear in your trip recap.',
                    'Rate the places you visited to help the next visitor.',
                ],
            },
        ],
    },
    {
        value: 'partner',
        label: 'Partners',
        intro: 'List your business, take bookings and check in guests.',
        sections: [
            {
                title: 'Get verified',
                steps: [
                    'Register as a partner with your business permit number.',
                    'The tourism office reviews your business. You can prepare listings while you wait.',
                ],
            },
            {
                title: 'Create a listing',
                steps: [
                    'Go to My listings and add your place, tour or service with its map pin and opening hours.',
                    'Add photos and rates, then submit it for approval.',
                    'Approved listings appear in Explore and on the map.',
                ],
            },
            {
                title: 'Manage bookings',
                steps: [
                    'Set how many bookings you can take per day under Availability; block dates you are closed.',
                    'Accept or decline requests within 48 hours, or they expire.',
                    'Add payment instructions (e.g. GCash) in your business profile; guests see them on their voucher.',
                ],
            },
            {
                title: 'Check in guests',
                steps: [
                    'Open Check in a guest and scan the QR voucher with your phone camera.',
                    'No camera? Type the booking code instead.',
                    'Check-ins count as visits in your reports and let guests leave a review.',
                ],
            },
            {
                title: 'Reviews and reports',
                steps: [
                    'Reply to reviews under Reviews. A short, kind reply goes a long way.',
                    'Reports show bookings and earnings per month, and your best-selling rates.',
                ],
            },
        ],
    },
    {
        value: 'office',
        label: 'Tourism office',
        intro: 'Keep the guide accurate, visitors safe and decisions informed.',
        sections: [
            {
                title: 'Verify partners and listings',
                steps: [
                    'Partner verification lists new businesses. Check the permit, then approve or reject with a reason.',
                    'Listings waiting for approval are marked under Listings. Approve, or send back with notes.',
                    'Add heritage stories and keep events up to date.',
                ],
            },
            {
                title: 'Post advisories',
                steps: [
                    'Use Advisories for closures, crowds and weather. Choose the severity and the affected places.',
                    'Tourists with trips on those dates are notified. Warnings and danger notices for the whole town show on every page.',
                ],
            },
            {
                title: 'Respond to SOS alerts',
                steps: [
                    'Keep the SOS monitor open during operating hours; it refreshes every 15 seconds.',
                    'Call the tourist, open their location, and mark "I am responding".',
                    'Add notes and mark the alert resolved when the tourist is safe.',
                ],
            },
            {
                title: 'Moderate reviews',
                steps: [
                    'Hide reviews that are abusive, spam or share personal information, with a short reason.',
                ],
            },
            {
                title: 'Read visitor analytics',
                steps: [
                    'Visitor analytics shows tourists in town per day, site visits, bookings, top sites and where visitors come from.',
                    'Choose a date range and download the CSV for reports.',
                ],
            },
        ],
    },
];

function defaultGuide(role: Role | undefined): Guide {
    const requested = new URLSearchParams(
        typeof window === 'undefined' ? '' : window.location.search,
    ).get('for');

    if (
        requested === 'tourist' ||
        requested === 'partner' ||
        requested === 'office'
    ) {
        return requested;
    }

    if (role === 'partner') {
        return 'partner';
    }

    return role === 'tourism_officer' || role === 'admin'
        ? 'office'
        : 'tourist';
}

const selected = ref<Guide>(defaultGuide(page.props.auth.user?.role));

const guide = computed(() =>
    guides.find((item) => item.value === selected.value)!,
);
</script>

<template>
    <Head :title="t('Help')">
        <meta
            name="description"
            content="How to use PaTH: Paoay Travel Hub as a tourist, partner business or tourism office staff."
        />
    </Head>

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 md:px-6">
        <header>
            <h1 class="font-display text-3xl">{{ t('How to use PaTH') }}</h1>
            <p class="mt-2 text-muted-foreground">
                {{ t('Short guides for each kind of user.') }}
            </p>
        </header>

        <div
            class="flex gap-1 overflow-x-auto rounded-lg bg-muted p-1"
            role="tablist"
            :aria-label="t('Guides')"
        >
            <button
                v-for="item in guides"
                :key="item.value"
                type="button"
                role="tab"
                class="flex-1 rounded-md px-3 py-2 text-sm font-medium whitespace-nowrap"
                :class="
                    selected === item.value
                        ? 'bg-background shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'
                "
                :aria-selected="selected === item.value"
                @click="selected = item.value"
            >
                {{ t(item.label) }}
            </button>
        </div>

        <section role="tabpanel" class="space-y-4">
            <p class="text-lg">{{ t(guide.intro) }}</p>

            <details
                v-for="(section, index) in guide.sections"
                :key="section.title"
                class="group rounded-xl border bg-card p-4"
                :open="index === 0"
            >
                <summary
                    class="cursor-pointer list-none font-semibold marker:hidden"
                >
                    <span class="flex items-center justify-between gap-2">
                        {{ t(section.title) }}
                        <span
                            class="text-muted-foreground transition-transform group-open:rotate-45"
                            aria-hidden="true"
                            >+</span
                        >
                    </span>
                </summary>
                <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm">
                    <li v-for="step in section.steps" :key="step">
                        {{ t(step) }}
                    </li>
                </ol>
            </details>
        </section>
    </div>
</template>
