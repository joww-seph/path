<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CheckCircle2,
    Circle,
    GripVertical,
    Pencil,
    Ticket,
    Trash2,
} from '@lucide/vue';
import CategoryIcon from '@/components/guide/CategoryIcon.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { formatClock } from '@/lib/format';
import listingsRoutes from '@/routes/listings';
import tourist from '@/routes/tourist';
import type { ItineraryItem, PlannerWarning } from '@/types';

defineProps<{
    item: ItineraryItem;
    index: number;
    warnings: PlannerWarning[];
    editable: boolean;
}>();

const emit = defineEmits<{
    (e: 'edit'): void;
    (e: 'remove'): void;
    (e: 'toggle-done'): void;
}>();

const { t } = useTrans();
</script>

<template>
    <div
        class="flex items-stretch gap-2 rounded-lg border bg-card p-3"
        :class="{ 'opacity-60': item.is_done }"
    >
        <button
            v-if="editable"
            type="button"
            class="drag-handle -ml-1 flex cursor-grab items-center text-muted-foreground active:cursor-grabbing"
            :aria-label="t('Drag to reorder :name', { name: item.title })"
        >
            <GripVertical class="size-4" />
        </button>

        <div class="w-[4.75rem] shrink-0 text-sm">
            <p class="font-semibold tabular-nums">
                {{ formatClock(item.start_time) }}
            </p>
            <p class="text-xs text-muted-foreground tabular-nums">
                {{ formatClock(item.end_time) }}
            </p>
        </div>

        <span
            class="flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white"
            :style="{
                backgroundColor: item.listing?.category?.color ?? '#475569',
            }"
        >
            {{ index + 1 }}
        </span>

        <div class="min-w-0 flex-1">
            <p
                class="flex items-center gap-1.5 font-medium"
                :class="{ 'line-through': item.is_done }"
            >
                <CategoryIcon
                    v-if="item.listing"
                    :icon="item.listing.category?.icon"
                    class="size-4 shrink-0 text-muted-foreground"
                />
                <Link
                    v-if="item.listing"
                    :href="listingsRoutes.show(item.listing.slug)"
                    class="truncate hover:text-primary"
                    >{{ item.title }}</Link
                >
                <span v-else class="truncate">{{ item.title }}</span>
            </p>
            <p class="text-xs text-muted-foreground">
                {{ t(':minutes min visit', { minutes: item.visit_minutes }) }}
                <template v-if="item.fixed_start_time">
                    ·
                    {{
                        t('fixed at :time', {
                            time: formatClock(item.fixed_start_time),
                        })
                    }}</template
                >
            </p>
            <Link
                v-if="item.booking"
                :href="tourist.bookings.show(item.booking.code)"
                class="mt-1 inline-flex items-center gap-1 rounded bg-accent px-1.5 py-0.5 text-xs font-medium text-accent-foreground"
            >
                <Ticket class="size-3" />
                {{ t('Booked · :code', { code: item.booking.code }) }}
            </Link>
            <p
                v-if="item.notes"
                class="mt-1 text-sm whitespace-pre-line text-muted-foreground"
            >
                {{ item.notes }}
            </p>
            <p
                v-for="warning in warnings"
                :key="warning.type"
                class="mt-1 flex items-start gap-1 text-xs text-amber-700 dark:text-amber-400"
            >
                <AlertTriangle class="mt-0.5 size-3.5 shrink-0" />
                {{ warning.message }}
            </p>
        </div>

        <div class="flex shrink-0 flex-col items-end gap-1">
            <Button
                variant="ghost"
                size="icon"
                class="size-7"
                :disabled="!editable"
                :aria-label="
                    item.is_done ? t('Mark as not done') : t('Mark as done')
                "
                :aria-pressed="item.is_done"
                @click="emit('toggle-done')"
            >
                <CheckCircle2 v-if="item.is_done" class="text-green-600" />
                <Circle v-else />
            </Button>
            <template v-if="editable">
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-7"
                    :aria-label="t('Edit stop')"
                    @click="emit('edit')"
                    ><Pencil
                /></Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-7"
                    :aria-label="t('Remove stop')"
                    @click="emit('remove')"
                    ><Trash2
                /></Button>
            </template>
        </div>
    </div>
</template>
