<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Accessibility, MapPin, Star } from '@lucide/vue';
import CategoryIcon from '@/components/guide/CategoryIcon.vue';
import { useTrans } from '@/composables/useTrans';
import { formatPeso } from '@/lib/format';
import listings from '@/routes/listings';
import type { ListingCard } from '@/types';

defineProps<{
    listing: ListingCard;
}>();

const { t } = useTrans();
</script>

<template>
    <Link
        :href="listings.show(listing.slug)"
        class="group flex flex-col overflow-hidden rounded-xl border bg-card text-card-foreground transition-shadow hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
    >
        <div class="relative aspect-[4/3] overflow-hidden bg-muted">
            <img
                v-if="listing.photo"
                :src="listing.photo"
                :alt="listing.name"
                loading="lazy"
                class="size-full object-cover transition-transform group-hover:scale-105"
            />
            <div
                v-else
                class="flex size-full items-center justify-center"
                :style="{
                    backgroundColor: `${listing.category?.color ?? '#222d60'}1a`,
                    color: listing.category?.color ?? '#222d60',
                }"
            >
                <CategoryIcon
                    :icon="listing.category?.icon"
                    class="size-12 opacity-70"
                />
            </div>
            <span
                v-if="listing.is_featured"
                class="absolute top-2 left-2 rounded-full bg-gold px-2 py-0.5 text-xs font-semibold text-indigo-deep"
                >{{ t('Must see') }}</span
            >
        </div>
        <div class="flex flex-1 flex-col gap-1 p-4">
            <p
                v-if="listing.category"
                class="flex items-center gap-1 text-xs font-medium tracking-wide uppercase"
                :style="{ color: listing.category.color }"
            >
                <CategoryIcon :icon="listing.category.icon" class="size-3.5" />
                {{ t(listing.category.name) }}
            </p>
            <h3 class="leading-snug font-semibold group-hover:text-primary">
                {{ listing.name }}
            </h3>
            <p
                v-if="listing.summary"
                class="line-clamp-2 text-sm text-muted-foreground"
            >
                {{ listing.summary }}
            </p>
            <div
                class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-1 pt-2 text-sm"
            >
                <span
                    v-if="listing.is_free"
                    class="font-medium text-green-700 dark:text-green-400"
                    >{{ t('Free entry') }}</span
                >
                <span
                    v-else-if="listing.starting_price !== null"
                    class="font-medium"
                >
                    {{
                        t('From :price', {
                            price: formatPeso(listing.starting_price),
                        })
                    }}
                </span>
                <span
                    v-if="listing.reviews_count > 0"
                    class="flex items-center gap-1 text-muted-foreground"
                >
                    <Star class="size-3.5 fill-gold text-gold" />
                    {{ listing.rating_average.toFixed(1) }} ({{
                        listing.reviews_count
                    }})
                </span>
                <span
                    v-if="listing.distance_km !== undefined"
                    class="flex items-center gap-1 text-muted-foreground"
                >
                    <MapPin class="size-3.5" />
                    {{ t(':km km away', { km: listing.distance_km }) }}
                </span>
                <Accessibility
                    v-if="listing.is_accessible"
                    class="size-4 text-muted-foreground"
                    :aria-label="t('Accessible')"
                />
            </div>
        </div>
    </Link>
</template>
