<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { buildNavigationItems, flattenNavigationItems } from '@/lib/menu';
import { getInitials } from '@/lib/utils';
import { router, usePage } from '@inertiajs/vue3';
import type { DropdownMenuItem } from '@nuxt/ui';
import { useToast } from '@nuxt/ui/composables';
import { computed, watch } from 'vue';

defineProps<{
    title?: string;
}>();

const page = usePage();
const toast = useToast();
const { appearance, updateAppearance } = useAppearance();

const items = computed(() => buildNavigationItems(page.props.auth?.menu, (name) => route(name)));

const links = computed<DropdownMenuItem[]>(() => [
    { label: 'Pengaturan profil', icon: 'i-lucide-settings', onSelect: () => router.visit(route('profile.edit')) },
    { label: 'Keluar', icon: 'i-lucide-log-out', color: 'error', onSelect: () => router.post(route('logout')) },
]);

const groups = computed(() => [
    {
        id: 'halaman',
        label: 'Halaman',
        items: flattenNavigationItems(items.value).map((item) => ({
            label: item.label,
            icon: item.icon,
            onSelect: () => router.visit(item.to as string),
        })),
    },
]);

const user = computed(() => page.props.auth?.user);

const avatar = computed(() => ({
    src: user.value?.avatar_url,
    alt: user.value?.name,
    text: user.value?.name ? getInitials(user.value.name) : undefined,
}));

// Menggantikan provider_v1/FlashToastProvider.vue — satu watcher sudah cukup.
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.add({ title: flash.success, color: 'success', icon: 'i-lucide-circle-check' });
        }

        if (flash?.error) {
            toast.add({ title: flash.error, color: 'error', icon: 'i-lucide-circle-alert' });
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <UApp>
        <UDashboardGroup storage="local" unit="rem">
            <UDashboardSidebar collapsible resizable :default-size="16" :min-size="12" :max-size="24"
                :collapsed-size="4" :ui="{
                    root: 'transition-[width] duration-200 ease-out data-[dragging=true]:transition-none motion-reduce:transition-none',
                    header: 'overflow-hidden',
                    body: 'overflow-x-hidden',
                    footer: 'overflow-hidden',
                }">
                <template #header="{ collapsed }">
                    <UIcon name="i-lucide-hexagon" class="size-5 shrink-0 text-primary"
                        :class="{ 'flex-1': collapsed }" />
                    <span v-if="!collapsed" class="truncate font-semibold">{{ page.props.name }}</span>
                </template>

                <template #default="{ collapsed }">
                    <UDashboardSearchButton :collapsed="collapsed" />
                    <UNavigationMenu :items="items" :collapsed="collapsed" orientation="vertical" />
                </template>

                <template #footer="{ collapsed }">
                    <UDropdownMenu :items="links" :content="{ align: collapsed ? 'center' : 'start' }" class="w-full">
                        <UButton v-if="collapsed" color="neutral" variant="ghost" square :avatar="avatar" />
                        <UButton v-else color="neutral" variant="ghost" :avatar="avatar" :label="user?.name"
                            trailing-icon="i-lucide-chevrons-up-down" class="w-full"
                            :ui="{ label: 'truncate', trailingIcon: 'ms-auto' }" />
                    </UDropdownMenu>
                </template>
            </UDashboardSidebar>

            <UDashboardSearch :groups="groups" placeholder="Cari halaman..." />

            <!-- scroll di root, bukan di body: konten harus lewat di belakang navbar agar backdrop-blur terlihat -->
            <UDashboardPanel id="main" :ui="{ root: 'overflow-y-auto', body: 'overflow-visible' }">
                <template #header>
                    <UDashboardNavbar :title="title" class="sticky top-0 z-10 bg-default/70 backdrop-blur-md">
                        <template #leading>
                            <UDashboardSidebarCollapse class="ms-auto" />
                        </template>

                        <template #right>
                            <ThemeSwitcher />
                            <UButton :icon="appearance === 'dark' ? 'i-lucide-sun' : 'i-lucide-moon'" color="neutral"
                                variant="ghost" :aria-label="appearance === 'dark' ? 'Mode terang' : 'Mode gelap'"
                                @click="updateAppearance(appearance === 'dark' ? 'light' : 'dark')" />
                        </template>
                    </UDashboardNavbar>
                </template>

                <template #body>
                    <slot />
                </template>
            </UDashboardPanel>
        </UDashboardGroup>
    </UApp>
</template>
