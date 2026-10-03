<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTrans } from '@/composables/useTrans';
import office from '@/routes/office';
import type { HeritageStory } from '@/types';

const props = defineProps<{
    listingSlug: string;
    stories: HeritageStory[];
}>();

const { t } = useTrans();
const editing = ref<HeritageStory | null>(null);

function remove(story: HeritageStory) {
    if (confirm(t('Delete the story ":title"?', { title: story.title }))) {
        router.delete(
            office.listings.stories.destroy.url({
                listing: props.listingSlug,
                heritageStory: story.id,
            }),
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold">{{ t('Heritage stories') }}</h2>
            <p class="text-sm text-muted-foreground">
                {{
                    t(
                        'History and legends shown on the listing page. Cite a source where you can.',
                    )
                }}
            </p>
        </div>

        <ul v-if="stories.length" class="divide-y rounded-lg border">
            <li
                v-for="story in stories"
                :key="story.id"
                class="flex items-start gap-3 p-3"
            >
                <div class="min-w-0 flex-1">
                    <p class="font-medium">{{ story.title }}</p>
                    <p class="line-clamp-2 text-sm text-muted-foreground">
                        {{ story.body }}
                    </p>
                </div>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :aria-label="t('Edit')"
                    @click="editing = story"
                >
                    <Pencil />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    :aria-label="t('Delete')"
                    @click="remove(story)"
                >
                    <Trash2 />
                </Button>
            </li>
        </ul>

        <Form
            :key="editing?.id ?? 'new'"
            v-bind="
                editing
                    ? office.listings.stories.update.form({
                          listing: listingSlug,
                          heritageStory: editing.id,
                      })
                    : office.listings.stories.store.form(listingSlug)
            "
            :options="{ preserveScroll: true }"
            reset-on-success
            class="space-y-3 rounded-lg border p-4"
            v-slot="{ errors, processing }"
            @success="editing = null"
        >
            <div class="grid gap-1.5">
                <Label for="story-title">{{ t('Title') }}</Label>
                <Input
                    id="story-title"
                    name="title"
                    required
                    :default-value="editing?.title"
                />
                <InputError :message="errors.title" />
            </div>
            <div class="grid gap-1.5">
                <Label for="story-body">{{ t('Story') }}</Label>
                <Textarea
                    id="story-body"
                    name="body"
                    rows="6"
                    required
                    :default-value="editing?.body"
                />
                <InputError :message="errors.body" />
            </div>
            <div class="grid gap-1.5">
                <Label for="story-source">{{ t('Source') }}</Label>
                <Input
                    id="story-source"
                    name="source"
                    :default-value="editing?.source ?? ''"
                />
            </div>
            <div class="flex gap-2">
                <Button :disabled="processing" variant="secondary">{{
                    editing ? t('Save story') : t('Add story')
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
    </section>
</template>
