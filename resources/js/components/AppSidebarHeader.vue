<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import InstallAppButton from '@/components/InstallAppButton.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useTrans } from '@/composables/useTrans';
import notificationsRoutes from '@/routes/notifications';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const { t } = useTrans();
const unread = computed(() => Number(page.props.auth.unreadNotifications ?? 0));
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <InstallAppButton />
            <Link
                :href="notificationsRoutes.index()"
                class="relative flex size-9 items-center justify-center rounded-md hover:bg-accent"
                :aria-label="
                    unread
                        ? t(':count unread notifications', { count: unread })
                        : t('Notifications')
                "
            >
                <Bell class="size-5" />
                <span
                    v-if="unread"
                    class="absolute top-1 right-1 flex min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-semibold text-white"
                    >{{ unread > 9 ? '9+' : unread }}</span
                >
            </Link>
        </div>
    </header>
</template>
