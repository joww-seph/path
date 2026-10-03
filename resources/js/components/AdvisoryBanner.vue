<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { AlertTriangle, Info, OctagonAlert, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useTrans } from '@/composables/useTrans';
import { formatDateTime } from '@/lib/format';
import type { Advisory } from '@/types';

const props = defineProps<{
    /** Advisories for a page (a listing); defaults to the town-wide ones shared on every page. */
    advisories?: Advisory[];
    dismissible?: boolean;
}>();

const page = usePage();
const { t } = useTrans();
const dismissed = ref<Set<number>>(new Set());

const shown = computed(() =>
    (props.advisories ?? page.props.townAdvisories ?? []).filter(
        (advisory) => !dismissed.value.has(advisory.id),
    ),
);

const styles = {
    info: 'border-blue-300 bg-blue-50 text-blue-950 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100',
    warning:
        'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100',
    danger: 'border-red-300 bg-red-50 text-red-950 dark:border-red-800 dark:bg-red-950 dark:text-red-100',
};
</script>

<template>
    <div v-if="shown.length" class="space-y-2">
        <div
            v-for="advisory in shown"
            :key="advisory.id"
            class="flex items-start gap-3 border px-4 py-3 text-sm"
            :class="[
                styles[advisory.severity],
                dismissible ? 'border-x-0 border-t-0' : 'rounded-lg',
            ]"
            role="alert"
        >
            <OctagonAlert
                v-if="advisory.severity === 'danger'"
                class="mt-0.5 size-4 shrink-0"
            />
            <AlertTriangle
                v-else-if="advisory.severity === 'warning'"
                class="mt-0.5 size-4 shrink-0"
            />
            <Info v-else class="mt-0.5 size-4 shrink-0" />
            <div class="min-w-0 flex-1">
                <p class="font-semibold">
                    {{
                        t('Tourism office advisory: :title', {
                            title: advisory.title,
                        })
                    }}
                </p>
                <p class="mt-0.5">{{ advisory.body }}</p>
                <p v-if="advisory.ends_at" class="mt-0.5 text-xs opacity-80">
                    {{
                        t('Until :time', {
                            time: formatDateTime(advisory.ends_at),
                        })
                    }}
                </p>
            </div>
            <button
                v-if="dismissible"
                type="button"
                class="shrink-0 rounded p-1 hover:bg-black/5"
                :aria-label="t('Hide this advisory')"
                @click="dismissed = new Set([...dismissed, advisory.id])"
            >
                <X class="size-4" />
            </button>
        </div>
    </div>
</template>
