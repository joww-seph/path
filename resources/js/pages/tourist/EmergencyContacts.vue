<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Pencil, Phone, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTrans } from '@/composables/useTrans';
import tourist from '@/routes/tourist';
import type { EmergencyContact } from '@/types';

defineProps<{
    contacts: EmergencyContact[];
    maxContacts: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Emergency contacts',
                href: tourist.emergencyContacts.index(),
            },
        ],
    },
});

const { t } = useTrans();
const editing = ref<EmergencyContact | null>(null);

function remove(contact: EmergencyContact) {
    if (
        confirm(
            t('Remove :name from your emergency contacts?', {
                name: contact.name,
            }),
        )
    ) {
        router.delete(tourist.emergencyContacts.destroy.url(contact.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head :title="t('Emergency contacts')" />

    <div class="grid max-w-4xl gap-6 p-4 md:p-6 lg:grid-cols-[1fr_20rem]">
        <div>
            <Heading
                :title="t('Emergency contacts')"
                :description="
                    t(
                        'When you press SOS, PaTH texts and emails your location to these people.',
                    )
                "
            />

            <p
                v-if="contacts.length === 0"
                class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                {{ t('No emergency contacts yet. Add someone you trust.') }}
            </p>

            <ul v-else class="divide-y rounded-lg border">
                <li
                    v-for="contact in contacts"
                    :key="contact.id"
                    class="flex items-center gap-3 p-4"
                >
                    <div class="min-w-0 flex-1">
                        <p class="font-medium">
                            {{ contact.name }}
                            <span
                                v-if="contact.relationship"
                                class="text-sm font-normal text-muted-foreground"
                                >· {{ contact.relationship }}</span
                            >
                        </p>
                        <p
                            class="flex items-center gap-1 text-sm text-muted-foreground"
                        >
                            <Phone class="size-3.5" /> {{ contact.phone }}
                            <span v-if="contact.email">
                                · {{ contact.email }}</span
                            >
                        </p>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="t('Edit')"
                        @click="editing = contact"
                    >
                        <Pencil />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        :aria-label="t('Remove')"
                        @click="remove(contact)"
                    >
                        <Trash2 />
                    </Button>
                </li>
            </ul>
        </div>

        <Card class="self-start">
            <CardHeader>
                <CardTitle>{{
                    editing ? t('Edit contact') : t('Add a contact')
                }}</CardTitle>
            </CardHeader>
            <CardContent>
                <p
                    v-if="!editing && contacts.length >= maxContacts"
                    class="text-sm text-muted-foreground"
                >
                    {{
                        t('You can save up to :count emergency contacts.', {
                            count: maxContacts,
                        })
                    }}
                </p>
                <Form
                    v-else
                    :key="editing?.id ?? 'new'"
                    v-bind="
                        editing
                            ? tourist.emergencyContacts.update.form(editing.id)
                            : tourist.emergencyContacts.store.form()
                    "
                    reset-on-success
                    class="space-y-4"
                    v-slot="{ errors, processing }"
                    @success="editing = null"
                >
                    <div class="grid gap-2">
                        <Label for="contact-name">{{ t('Name') }}</Label>
                        <Input
                            id="contact-name"
                            name="name"
                            required
                            :default-value="editing?.name"
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact-relationship">{{
                            t('Relationship')
                        }}</Label>
                        <Input
                            id="contact-relationship"
                            name="relationship"
                            :placeholder="t('e.g. Mother, friend')"
                            :default-value="editing?.relationship ?? ''"
                        />
                        <InputError :message="errors.relationship" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact-phone">{{
                            t('Mobile number')
                        }}</Label>
                        <Input
                            id="contact-phone"
                            name="phone"
                            type="tel"
                            required
                            placeholder="09XX XXX XXXX"
                            :default-value="editing?.phone"
                        />
                        <InputError :message="errors.phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="contact-email">{{
                            t('Email (optional)')
                        }}</Label>
                        <Input
                            id="contact-email"
                            name="email"
                            type="email"
                            :default-value="editing?.email ?? ''"
                        />
                        <InputError :message="errors.email" />
                    </div>
                    <div class="flex gap-2">
                        <Button :disabled="processing">{{
                            editing ? t('Save changes') : t('Add contact')
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
            </CardContent>
        </Card>
    </div>
</template>
