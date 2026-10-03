<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Languages } from '@lucide/vue';
import { computed } from 'vue';
import { update } from '@/routes/locale';

const page = usePage();
const locale = computed(() => page.props.locale);

function switchTo(code: string) {
    router.post(update.url(), { locale: code }, { preserveScroll: true });
}
</script>

<template>
    <div
        class="flex items-center gap-2 px-2 text-xs text-muted-foreground group-data-[collapsible=icon]:hidden"
    >
        <Languages class="size-4" aria-hidden="true" />
        <button
            v-for="(label, code) in locale.available"
            :key="code"
            type="button"
            class="rounded px-1.5 py-0.5 hover:text-foreground"
            :class="{
                'bg-sidebar-accent font-medium text-foreground':
                    code === locale.current,
            }"
            :aria-pressed="code === locale.current"
            @click="switchTo(code)"
        >
            {{ label }}
        </button>
    </div>
</template>
