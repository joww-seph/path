<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, FileText, Mail, Phone } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import VerificationBadge from '@/components/VerificationBadge.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { formatDate } from '@/lib/format';
import office from '@/routes/office';
import type { Business, Paginated, VerificationStatus } from '@/types';

type PartnerBusiness = Business & {
    type_label: string;
    listings_count: number;
    owner: { id: number; name: string; email: string; phone: string | null };
};

defineProps<{
    businesses: Paginated<PartnerBusiness>;
    status: VerificationStatus;
    counts: Partial<Record<VerificationStatus, number>>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Partner verification', href: office.partners.index() },
        ],
    },
});

const { t } = useTrans();

const tabs: { value: VerificationStatus; label: string }[] = [
    { value: 'pending', label: 'Awaiting verification' },
    { value: 'approved', label: 'Verified' },
    { value: 'rejected', label: 'Not approved' },
];

function approve(business: PartnerBusiness) {
    router.put(
        office.partners.update.url(business.id),
        { decision: 'approve' },
        { preserveScroll: true },
    );
}

function reject(business: PartnerBusiness) {
    const note = prompt(
        t('Why is :name not approved? The partner will see this note.', {
            name: business.name,
        }),
    );

    if (note) {
        router.put(
            office.partners.update.url(business.id),
            { decision: 'reject', note },
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head :title="t('Partner verification')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('Partner verification')"
            :description="
                t(
                    'Check each business permit before the partner can publish listings and take bookings.',
                )
            "
        />

        <nav class="flex gap-1 border-b" :aria-label="t('Verification status')">
            <Link
                v-for="tab in tabs"
                :key="tab.value"
                :href="office.partners.index({ query: { status: tab.value } })"
                class="-mb-px border-b-2 px-3 py-2 text-sm font-medium"
                :class="
                    status === tab.value
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground'
                "
            >
                {{ t(tab.label) }}
                <span class="ml-1 rounded-full bg-muted px-1.5 text-xs">{{
                    counts[tab.value] ?? 0
                }}</span>
            </Link>
        </nav>

        <p
            v-if="businesses.data.length === 0"
            class="rounded-xl border border-dashed p-8 text-center text-muted-foreground"
        >
            {{ t('Nothing here.') }}
        </p>

        <ul class="grid gap-4 lg:grid-cols-2">
            <li
                v-for="business in businesses.data"
                :key="business.id"
                class="flex flex-col gap-3 rounded-xl border p-4"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="flex items-center gap-2 font-semibold">
                            <Building2 class="size-4" /> {{ business.name }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ t(business.type_label) }} ·
                            {{ business.address }}
                        </p>
                    </div>
                    <VerificationBadge :status="business.verification_status" />
                </div>
                <dl class="grid gap-1 text-sm">
                    <div class="flex items-center gap-2">
                        <FileText class="size-4 text-muted-foreground" />
                        <dt class="sr-only">{{ t('Permit') }}</dt>
                        <dd>{{ business.permit_no ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <Phone class="size-4 text-muted-foreground" />
                        <dt class="sr-only">{{ t('Phone') }}</dt>
                        <dd>
                            {{
                                business.contact_phone ??
                                business.owner.phone ??
                                '—'
                            }}
                        </dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <Mail class="size-4 text-muted-foreground" />
                        <dt class="sr-only">{{ t('Owner') }}</dt>
                        <dd>
                            {{ business.owner.name }} ·
                            {{ business.owner.email }}
                        </dd>
                    </div>
                </dl>
                <p class="text-xs text-muted-foreground">
                    {{
                        t('Applied :date · :count listings', {
                            date: formatDate(business.created_at),
                            count: business.listings_count,
                        })
                    }}
                </p>
                <p
                    v-if="business.verification_note"
                    class="rounded-md bg-muted p-2 text-sm"
                >
                    {{ business.verification_note }}
                </p>
                <div class="mt-auto flex gap-2">
                    <Button
                        v-if="business.verification_status !== 'approved'"
                        size="sm"
                        @click="approve(business)"
                        >{{ t('Approve') }}</Button
                    >
                    <Button
                        v-if="business.verification_status !== 'rejected'"
                        size="sm"
                        variant="outline"
                        @click="reject(business)"
                    >
                        {{
                            business.verification_status === 'approved'
                                ? t('Revoke verification')
                                : t('Reject')
                        }}
                    </Button>
                </div>
            </li>
        </ul>

        <Pagination :paginator="businesses" />
    </div>
</template>
