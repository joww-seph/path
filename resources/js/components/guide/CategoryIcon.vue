<script setup lang="ts">
import {
    BedDouble,
    Car,
    Flag,
    Info,
    Landmark,
    MapPin,
    Mountain,
    ShoppingBag,
    Utensils,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';

const props = defineProps<{
    icon?: string | null;
}>();

const icons: Record<string, Component> = {
    landmark: Landmark,
    utensils: Utensils,
    'bed-double': BedDouble,
    mountain: Mountain,
    flag: Flag,
    'shopping-bag': ShoppingBag,
    car: Car,
    info: Info,
};

const component = computed(() => icons[props.icon ?? ''] ?? MapPin);
</script>

<template>
    <component :is="component" aria-hidden="true" />
</template>
