<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import Pagination from '@/components/Pagination.vue';
import StarRating from '@/components/StarRating.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { NativeSelect } from '@/components/ui/native-select';
import { useTrans } from '@/composables/useTrans';
import { formatDate } from '@/lib/format';
import listingsRoutes from '@/routes/listings';
import office from '@/routes/office';
import type { Paginated } from '@/types';

type ModeratedReview = {
    id: number;
    rating: number;
    comment: string | null;
    partner_reply: string | null;
    status: 'published' | 'hidden';
    moderation_note: string | null;
    created_at: string;
    author: { name: string; email: string };
    listing: { name: string; slug: string };
};

const props = defineProps<{
    reviews: Paginated<ModeratedReview>;
    filters: { status?: string; max_rating?: string | number };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Review moderation', href: office.reviews.index() },
        ],
    },
});

const { t } = useTrans();

function filter(key: string, value: string) {
    router.get(
        office.reviews.index.url(),
        { ...props.filters, [key]: value || undefined },
        { preserveState: true, replace: true },
    );
}

function hide(review: ModeratedReview) {
    const note = prompt(
        t('Why hide this review? (e.g. abusive, spam, personal information)'),
    );

    if (note) {
        router.put(
            office.reviews.update.url(review.id),
            { status: 'hidden', moderation_note: note },
            { preserveScroll: true },
        );
    }
}

function restore(review: ModeratedReview) {
    router.put(
        office.reviews.update.url(review.id),
        { status: 'published' },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="t('Review moderation')" />

    <div class="flex max-w-5xl flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('Review moderation')"
            :description="
                t(
                    'Hide reviews that are abusive, spam or share personal information.',
                )
            "
        />

        <div class="flex flex-wrap gap-2">
            <NativeSelect
                class="w-auto"
                :model-value="filters.status ?? ''"
                :aria-label="t('Status')"
                @change="
                    filter('status', ($event.target as HTMLSelectElement).value)
                "
            >
                <option value="">{{ t('All reviews') }}</option>
                <option value="published">{{ t('Published') }}</option>
                <option value="hidden">{{ t('Hidden') }}</option>
            </NativeSelect>
            <NativeSelect
                class="w-auto"
                :model-value="String(filters.max_rating ?? '')"
                :aria-label="t('Rating')"
                @change="
                    filter(
                        'max_rating',
                        ($event.target as HTMLSelectElement).value,
                    )
                "
            >
                <option value="">{{ t('Any rating') }}</option>
                <option value="2">{{ t('2 stars or lower') }}</option>
                <option value="1">{{ t('1 star') }}</option>
            </NativeSelect>
        </div>

        <ul class="space-y-3">
            <li
                v-if="reviews.data.length === 0"
                class="rounded-xl border border-dashed p-6 text-center text-muted-foreground"
            >
                {{ t('No reviews match.') }}
            </li>
            <li
                v-for="review in reviews.data"
                :key="review.id"
                class="rounded-xl border p-4"
                :class="{ 'bg-muted/50': review.status === 'hidden' }"
            >
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <Link
                            :href="listingsRoutes.show(review.listing.slug)"
                            class="font-medium hover:text-primary"
                            >{{ review.listing.name }}</Link
                        >
                        <p class="text-sm text-muted-foreground">
                            {{ review.author.name }} ·
                            {{ review.author.email }} ·
                            {{ formatDate(review.created_at) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <StarRating :rating="review.rating" />
                        <Badge
                            v-if="review.status === 'hidden'"
                            variant="outline"
                            >{{ t('Hidden') }}</Badge
                        >
                    </div>
                </div>
                <p v-if="review.comment" class="mt-2 text-sm">
                    {{ review.comment }}
                </p>
                <p
                    v-if="review.moderation_note"
                    class="mt-2 text-xs text-muted-foreground"
                >
                    {{
                        t('Hidden because: :note', {
                            note: review.moderation_note,
                        })
                    }}
                </p>
                <div class="mt-3">
                    <Button
                        v-if="review.status === 'published'"
                        size="sm"
                        variant="outline"
                        @click="hide(review)"
                        >{{ t('Hide') }}</Button
                    >
                    <Button
                        v-else
                        size="sm"
                        variant="outline"
                        @click="restore(review)"
                        >{{ t('Publish again') }}</Button
                    >
                </div>
            </li>
        </ul>

        <Pagination :paginator="reviews" />
    </div>
</template>
