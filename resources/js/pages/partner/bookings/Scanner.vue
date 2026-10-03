<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Camera, CameraOff } from '@lucide/vue';
import { onBeforeUnmount, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTrans } from '@/composables/useTrans';
import partner from '@/routes/partner';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Bookings', href: partner.bookings.index() },
            { title: 'Check in', href: partner.checkIn.scanner() },
        ],
    },
});

const { t } = useTrans();
const scanning = ref(false);
const cameraError = ref<string | null>(null);
let scanner: { stop: () => Promise<void>; clear: () => void } | null = null;

/**
 * Voucher QR codes hold a check-in link ending in the booking's secret token.
 */
function tokenFrom(text: string): string | null {
    const match = text.match(/\/partner\/check-in\/([A-Za-z0-9]+)/);

    return match ? match[1] : null;
}

async function start() {
    cameraError.value = null;

    try {
        const { Html5Qrcode } = await import('html5-qrcode');
        const instance = new Html5Qrcode('qr-reader');
        scanner = instance;
        scanning.value = true;

        await instance.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 240, height: 240 } },
            (text) => {
                const token = tokenFrom(text);

                if (token) {
                    void stop();
                    router.visit(partner.checkIn.show.url(token));
                }
            },
            () => undefined,
        );
    } catch {
        scanning.value = false;
        cameraError.value = t(
            'Could not open the camera. Allow camera access, or type the booking code below.',
        );
    }
}

async function stop() {
    if (scanner) {
        await scanner.stop().catch(() => undefined);
        scanner.clear();
        scanner = null;
    }

    scanning.value = false;
}

onBeforeUnmount(() => void stop());
</script>

<template>
    <Head :title="t('Check in a guest')" />

    <div class="mx-auto flex w-full max-w-md flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('Check in a guest')"
            :description="
                t(
                    'Scan the QR code on the tourist\'s voucher, or type the booking code.',
                )
            "
        />

        <div class="overflow-hidden rounded-xl border bg-muted">
            <div id="qr-reader" class="aspect-square w-full" />
        </div>

        <Button v-if="!scanning" @click="start"
            ><Camera /> {{ t('Start camera') }}</Button
        >
        <Button v-else variant="outline" @click="stop"
            ><CameraOff /> {{ t('Stop camera') }}</Button
        >
        <p v-if="cameraError" class="text-sm text-destructive">
            {{ cameraError }}
        </p>

        <Form
            v-bind="partner.checkIn.store.form()"
            class="grid gap-2 rounded-xl border p-4"
            v-slot="{ errors, processing }"
        >
            <Label for="code">{{ t('Booking code') }}</Label>
            <div class="flex gap-2">
                <Input
                    id="code"
                    name="code"
                    class="font-mono uppercase"
                    placeholder="PTH-XXXXXX"
                    required
                    autocomplete="off"
                />
                <Button :disabled="processing">{{ t('Check in') }}</Button>
            </div>
            <InputError :message="errors.code" />
        </Form>
    </div>
</template>
