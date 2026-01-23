<script setup lang="ts">
import { useLayout } from '@/composables/useLayouts';
import { Link, router } from '@inertiajs/vue3';
import { Ref } from 'vue';
import { inject, ref } from 'vue';
import { Notification } from '@/types';


interface NotificationStore {
    notifications: Ref<Notification[]>
    unreadCount: Ref<number>
    markAsRead: (notificationId: string) => Promise<void>
    clearNotifications: () => Promise<void>
}

const { onMenuToggle, onConfigSidebarToggle } = useLayout();

// Logout
async function logout() {
    router.post(route('logout'));
}

const notificationStore: NotificationStore | undefined = inject('notifications');

if (!notificationStore) {
    throw new Error('NotificationProvider is missing');
}

const {
    notifications,
    unreadCount,
    markAsRead,
    clearNotifications,
} = notificationStore;

const showNotificationDropdown = ref(false);
const showUserMenu = ref(false);


const handleNotificationClick = async (notificationId: string) => {
    markAsRead(notificationId);
    showNotificationDropdown.value = false;
}

const clear = async () => {
    await clearNotifications()
    showNotificationDropdown.value = false
}

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
                    <li class="relative">
                        <button
                            @click="
                                async () => {
                                    showNotificationDropdown = !showNotificationDropdown;
                                    showUserMenu = false;
                                }
                            "
                            class="relative p-2 focus:outline-none"
                        >
                            <i class="pi pi-bell text-xl"></i>
                            <span
                                v-if="unreadCount > 0"
                                class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-xs text-white"
                            >
                                {{ unreadCount }}
                            </span>
                        </button>

                        <div
                            v-show="showNotificationDropdown"
                            class="absolute right-0 z-50 mt-2 w-96 rounded-md border border-gray-200 bg-white shadow-lg"
                            @click.stop
                        >
                            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-2">
                                <span class="font-semibold">Notifications</span>
                                <button v-if="notifications.length > 0" class="text-xs text-red-600 hover:underline" @click="clear">
                                    Clear All
                                </button>
                            </div>

                            <ul class="m-0 max-h-64 list-none overflow-y-auto p-0">
                                <li
                                    v-for="notif in notifications"
                                    :key="notif.id"
                                    class="flex cursor-pointer px-4 py-2 hover:bg-gray-100"
                                    :class="{ 'font-bold': !notif.is_read }"
                                >
                                    <div 
                                        class="my-2 relative gap-2 flex flex-row"
                                        @click="handleNotificationClick(notif.id)"
                                    >
                                        <i class="pi pi-info-circle my-auto ml-1 mr-2"></i>
                                        <span>{{ notif.message }}</span>
                                    </div>
                                    <div 
                                        class="my-2 ml-auto"
                                        v-if="!notif.message.toLowerCase().includes('delete')"
                                    >
                                        <Link :href="route('task.show', notif.task_id)" @click.stop>
                                            <Button icon="pi pi-arrow-right" rounded aria-label="Filter" size="small" />
                                        </Link>
                                    </div>
                                </li>

                                <li v-if="notifications.length === 0" class="px-4 py-2 text-gray-500">No notifications</li>
                            </ul>
                        </div>
                    </li>

                    <!-- User Menu -->
                    <li class="relative">
                        <button
                            @click="
                                () => {
                                    showUserMenu = !showUserMenu;
                                    showNotificationDropdown = false;
                                }
                            "
                            class="p-2 focus:outline-none"
                        >
                            <i class="pi pi-user"></i>
                        </button>

                        <div
                            v-show="showUserMenu"
                            class="absolute right-0 z-50 mt-2 w-48 rounded-md border border-gray-200 bg-white shadow-lg"
                            @click.stop
                        >
                            <ul class="m-0 list-none p-0">
                                <li>
                                    <Link href="/settings" class="flex cursor-pointer gap-2 px-4 py-2 hover:text-primary">
                                        <i class="pi pi-fw pi-sliders-h text-lg"></i>
                                        <span>Settings</span>
                                    </Link>
                                </li>
                                <li>
                                    <a class="flex cursor-pointer gap-2 px-4 py-2 hover:text-primary" @click="logout">
                                        <i class="pi pi-fw pi-sign-out text-lg"></i>
                                        <span>Logout</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
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
