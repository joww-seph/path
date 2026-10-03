<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTrans } from '@/composables/useTrans';
import { formatDateTime } from '@/lib/format';
import admin from '@/routes/admin';
import type { ActivityLogEntry, Paginated } from '@/types';

const props = defineProps<{
    entries: Paginated<ActivityLogEntry>;
    filters: { action?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Activity log', href: admin.activity.index() }],
    },
});

const { t } = useTrans();
const action = ref(props.filters.action ?? '');

function filter() {
    router.get(
        admin.activity.index.url(),
        { action: action.value || undefined },
        { preserveState: true, replace: true },
    );
}

function subject(entry: ActivityLogEntry) {
    if (!entry.subject_type) {
        return '';
    }

    return `${entry.subject_type.split('\\').pop()} #${entry.subject_id}`;
}
</script>

<template>
    <Head :title="t('Activity log')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('Activity log')"
            :description="
                t(
                    'A record of sensitive actions such as role changes and partner verification.',
                )
            "
        />

        <form class="flex max-w-md gap-2" @submit.prevent="filter">
            <Input
                v-model="action"
                type="search"
                :placeholder="t('Filter by action, e.g. user.')"
                :aria-label="t('Filter by action')"
            />
            <Button type="submit" variant="outline">{{ t('Filter') }}</Button>
        </form>

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="p-3 font-medium">{{ t('When') }}</th>
                        <th class="p-3 font-medium">{{ t('Who') }}</th>
                        <th class="p-3 font-medium">{{ t('Action') }}</th>
                        <th class="p-3 font-medium">{{ t('Record') }}</th>
                        <th class="p-3 font-medium">{{ t('Details') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="entries.data.length === 0">
                        <td
                            colspan="5"
                            class="p-6 text-center text-muted-foreground"
                        >
                            {{ t('No activity recorded.') }}
                        </td>
                    </tr>
                    <tr v-for="entry in entries.data" :key="entry.id">
                        <td class="p-3 whitespace-nowrap text-muted-foreground">
                            {{ formatDateTime(entry.created_at) }}
                        </td>
                        <td class="p-3">
                            {{ entry.user?.name ?? t('System') }}
                        </td>
                        <td class="p-3">
                            <code class="text-xs">{{ entry.action }}</code>
                        </td>
                        <td class="p-3 text-muted-foreground">
                            {{ subject(entry) }}
                        </td>
                        <td class="p-3 font-mono text-xs text-muted-foreground">
                            {{
                                entry.properties
                                    ? JSON.stringify(entry.properties)
                                    : ''
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :paginator="entries" />
    </div>
</template>
