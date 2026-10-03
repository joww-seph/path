<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { useTrans } from '@/composables/useTrans';
import admin from '@/routes/admin';
import type { Option } from '@/types';

type Hotline = {
    id: number;
    name: string;
    phone: string;
    description: string | null;
    position: number;
    type: string;
};

defineProps<{
    hotlines: Hotline[];
    types: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Hotlines', href: admin.hotlines.index() }],
    },
});

const { t } = useTrans();
const editing = ref<Hotline | null>(null);

function remove(hotline: Hotline) {
    if (confirm(t('Delete the hotline ":name"?', { name: hotline.name }))) {
        router.delete(admin.hotlines.destroy.url(hotline.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head :title="t('Hotlines')" />

    <div class="grid flex-1 gap-6 p-4 md:p-6 lg:grid-cols-[1fr_24rem]">
        <div class="space-y-6">
            <Heading
                :title="t('Hotlines')"
                :description="
                    t(
                        'Shown on the SOS page, the hotlines page and saved offline. Check every number before the pilot.',
                    )
                "
            />
            <ul class="divide-y rounded-xl border">
                <li
                    v-for="hotline in hotlines"
                    :key="hotline.id"
                    class="flex items-center gap-3 p-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="font-medium">
                            {{ hotline.name }} ·
                            <span class="font-mono">{{ hotline.phone }}</span>
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{
                                t(
                                    types.find(
                                        (type) => type.value === hotline.type,
                                    )?.label ?? hotline.type,
                                )
                            }}<template v-if="hotline.description">
                                · {{ hotline.description }}</template
                            >
                        </p>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="t('Edit')"
                        @click="editing = hotline"
                        ><Pencil
                    /></Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="t('Delete')"
                        @click="remove(hotline)"
                        ><Trash2
                    /></Button>
                </li>
            </ul>
        </div>
        <Form
            :key="editing?.id ?? 'new'"
            v-bind="
                editing
                    ? admin.hotlines.update.form(editing.id)
                    : admin.hotlines.store.form()
            "
            :options="{ preserveScroll: true }"
            reset-on-success
            class="space-y-3 self-start rounded-xl border p-4"
            v-slot="{ errors, processing }"
            @success="editing = null"
        >
            <h2 class="font-semibold">
                {{ editing ? t('Edit hotline') : t('Add a hotline') }}
            </h2>
            <div class="grid gap-1.5">
                <Label for="hotline-name">{{ t('Name') }}</Label>
                <Input
                    id="hotline-name"
                    name="name"
                    required
                    :default-value="editing?.name"
                    :placeholder="t('e.g. Paoay Municipal Police Station')"
                />
                <InputError :message="errors.name" />
            </div>
            <div class="grid gap-1.5">
                <Label for="hotline-type">{{ t('Type') }}</Label>
                <NativeSelect
                    id="hotline-type"
                    name="type"
                    :default-value="editing?.type ?? 'police'"
                >
                    <option
                        v-for="type in types"
                        :key="type.value"
                        :value="type.value"
                    >
                        {{ t(type.label) }}
                    </option>
                </NativeSelect>
            </div>
            <div class="grid gap-1.5">
                <Label for="hotline-phone">{{ t('Phone') }}</Label>
                <Input
                    id="hotline-phone"
                    name="phone"
                    type="tel"
                    required
                    :default-value="editing?.phone"
                />
                <InputError :message="errors.phone" />
            </div>
            <div class="grid gap-1.5">
                <Label for="hotline-description">{{ t('Description') }}</Label>
                <Input
                    id="hotline-description"
                    name="description"
                    :default-value="editing?.description ?? ''"
                />
            </div>
            <div class="grid gap-1.5">
                <Label for="hotline-position">{{ t('Order') }}</Label>
                <Input
                    id="hotline-position"
                    name="position"
                    type="number"
                    min="0"
                    :default-value="editing?.position ?? ''"
                />
            </div>
            <div class="flex gap-2">
                <Button :disabled="processing">{{
                    editing ? t('Save') : t('Add hotline')
                }}</Button>
                <Button
                    v-if="editing"
                    type="button"
                    variant="ghost"
                    @click="editing = null"
                    >{{ t('Cancel') }}</Button
                >
            </div>
        </Form>
    </div>
</template>
