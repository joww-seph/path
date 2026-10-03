<script setup lang="ts">
import { Download } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import { canInstall, promptInstall } from '@/lib/pwa';

const { t } = useTrans();
</script>

<template>
    <Button
        v-if="canInstall"
        variant="outline"
        size="sm"
        @click="promptInstall"
    >
        <Download /> {{ t('Install app') }}
    </Button>
</template>
