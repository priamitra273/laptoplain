<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { buildNavigationItems, flattenNavigationItems, toNavigationGroups } from '@/lib/menu';
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

const items = computed(() => buildNavigationItems(page.props.auth?.menu, (name) => route(name, undefined, false), page.url));
const navGroups = computed(() => toNavigationGroups(items.value));

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

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) {
            return;
        }

        // Pesan dikosongkan setelah ditampilkan. Partial reload (router.reload dengan
        // `only`) tidak mengirim ulang flash, tapi tetap membangun ulang page.props —
        // tanpa ini pesan yang sama akan muncul kedua kalinya sebagai toast baru.
        if (flash.success) {
            toast.add({ title: flash.success, color: 'success', icon: 'i-lucide-circle-check' });
            flash.success = null;
        }

        if (flash.error) {
            toast.add({ title: flash.error, color: 'error', icon: 'i-lucide-circle-alert' });
            flash.error = null;
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <UApp>
        <UDashboardGroup storage="local" unit="rem" class="bg-sidebar">
            <UDashboardSidebar
                collapsible
                resizable
                :default-size="16"
                :min-size="12"
                :max-size="24"
                :collapsed-size="4"
                :ui="{
                    root: 'transition-[width] duration-200 ease-out data-[dragging=true]:transition-none motion-reduce:transition-none',
                    header: 'overflow-hidden',
                    body: 'overflow-x-hidden [scrollbar-width:thin] [scrollbar-color:var(--ui-border-accented)_transparent]',
                    footer: 'overflow-hidden',
                }"
            >
                <template #header="{ collapsed }">
                    <div class="flex h-12 w-full items-start gap-2 rounded-lg" :class="{ 'justify-center': collapsed }">
                        <img :src="collapsed ? '/storage/t-logo.png' : '/storage/logo.png'" alt="Logo" class="h-9 w-auto" />
                    </div>
                </template>

                <template #default="{ collapsed }">
                    <UDashboardSearchButton :collapsed="collapsed" class="shrink-0" :class="{ 'mx-auto': collapsed }" />

                    <UNavigationMenu
                        :items="collapsed ? items : navGroups"
                        :collapsed="collapsed"
                        orientation="vertical"
                        :popover="{ content: { sideOffset: 8, alignOffset: -4 } }"
                        :tooltip="{ content: { sideOffset: 8 } }"
                        :ui="{ childList: 'min-w-44', separator: 'h-px' }"
                    />
                </template>

                <template #footer="{ collapsed }">
                    <UDropdownMenu
                        :items="links"
                        :content="{ align: collapsed ? 'center' : 'end', side: 'right', sideOffset: 8 }"
                        :class="collapsed ? 'mx-auto' : 'w-full'"
                    >
                        <UButton v-if="collapsed" color="neutral" variant="ghost" square :avatar="avatar" :ui="{ leadingAvatar: 'rounded-lg' }" />
                        <UButton v-else color="neutral" variant="ghost" class="h-12 w-full">
                            <UAvatar v-bind="avatar" :ui="{ root: 'rounded-lg' }" />
                            <span class="grid min-w-0 flex-1 text-start leading-tight">
                                <span class="truncate text-sm font-medium">{{ user?.name }}</span>
                                <span class="truncate text-xs text-muted">{{ user?.email }}</span>
                            </span>
                            <UIcon name="i-lucide-chevrons-up-down" class="size-4 shrink-0" />
                        </UButton>
                    </UDropdownMenu>
                </template>
            </UDashboardSidebar>

            <UDashboardSearch :groups="groups" placeholder="Cari halaman..." />

            <UDashboardPanel
                id="main"
                :ui="{
                    root: 'min-h-0 overflow-y-auto bg-default lg:m-2 lg:ms-0 lg:rounded-xl lg:shadow-sm',
                    body: 'overflow-visible',
                }"
            >
                <template #header>
                    <UDashboardNavbar :title="title" class="sticky top-0 z-10 bg-default/70 backdrop-blur-md">
                        <template #leading>
                            <UDashboardSidebarCollapse class="-ms-1" />
                        </template>

                        <template #right>
                            <ThemeSwitcher />
                            <UButton
                                :icon="appearance === 'dark' ? 'i-lucide-sun' : 'i-lucide-moon'"
                                color="neutral"
                                variant="ghost"
                                :aria-label="appearance === 'dark' ? 'Mode terang' : 'Mode gelap'"
                                @click="updateAppearance(appearance === 'dark' ? 'light' : 'dark')"
                            />
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
