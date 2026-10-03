<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!-- A map pin with a winding path through it: the "path" in PaTH. -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M20 2C12.27 2 6 8.1 6 15.62 6 25.5 20 38 20 38s14-12.5 14-22.38C34 8.1 27.73 2 20 2Zm-4.6 9.2c1.9-1.5 5.3-1.6 7.4.2 1.4 1.2 1.5 3 .3 4.2-1.3 1.3-3.7 1.6-5.2 2.9-1.3 1.1-1.2 2.9.3 3.8 1.4.8 3.5.6 4.9-.3l1.4 2c-2.3 1.5-5.6 1.8-8 .4-3-1.7-3.3-5.4-.8-7.6 1.6-1.4 4-1.7 5.3-2.8.4-.4.4-.9 0-1.2-1.1-.9-3.2-.8-4.2 0l-1.4-1.6Z"
        />
    </svg>
</template>
