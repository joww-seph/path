<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';
import type { Paginated } from '@/types';

defineProps<{
    paginator: Paginated<unknown>;
}>();

const { t } = useTrans();

function label(text: string) {
    return text
        .replace('&laquo; Previous', '‹')
        .replace('Next &raquo;', '›')
        .replace('pagination.previous', '‹')
        .replace('pagination.next', '›');
}
</script>

<template>
    <nav
        v-if="paginator.last_page > 1"
        class="flex flex-wrap items-center justify-between gap-2 text-sm"
        :aria-label="t('Pagination')"
    >
        <p class="text-muted-foreground">
            {{
                t('Showing :from–:to of :total', {
                    from: paginator.from ?? 0,
                    to: paginator.to ?? 0,
                    total: paginator.total,
                })
            }}
        </p>
        <div class="flex flex-wrap gap-1">
            <template v-for="(link, index) in paginator.links" :key="index">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="min-w-8 rounded-md border px-2.5 py-1 text-center hover:bg-accent"
                    :class="{
                        'border-primary bg-primary text-primary-foreground hover:bg-primary':
                            link.active,
                    }"
                    :aria-current="link.active ? 'page' : undefined"
                    >{{ label(link.label) }}</Link
                >
                <span
                    v-else
                    class="min-w-8 rounded-md border px-2.5 py-1 text-center text-muted-foreground opacity-50"
                    >{{ label(link.label) }}</span
                >
            </template>
        </div>
    </nav>
</template>
