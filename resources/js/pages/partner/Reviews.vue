<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/Pagination.vue';
import StarRating from '@/components/StarRating.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useTrans } from '@/composables/useTrans';
import { formatDate } from '@/lib/format';
import listingsRoutes from '@/routes/listings';
import partner from '@/routes/partner';
import type { Paginated, Review } from '@/types';

defineProps<{
    reviews: Paginated<Review & { listing: { name: string; slug: string } }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Reviews', href: partner.reviews.index() }],
    },
});

const { t } = useTrans();
const replying = ref<number | null>(null);
</script>

<template>
    <Head :title="t('Reviews')" />

    <div class="flex max-w-4xl flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('Reviews')"
            :description="
                t(
                    'What visitors say about your listings. A short, kind reply goes a long way.',
                )
            "
        />

        <p
            v-if="reviews.data.length === 0"
            class="rounded-xl border border-dashed p-8 text-center text-muted-foreground"
        >
            {{ t('No reviews yet.') }}
        </p>

        <ul class="space-y-4">
            <li
                v-for="review in reviews.data"
                :key="review.id"
                class="rounded-xl border p-4"
            >
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <Link
                            :href="listingsRoutes.show(review.listing.slug)"
                            class="font-medium hover:text-primary"
                            >{{ review.listing.name }}</Link
                        >
                        <p class="text-sm text-muted-foreground">
                            {{ review.author }} ·
                            {{ formatDate(review.created_at) }}
                        </p>
                    </div>
                    <StarRating :rating="review.rating" />
                </div>
                <p v-if="review.comment" class="mt-2">{{ review.comment }}</p>

                <Form
                    v-if="replying === review.id"
                    v-bind="partner.reviews.reply.form(review.id)"
                    :options="{ preserveScroll: true }"
                    class="mt-3 space-y-2"
                    v-slot="{ errors, processing }"
                    @success="replying = null"
                >
                    <Textarea
                        name="partner_reply"
                        rows="3"
                        :default-value="review.partner_reply ?? ''"
                        :aria-label="t('Your reply')"
                    />
                    <InputError :message="errors.partner_reply" />
                    <div class="flex gap-2">
                        <Button size="sm" :disabled="processing">{{
                            t('Save reply')
                        }}</Button>
                        <Button
                            size="sm"
                            type="button"
                            variant="ghost"
                            @click="replying = null"
                            >{{ t('Cancel') }}</Button
                        >
                    </div>
                </Form>
                <template v-else>
                    <p
                        v-if="review.partner_reply"
                        class="mt-3 rounded-md bg-muted p-3 text-sm"
                    >
                        <span class="font-medium">{{ t('Your reply:') }}</span>
                        {{ review.partner_reply }}
                    </p>
                    <Button
                        size="sm"
                        variant="outline"
                        class="mt-3"
                        @click="replying = review.id"
                    >
                        {{
                            review.partner_reply ? t('Edit reply') : t('Reply')
                        }}
                    </Button>
                </template>
            </li>
        </ul>

        <Pagination :paginator="reviews" />
    </div>
</template>
