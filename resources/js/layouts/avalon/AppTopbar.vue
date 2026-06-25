<script setup lang="ts">
import { useLayout } from '@/composables/useLayouts';
import { Notification } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { onClickOutside } from '@vueuse/core';
import Avatar from 'primevue/avatar';
import { computed, inject, onMounted, onUnmounted, Ref, ref, watch } from 'vue';

interface NotificationStore {
    notifications: Ref<Notification[]>;
    unreadCount: Ref<number>;
    markAsRead: (notificationId: string) => Promise<void>;
    clearNotifications: () => Promise<void>;
    connect: () => void;
    disconnect: () => void;
}

const { onMenuToggle, onConfigSidebarToggle } = useLayout();

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

async function logout() {
    router.post(route('logout'));
}

const notificationStore: NotificationStore | undefined = inject('notifications');
if (!notificationStore) {
    throw new Error('NotificationProvider is missing');
}
const { notifications, unreadCount, markAsRead, clearNotifications, connect, disconnect } = notificationStore;

const showNotificationDropdown = ref(false);
const showUserMenu = ref(false);
const notificationContainer = ref<HTMLElement | null>(null);
const userMenuContainer = ref<HTMLElement | null>(null);

onClickOutside(notificationContainer, () => {
    showNotificationDropdown.value = false;
});
onClickOutside(userMenuContainer, () => {
    showUserMenu.value = false;
});

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') {
        showNotificationDropdown.value = false;
        showUserMenu.value = false;
    }
}

onMounted(() => document.addEventListener('keydown', handleKeydown));
onUnmounted(() => document.removeEventListener('keydown', handleKeydown));

const readNotification = async (notificationId: string) => {
    markAsRead(notificationId);
};

const clear = async () => {
    showNotificationDropdown.value = false;
    await clearNotifications();
};

function isDelete(message: string): boolean {
    return message.toLowerCase().includes('delete');
}

function getNotifIcon(message: string): string {
    const m = message.toLowerCase();
    if (m.includes('delet')) return 'pi pi-trash';
    if (m.includes('creat') || m.includes('new')) return 'pi pi-plus-circle';
    if (m.includes('mention')) return 'pi pi-at';
    return 'pi pi-pencil';
}

function notifIconClasses(message: string): string {
    const m = message.toLowerCase();
    if (m.includes('delet')) return 'bg-red-500/10 text-red-500';
    if (m.includes('creat') || m.includes('new')) return 'bg-emerald-500/10 text-emerald-500';
    if (m.includes('mention')) return 'bg-violet-500/10 text-violet-500';
    return 'bg-blue-500/10 text-blue-500';
}

const avatarLabel = computed(() => (user.value as any)?.name?.charAt(0).toUpperCase() ?? 'U');
const avatarImage = computed(() => (user.value as any)?.avatar_url ?? null);
const userName = computed(() => (user.value as any)?.name ?? '');

watch(
    user,
    (newUser) => {
        if (!notifications) return;
        if (newUser) {
            connect();
        } else {
            disconnect();
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-start">
            <Link class="layout-topbar-logo" href="/">
                <img src="/storage/logo.png" />
            </Link>
            <a ref="menuButton" class="layout-menu-button" @click="onMenuToggle">
                <i class="pi pi-angle-right"></i>
            </a>
        </div>

        <div class="layout-topbar-end">
            <div class="layout-topbar-actions-end">
                <ul class="layout-topbar-items">
                    <!-- Notification Bell -->
                    <li ref="notificationContainer" class="relative">
                        <button
                            type="button"
                            :class="{ 'bg-[var(--topbar-item-hover-bg)]': showNotificationDropdown }"
                            aria-label="Notifications"
                            @click="
                                () => {
                                    showNotificationDropdown = !showNotificationDropdown;
                                    showUserMenu = false;
                                }
                            "
                        >
                            <i class="pi pi-bell"></i>
                            <span
                                v-if="unreadCount > 0"
                                class="pointer-events-none absolute right-1 top-1 box-border flex h-[15px] min-w-[15px] items-center justify-center rounded-full bg-red-500 px-[3px] text-[9px] font-bold leading-none text-white"
                            >
                                {{ unreadCount > 9 ? '9+' : unreadCount }}
                            </span>
                        </button>

                        <Transition
                            enter-active-class="transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] motion-reduce:transition-none"
                            enter-from-class="-translate-y-1.5 scale-95 opacity-0"
                            enter-to-class="translate-y-0 scale-100 opacity-100"
                            leave-active-class="transition duration-150 ease-[cubic-bezier(0.4,0,1,1)] motion-reduce:transition-none"
                            leave-from-class="translate-y-0 scale-100 opacity-100"
                            leave-to-class="-translate-y-1.5 scale-95 opacity-0"
                        >
                            <div
                                v-show="showNotificationDropdown"
                                class="relative !top-12 w-[22rem] !min-w-0 max-w-[calc(100vw-1rem)] !origin-top-right overflow-hidden rounded-lg !border-t-0 !p-0 !shadow-[0_0_0_1px_var(--surface-border),0_10px_30px_-12px_rgba(0,0,0,0.18),0_4px_10px_-4px_rgba(0,0,0,0.08)]"
                            >
                                <div class="flex items-center justify-between border-b border-[color:var(--surface-border)] px-3.5 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[13px] font-semibold text-[color:var(--text-color)]">Notifications</span>
                                        <span
                                            v-if="unreadCount > 0"
                                            class="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-[var(--primary-color)] px-[5px] text-[10px] font-semibold leading-none text-[color:var(--primary-color-text)]"
                                        >
                                            {{ unreadCount }}
                                        </span>
                                    </div>
                                    <button
                                        v-if="notifications.length > 0"
                                        type="button"
                                        class="cursor-pointer rounded-md px-[7px] py-[3px] text-[11px] font-medium text-red-500 transition-colors duration-100 hover:bg-red-500/10"
                                        @click="clear"
                                    >
                                        Clear all
                                    </button>
                                </div>

                                <ul
                                    class="m-0 max-h-[400px] list-none overflow-y-auto p-1 [scrollbar-color:var(--surface-border)_transparent] [scrollbar-width:thin]"
                                >
                                    <li v-for="notif in notifications" :key="notif.id">
                                        <component
                                            :is="!isDelete(notif.message) ? Link : 'div'"
                                            :href="!isDelete(notif.message) ? route('task.show', notif.task_id) : null"
                                            class="flex cursor-pointer items-start gap-2.5 rounded-lg px-2.5 py-2.5 text-[color:var(--text-color)] no-underline transition-colors duration-100 hover:bg-[var(--surface-hover)]"
                                            :class="{ 'bg-[var(--surface-hover)]': !notif.is_read }"
                                            @click="readNotification(notif.id)"
                                        >
                                            <span
                                                class="mt-px flex h-[26px] w-[26px] shrink-0 items-center justify-center rounded-[7px]"
                                                :class="notifIconClasses(notif.message)"
                                            >
                                                <i :class="getNotifIcon(notif.message)" class="text-[0.65rem]"></i>
                                            </span>
                                            <span class="min-w-0 flex-1 break-words text-[12.5px] leading-[1.55] text-[color:var(--text-color)]">
                                                {{ notif.message }}
                                            </span>
                                            <span
                                                v-if="!notif.is_read"
                                                class="mt-[5px] h-[7px] w-[7px] shrink-0 rounded-full bg-[var(--primary-color)]"
                                            ></span>
                                        </component>
                                    </li>

                                    <li
                                        v-if="notifications.length === 0"
                                        class="flex flex-col items-center justify-center gap-2 px-4 py-24 text-sm text-[color:var(--text-color-secondary)]"
                                    >
                                        <i class="pi pi-bell-slash text-2xl opacity-20"></i>
                                        <span>You're all caught up</span>
                                    </li>
                                </ul>
                            </div>
                        </Transition>
                    </li>

                    <!-- User Menu -->
                    <li ref="userMenuContainer" class="relative">
                        <button
                            type="button"
                            :class="{ 'bg-[var(--topbar-item-hover-bg)]': showUserMenu }"
                            aria-label="Account menu"
                            @click="
                                () => {
                                    showUserMenu = !showUserMenu;
                                    showNotificationDropdown = false;
                                }
                            "
                        >
                            <i class="pi pi-user"></i>
                        </button>

                        <Transition
                            enter-active-class="transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)] motion-reduce:transition-none"
                            enter-from-class="-translate-y-1.5 scale-95 opacity-0"
                            enter-to-class="translate-y-0 scale-100 opacity-100"
                            leave-active-class="transition duration-150 ease-[cubic-bezier(0.4,0,1,1)] motion-reduce:transition-none"
                            leave-from-class="translate-y-0 scale-100 opacity-100"
                            leave-to-class="-translate-y-1.5 scale-95 opacity-0"
                        >
                            <div
                                v-show="showUserMenu"
                                class="relative !top-12 w-[210px] !min-w-0 !origin-top-right overflow-hidden rounded-lg !border-t-0 !p-0 !shadow-[0_0_0_1px_var(--surface-border),0_10px_30px_-12px_rgba(0,0,0,0.18),0_4px_10px_-4px_rgba(0,0,0,0.08)]"
                            >
                                <div class="flex items-center gap-2.5 bg-[var(--surface-hover)] px-3.5 py-3">
                                    <Avatar v-if="avatarImage" :image="avatarImage" shape="circle" class="shrink-0" />
                                    <Avatar v-else :label="avatarLabel" shape="circle" class="shrink-0 bg-primary text-white" />
                                    <div class="min-w-0">
                                        <p
                                            class="overflow-hidden text-ellipsis whitespace-nowrap text-[13px] font-semibold text-[color:var(--text-color)]"
                                        >
                                            {{ userName }}
                                        </p>
                                    </div>
                                </div>

                                <div class="h-px bg-[var(--surface-border)]"></div>

                                <ul class="m-0 list-none p-1">
                                    <li>
                                        <Link
                                            href="/settings"
                                            class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3.5 py-2.5 text-[13px] text-[color:var(--text-color)] no-underline transition-colors duration-100 hover:bg-[var(--surface-hover)]"
                                        >
                                            <i class="pi pi-sliders-h text-[0.85rem]"></i>
                                            <span>Settings</span>
                                        </Link>
                                    </li>
                                    <li>
                                        <a
                                            class="flex cursor-pointer items-center gap-2.5 rounded-lg px-3.5 py-2.5 text-[13px] text-[color:var(--text-color)] no-underline transition-colors duration-100 hover:bg-red-500/[0.06] hover:text-red-500"
                                            @click="logout"
                                        >
                                            <i class="pi pi-sign-out text-[0.85rem]"></i>
                                            <span>Log out</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </Transition>
                    </li>

                    <li>
                        <button type="button" class="layout-config-button" @click="onConfigSidebarToggle">
                            <i class="pi pi-palette"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
