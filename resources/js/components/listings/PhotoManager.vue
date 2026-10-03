<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { ImagePlus, Star, Trash2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { useTrans } from '@/composables/useTrans';
import listingsRoutes from '@/routes/listings';
import type { ListingPhoto } from '@/types';

const props = defineProps<{
    listingSlug: string;
    photos: ListingPhoto[];
}>();

const { t } = useTrans();

function makeCover(photo: ListingPhoto) {
    router.patch(
        listingsRoutes.photos.update.url({
            listing: props.listingSlug,
            photo: photo.id,
        }),
        { make_cover: true },
        { preserveScroll: true },
    );
}

function remove(photo: ListingPhoto) {
    if (confirm(t('Delete this photo?'))) {
        router.delete(
            listingsRoutes.photos.destroy.url({
                listing: props.listingSlug,
                photo: photo.id,
            }),
            {
                preserveScroll: true,
            },
        );
    }
}
</script>

<template>
    <section class="space-y-4">
        <div>
            <h2 class="text-lg font-semibold">{{ t('Photos') }}</h2>
            <p class="text-sm text-muted-foreground">
                {{
                    t(
                        'Up to 12 photos. The first one is the cover. Photos are resized automatically.',
                    )
                }}
            </p>
        </div>

        <ul
            v-if="photos.length"
            class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
        >
            <li
                v-for="(photo, index) in photos"
                :key="photo.id"
                class="group relative overflow-hidden rounded-lg border"
            >
                <img
                    :src="photo.thumbnail_url"
                    :alt="photo.caption ?? ''"
                    class="aspect-[4/3] w-full object-cover"
                />
                <span
                    v-if="index === 0"
                    class="absolute top-1.5 left-1.5 rounded bg-gold px-1.5 text-xs font-semibold text-indigo-deep"
                >
                    {{ t('Cover') }}
                </span>
                <div
                    class="absolute inset-x-0 bottom-0 flex justify-end gap-1 bg-black/50 p-1 opacity-0 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100"
                >
                    <Button
                        v-if="index !== 0"
                        type="button"
                        size="icon"
                        variant="secondary"
                        class="size-7"
                        :aria-label="t('Make cover photo')"
                        @click="makeCover(photo)"
                    >
                        <Star />
                    </Button>
                    <Button
                        type="button"
                        size="icon"
                        variant="destructive"
                        class="size-7"
                        :aria-label="t('Delete photo')"
                        @click="remove(photo)"
                    >
                        <Trash2 />
                    </Button>
                </div>
            </li>
        </ul>

        <Form
            v-if="photos.length < 12"
            v-bind="listingsRoutes.photos.store.form(listingSlug)"
            :options="{ preserveScroll: true }"
            reset-on-success
            class="flex flex-wrap items-center gap-3"
            v-slot="{ errors, processing, progress }"
        >
            <label
                class="flex cursor-pointer items-center gap-2 rounded-md border border-dashed px-4 py-3 text-sm hover:border-primary"
            >
                <ImagePlus class="size-5 text-muted-foreground" />
                <span>{{ t('Choose photos') }}</span>
                <input
                    type="file"
                    name="photos[]"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    class="sr-only"
                    required
                />
            </label>
            <Button :disabled="processing">{{ t('Upload') }}</Button>
            <progress
                v-if="progress"
                :value="progress.percentage"
                max="100"
                class="h-2 w-32"
            />
            <InputError
                class="w-full"
                :message="
                    errors.photos ??
                    Object.entries(errors).find(([key]) =>
                        key.startsWith('photos.'),
                    )?.[1]
                "
            />
        </Form>
    </section>
</template>
