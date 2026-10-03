<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';
import { computed } from 'vue';
import type { Paginated, ResourcePage } from '@/types';

const props = defineProps<{
    paginator: Paginated<unknown> | ResourcePage<unknown>;
}>();

// Paginators come flat from ->paginate() and nested under "meta" from API resources.
const page = computed(() =>
    'meta' in props.paginator ? props.paginator.meta : props.paginator,
);

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
        v-if="page.last_page > 1"
        class="flex flex-wrap items-center justify-between gap-2 text-sm"
        :aria-label="t('Pagination')"
    >
        <p class="text-muted-foreground">
            {{
                t('Showing :from–:to of :total', {
                    from: page.from ?? 0,
                    to: page.to ?? 0,
                    total: page.total,
                })
            }}
        </p>
        <div class="flex flex-wrap gap-1">
            <template v-for="(link, index) in page.links" :key="index">
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
