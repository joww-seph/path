<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import StatCard from '@/components/StatCard.vue';
import { useTrans } from '@/composables/useTrans';
import { formatDateTime } from '@/lib/format';
import admin from '@/routes/admin';
import type { ActivityLogEntry, Role } from '@/types';

defineProps<{
    usersByRole: { role: Role; label: string; total: number }[];
    deactivatedUsers: number;
    recentActivity: ActivityLogEntry[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: admin.dashboard() }],
    },
});

const { t } = useTrans();
</script>

<template>
    <Head :title="t('Administration')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('Administration')"
            :description="t('Accounts, roles and system activity.')"
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <StatCard
                v-for="row in usersByRole"
                :key="row.role"
                :label="t(row.label)"
                :value="row.total"
                :href="admin.users.index({ query: { role: row.role } })"
            />
            <StatCard
                :label="t('Deactivated accounts')"
                :value="deactivatedUsers"
                :href="admin.users.index({ query: { status: 'deactivated' } })"
            />
        </div>

        <section class="rounded-xl border">
            <header class="flex items-center justify-between border-b p-4">
                <h2 class="font-medium">{{ t('Recent activity') }}</h2>
                <Link
                    :href="admin.activity.index()"
                    class="text-sm text-primary underline-offset-4 hover:underline"
                    >{{ t('View all') }}</Link
                >
            </header>
            <p
                v-if="recentActivity.length === 0"
                class="p-4 text-sm text-muted-foreground"
            >
                {{ t('Nothing has happened yet.') }}
            </p>
            <ul v-else class="divide-y text-sm">
                <li
                    v-for="entry in recentActivity"
                    :key="entry.id"
                    class="flex flex-wrap justify-between gap-2 p-4"
                >
                    <span>
                        <span class="font-medium">{{
                            entry.user?.name ?? t('System')
                        }}</span>
                        · <code class="text-xs">{{ entry.action }}</code>
                    </span>
                    <span class="text-muted-foreground">{{
                        formatDateTime(entry.created_at)
                    }}</span>
                </li>
            </ul>
        </section>
    </div>
</template>
