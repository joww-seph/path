<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import InstallAppButton from '@/components/InstallAppButton.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import AdvisoryBanner from '@/components/AdvisoryBanner.vue';
import OfflineBanner from '@/components/OfflineBanner.vue';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTrans } from '@/composables/useTrans';
import {
    dashboard,
    explore,
    home,
    hotlines,
    login,
    map,
    register,
} from '@/routes';
import events from '@/routes/events';

const page = usePage();
const { t } = useTrans();
const { isCurrentOrParentUrl } = useCurrentUrl();
const menuOpen = ref(false);

const user = computed(() => page.props.auth.user);

const links = computed(() => [
    { title: t('Explore'), href: explore() },
    { title: t('Map'), href: map() },
    { title: t('Events'), href: events.index() },
    { title: t('Hotlines'), href: hotlines() },
]);
</script>

<template>
    <div class="flex min-h-dvh flex-col bg-background">
        <header
            class="sticky top-0 z-30 border-b bg-background/90 backdrop-blur supports-backdrop-filter:bg-background/75"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 md:px-6"
            >
                <a :href="home().url" class="flex items-center gap-2">
                    <span
                        class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground"
                    >
                        <AppLogoIcon class="size-5 fill-current" />
                    </span>
                    <span class="leading-tight">
                        <span class="block font-semibold">PaTH</span>
                        <span class="block text-xs text-muted-foreground">{{
                            t('Paoay Travel Hub')
                        }}</span>
                    </span>
                </a>

                <nav
                    class="ml-4 hidden items-center gap-1 md:flex"
                    :aria-label="t('Main')"
                >
                    <Link
                        v-for="link in links"
                        :key="link.title"
                        :href="link.href"
                        class="rounded-md px-3 py-2 text-sm font-medium hover:bg-accent"
                        :class="{
                            'bg-accent text-accent-foreground':
                                isCurrentOrParentUrl(link.href),
                        }"
                        >{{ link.title }}</Link
                    >
                </nav>

                <div class="ml-auto hidden items-center gap-2 md:flex">
                    <InstallAppButton />
                    <LanguageSwitcher />
                    <Button v-if="user" as-child size="sm">
                        <Link :href="dashboard()">{{ t('My dashboard') }}</Link>
                    </Button>
                    <template v-else>
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="login()">{{ t('Log in') }}</Link>
                        </Button>
                        <Button as-child size="sm">
                            <Link :href="register()">{{
                                t('Plan a trip')
                            }}</Link>
                        </Button>
                    </template>
                </div>

                <Button
                    variant="ghost"
                    size="icon"
                    class="ml-auto md:hidden"
                    :aria-label="menuOpen ? t('Close menu') : t('Open menu')"
                    :aria-expanded="menuOpen"
                    @click="menuOpen = !menuOpen"
                >
                    <X v-if="menuOpen" />
                    <Menu v-else />
                </Button>
            </div>

            <nav
                v-if="menuOpen"
                class="space-y-1 border-t px-4 py-3 md:hidden"
                :aria-label="t('Main')"
            >
                <Link
                    v-for="link in links"
                    :key="link.title"
                    :href="link.href"
                    class="block rounded-md px-3 py-2 font-medium hover:bg-accent"
                    @click="menuOpen = false"
                    >{{ link.title }}</Link
                >
                <Link
                    :href="user ? dashboard() : login()"
                    class="block rounded-md px-3 py-2 font-medium hover:bg-accent"
                    >{{ user ? t('My dashboard') : t('Log in') }}</Link
                >
                <div class="pt-2"><LanguageSwitcher /></div>
            </nav>
        </header>

        <OfflineBanner />
        <AdvisoryBanner dismissible />

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t bg-muted/40">
            <div
                class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-6 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between md:px-6"
            >
                <p>
                    {{
                        t(
                            'PaTH: Paoay Travel Hub · Paoay, Ilocos Norte, Philippines',
                        )
                    }}
                </p>
                <p>
                    {{ t('Emergency? Call 911.') }}
                </p>
            </div>
        </footer>

        <Toaster />
    </div>
</template>
