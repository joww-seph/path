<script setup lang="ts">
import { Form, router } from '@inertiajs/vue3';
import { MessageSquareReply, Star } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import StarRating from '@/components/StarRating.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useTrans } from '@/composables/useTrans';
import { formatDate } from '@/lib/format';
import tourist from '@/routes/tourist';
import type { Review } from '@/types';

const props = defineProps<{
    listingSlug: string;
    reviews: Review[];
    average: number;
    count: number;
    canReview: boolean;
    myReview: {
        id: number;
        rating: number;
        comment: string | null;
        status: string;
    } | null;
}>();

const { t } = useTrans();
const rating = ref(props.myReview?.rating ?? 0);
const editing = ref(false);

function remove() {
    if (props.myReview && confirm(t('Delete your review?'))) {
        router.delete(tourist.reviews.destroy.url(props.myReview.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <section id="reviews" class="scroll-mt-20 space-y-4">
        <div class="flex flex-wrap items-baseline gap-3">
            <h2 class="text-xl font-semibold">{{ t('Reviews') }}</h2>
            <span
                v-if="count"
                class="flex items-center gap-2 text-sm text-muted-foreground"
            >
                <StarRating :rating="average" /> {{ average.toFixed(1) }} ·
                {{ t(':count reviews', { count }) }}
            </span>
        </div>

        <Form
            v-if="canReview || (myReview && editing)"
            v-bind="
                myReview
                    ? tourist.reviews.update.form(myReview.id)
                    : tourist.reviews.store.form(listingSlug)
            "
            :options="{ preserveScroll: true }"
            class="space-y-3 rounded-xl border p-4"
            v-slot="{ errors, processing }"
            @success="editing = false"
        >
            <p class="font-medium">
                {{
                    myReview ? t('Edit your review') : t('How was your visit?')
                }}
            </p>
            <fieldset>
                <legend class="sr-only">{{ t('Rating') }}</legend>
                <div class="flex gap-1">
                    <label
                        v-for="value in 5"
                        :key="value"
                        class="cursor-pointer"
                    >
                        <input
                            v-model="rating"
                            type="radio"
                            name="rating"
                            :value="value"
                            class="sr-only"
                            required
                        />
                        <Star
                            class="size-7"
                            :class="
                                value <= rating
                                    ? 'fill-gold text-gold'
                                    : 'text-muted-foreground/40'
                            "
                        />
                        <span class="sr-only">{{
                            t(':count stars', { count: value })
                        }}</span>
                    </label>
                </div>
                <InputError :message="errors.rating" />
            </fieldset>
            <div class="grid gap-1.5">
                <Label for="review-comment">{{
                    t('Your review (optional)')
                }}</Label>
                <Textarea
                    id="review-comment"
                    name="comment"
                    rows="3"
                    :default-value="myReview?.comment ?? ''"
                    :placeholder="t('What should other visitors know?')"
                />
                <InputError :message="errors.comment" />
            </div>
            <Button :disabled="processing || rating === 0">{{
                myReview ? t('Save review') : t('Post review')
            }}</Button>
        </Form>

        <p
            v-else-if="myReview"
            class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
        >
            {{
                myReview.status === 'hidden'
                    ? t('Your review was hidden by the tourism office.')
                    : t('You reviewed this place.')
            }}
            <Button
                variant="link"
                size="sm"
                class="h-auto p-0"
                @click="editing = true"
                >{{ t('Edit') }}</Button
            >
            <Button
                variant="link"
                size="sm"
                class="h-auto p-0 text-destructive"
                @click="remove"
                >{{ t('Delete') }}</Button
            >
        </p>
        <p v-else class="text-sm text-muted-foreground">
            {{
                t(
                    'Only visitors who completed a booking or ticked this place off their itinerary can review it.',
                )
            }}
        </p>

        <p v-if="reviews.length === 0" class="text-sm text-muted-foreground">
            {{ t('No reviews yet.') }}
        </p>
        <ul class="space-y-4">
            <li
                v-for="review in reviews"
                :key="review.id"
                class="rounded-xl border p-4"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-medium">{{ review.author }}</span>
                    <span
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <StarRating :rating="review.rating" />
                        {{ formatDate(review.created_at) }}
                    </span>
                </div>
                <p v-if="review.comment" class="mt-2 whitespace-pre-line">
                    {{ review.comment }}
                </p>
                <div
                    v-if="review.partner_reply"
                    class="mt-3 flex gap-2 rounded-md bg-muted p-3 text-sm"
                >
                    <MessageSquareReply class="mt-0.5 size-4 shrink-0" />
                    <p>
                        <span class="font-medium">{{
                            t('Reply from the owner:')
                        }}</span>
                        {{ review.partner_reply }}
                    </p>
                </div>
            </li>
        </ul>
    </section>
</template>
