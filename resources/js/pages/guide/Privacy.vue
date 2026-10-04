<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';
import { hotlines } from '@/routes';

defineProps<{ contactEmail: string | null }>();

const { t } = useTrans();

const collected = [
    {
        what: 'Account details: name, email address, mobile number and password (stored scrambled)',
        why: 'To sign you in, send booking updates and verify your number',
    },
    {
        what: 'Travel preferences: interests, group size, budget range, accessibility needs, home province or country',
        why: 'To suggest places and, without names, to show the tourism office where visitors come from',
    },
    {
        what: 'Trips, itineraries, expenses and the people you share them with',
        why: 'To run the trip planner and budget tracker',
    },
    {
        what: 'Bookings and check-ins',
        why: 'So the business can serve you and the tourism office can count visitors',
    },
    {
        what: 'Reviews you post',
        why: 'Shown publicly with your first name to help other visitors',
    },
    {
        what: 'Emergency contacts',
        why: 'Only used when you press SOS',
    },
    {
        what: 'Your location, only when you press SOS and allow it',
        why: 'Sent to your emergency contacts and the tourism office so they can find you',
    },
];
</script>

<template>
    <Head :title="t('Privacy notice')">
        <meta
            name="description"
            content="How PaTH: Paoay Travel Hub collects, uses and protects personal data under the Data Privacy Act of 2012 (RA 10173)."
        />
    </Head>

    <article class="mx-auto max-w-3xl space-y-6 px-4 py-8 md:px-6">
        <header>
            <h1 class="font-display text-3xl">{{ t('Privacy notice') }}</h1>
            <p class="mt-2 text-muted-foreground">
                PaTH: Paoay Travel Hub is run with the Municipal Tourism Office
                of Paoay, Ilocos Norte. We follow the Data Privacy Act of 2012
                (Republic Act No. 10173) and the rules of the National Privacy
                Commission.
            </p>
        </header>

        <section class="space-y-3">
            <h2 class="text-xl font-semibold">What we collect and why</h2>
            <div class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="p-3 font-medium">Data</th>
                            <th class="p-3 font-medium">Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in collected"
                            :key="row.what"
                            class="border-t align-top"
                        >
                            <td class="p-3">{{ row.what }}</td>
                            <td class="p-3 text-muted-foreground">
                                {{ row.why }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-2">
            <h2 class="text-xl font-semibold">Who sees it</h2>
            <ul class="list-disc space-y-1 pl-5">
                <li>
                    A business sees your name, contact details and booking
                    details only for bookings you make with them.
                </li>
                <li>
                    People you add to a trip see that trip, its expenses and the
                    names of the other travellers.
                </li>
                <li>
                    Tourism office staff see accounts, bookings and SOS alerts
                    to verify businesses, keep visitors safe and handle
                    complaints. Their reports use totals, not names.
                </li>
                <li>
                    Service providers that process data for us: our email and
                    SMS providers (to deliver messages) and map, routing and
                    weather services (which receive place coordinates, never
                    your name).
                </li>
                <li>We do not sell your data or use it for advertising.</li>
            </ul>
        </section>

        <section class="space-y-2">
            <h2 class="text-xl font-semibold">How long we keep it</h2>
            <p>
                We keep your account until you delete it. When you delete your
                account, your profile, trips, expenses, bookings, reviews,
                emergency contacts and SOS alerts are deleted. Visitor totals
                already counted in reports stay, without your name.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-xl font-semibold">Your rights</h2>
            <p>
                Under RA 10173 you may ask what data we hold, correct it,
                download a copy, object to its use, or have it deleted. You can
                do most of this yourself in <strong>Settings → Profile</strong>:
                edit your details, <strong>download your data</strong>, or
                <strong>delete your account</strong>. You may also file a
                complaint with the National Privacy Commission (privacy.gov.ph).
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-xl font-semibold">Security</h2>
            <p>
                Passwords are hashed, the site uses HTTPS, and staff access is
                limited by role and logged. Data saved for offline use stays on
                your phone and is removed when you clear the app's data.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-xl font-semibold">
                Contact our Data Protection Officer
            </h2>
            <p>
                Municipal Tourism Office, Paoay, Ilocos Norte. See the
                <Link :href="hotlines()" class="text-primary underline">
                    hotlines page</Link
                >
                for the office's number.<template v-if="contactEmail">
                    Email:
                    <a
                        :href="`mailto:${contactEmail}`"
                        class="text-primary underline"
                        >{{ contactEmail }}</a
                    >.</template
                >
            </p>
            <p class="text-sm text-muted-foreground">
                Last updated: October 2026.
            </p>
        </section>
    </article>
</template>
