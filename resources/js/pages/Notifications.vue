<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { formatDateTime } from '@/lib/format';
import notificationsRoutes from '@/routes/notifications';
import type { Paginated } from '@/types';

type AppNotification = {
    id: string;
    data: { title: string; body?: string; url?: string; level?: string };
    read_at: string | null;
    created_at: string;
};

defineProps<{
    notifications: Paginated<AppNotification>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Notifications', href: notificationsRoutes.index() },
        ],
    },
});

const { t } = useTrans();

function readAll() {
    router.post(
        notificationsRoutes.readAll.url(),
        {},
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="t('Notifications')" />

    <div class="flex max-w-3xl flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                class="mb-0!"
                :title="t('Notifications')"
                :description="t('Bookings, budget alerts and advisories.')"
            />
            <Button variant="outline" size="sm" @click="readAll">{{
                t('Mark all as read')
            }}</Button>
        </div>

        <p
            v-if="notifications.data.length === 0"
            class="rounded-xl border border-dashed p-8 text-center text-muted-foreground"
        >
            <Bell class="mx-auto mb-2 size-6" />
            {{ t('You are all caught up.') }}
        </p>

        <ul v-else class="divide-y rounded-xl border">
            <li
                v-for="notification in notifications.data"
                :key="notification.id"
            >
                <Link
                    :href="notificationsRoutes.show(notification.id)"
                    class="flex gap-3 p-4 hover:bg-accent/50"
                >
                    <span
                        class="mt-1.5 size-2 shrink-0 rounded-full"
                        :class="
                            notification.read_at
                                ? 'bg-transparent'
                                : 'bg-primary'
                        "
                    />
                    <span class="min-w-0">
                        <span
                            class="block"
                            :class="{ 'font-semibold': !notification.read_at }"
                            >{{ notification.data.title }}</span
                        >
                        <span
                            v-if="notification.data.body"
                            class="block text-sm text-muted-foreground"
                            >{{ notification.data.body }}</span
                        >
                        <span class="block text-xs text-muted-foreground">{{
                            formatDateTime(notification.created_at)
                        }}</span>
                    </span>
                </Link>
            </li>
        </ul>

        <Pagination :paginator="notifications" />
    </div>
</template>
