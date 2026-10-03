<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { useTrans } from '@/composables/useTrans';
import { formatDate } from '@/lib/format';
import admin from '@/routes/admin';
import type { Option, Paginated, Role } from '@/types';

type AdminUser = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: Role;
    email_verified_at: string | null;
    deactivated_at: string | null;
    created_at: string;
};

const props = defineProps<{
    users: Paginated<AdminUser>;
    filters: { search?: string; role?: string; status?: string };
    roles: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Users and roles', href: admin.users.index() }],
    },
});

const page = usePage();
const { t } = useTrans();

const search = ref(props.filters.search ?? '');
const role = ref(props.filters.role ?? '');
const status = ref(props.filters.status ?? '');

function applyFilters() {
    router.get(
        admin.users.index.url(),
        {
            search: search.value || undefined,
            role: role.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function changeRole(user: AdminUser, newRole: string) {
    if (newRole === user.role) {
        return;
    }

    const label = props.roles.find((option) => option.value === newRole)?.label;

    if (
        !confirm(
            t('Make :name a :role?', {
                name: user.name,
                role: t(label ?? newRole),
            }),
        )
    ) {
        router.reload({ only: ['users'] });

        return;
    }

    router.patch(
        admin.users.role.url(user.id),
        { role: newRole },
        { preserveScroll: true },
    );
}

function toggleActive(user: AdminUser) {
    if (user.deactivated_at) {
        router.post(
            admin.users.reactivate.url(user.id),
            {},
            { preserveScroll: true },
        );

        return;
    }

    if (
        confirm(
            t('Deactivate :name? They will be signed out and cannot log in.', {
                name: user.name,
            }),
        )
    ) {
        router.post(
            admin.users.deactivate.url(user.id),
            {},
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <Head :title="t('Users and roles')" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="t('Users and roles')"
            :description="
                t('Search accounts, change roles and deactivate users.')
            "
        />

        <form
            class="flex flex-wrap items-end gap-3"
            role="search"
            @submit.prevent="applyFilters"
        >
            <div class="relative min-w-56 flex-1">
                <Search
                    class="absolute top-2.5 left-2.5 size-4 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    v-model="search"
                    class="pl-8"
                    type="search"
                    :placeholder="t('Name, email or phone')"
                    :aria-label="t('Search users')"
                />
            </div>
            <NativeSelect
                v-model="role"
                class="w-auto"
                :aria-label="t('Role')"
                @change="applyFilters"
            >
                <option value="">{{ t('All roles') }}</option>
                <option
                    v-for="option in roles"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ t(option.label) }}
                </option>
            </NativeSelect>
            <NativeSelect
                v-model="status"
                class="w-auto"
                :aria-label="t('Status')"
                @change="applyFilters"
            >
                <option value="">{{ t('Any status') }}</option>
                <option value="active">{{ t('Active') }}</option>
                <option value="deactivated">{{ t('Deactivated') }}</option>
            </NativeSelect>
            <Button type="submit" variant="outline">{{ t('Search') }}</Button>
        </form>

        <InputError :message="page.props.errors?.user as string | undefined" />

        <div class="overflow-x-auto rounded-xl border">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted/50 text-muted-foreground">
                    <tr>
                        <th class="p-3 font-medium">{{ t('Name') }}</th>
                        <th class="p-3 font-medium">{{ t('Role') }}</th>
                        <th class="p-3 font-medium">{{ t('Joined') }}</th>
                        <th class="p-3 font-medium">{{ t('Status') }}</th>
                        <th class="p-3">
                            <span class="sr-only">{{ t('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-if="users.data.length === 0">
                        <td
                            colspan="5"
                            class="p-6 text-center text-muted-foreground"
                        >
                            {{ t('No users match these filters.') }}
                        </td>
                    </tr>
                    <tr v-for="user in users.data" :key="user.id">
                        <td class="p-3">
                            <p class="font-medium">{{ user.name }}</p>
                            <p class="text-muted-foreground">
                                {{ user.email }}
                                <span v-if="user.phone">
                                    · {{ user.phone }}</span
                                >
                            </p>
                        </td>
                        <td class="p-3">
                            <NativeSelect
                                class="w-auto"
                                :model-value="user.role"
                                :disabled="user.id === page.props.auth.user.id"
                                :aria-label="
                                    t('Role for :name', { name: user.name })
                                "
                                @change="
                                    changeRole(
                                        user,
                                        ($event.target as HTMLSelectElement)
                                            .value,
                                    )
                                "
                            >
                                <option
                                    v-for="option in roles"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ t(option.label) }}
                                </option>
                            </NativeSelect>
                        </td>
                        <td class="p-3 text-muted-foreground">
                            {{ formatDate(user.created_at) }}
                        </td>
                        <td class="p-3">
                            <Badge
                                v-if="user.deactivated_at"
                                variant="destructive"
                                >{{ t('Deactivated') }}</Badge
                            >
                            <Badge
                                v-else-if="!user.email_verified_at"
                                variant="outline"
                                >{{ t('Unverified email') }}</Badge
                            >
                            <Badge v-else variant="secondary">{{
                                t('Active')
                            }}</Badge>
                        </td>
                        <td class="p-3 text-right">
                            <Button
                                v-if="user.id !== page.props.auth.user.id"
                                size="sm"
                                :variant="
                                    user.deactivated_at ? 'outline' : 'ghost'
                                "
                                @click="toggleActive(user)"
                            >
                                {{
                                    user.deactivated_at
                                        ? t('Reactivate')
                                        : t('Deactivate')
                                }}
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Pagination :paginator="users" />
    </div>
</template>
