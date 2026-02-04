<script setup lang="ts">
import axios from 'axios';
import { Notification } from '@/types';
import { onBeforeUnmount, onMounted, onUnmounted, provide, ref } from 'vue';

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
let es: EventSource | null = null;

onMounted(() => {
    es = new EventSource('/notifications/stream');

    es.addEventListener('init', (e) => {
        notifications.value = JSON.parse(e.data);
        unreadCount.value = notifications.value.filter((n) => !n.is_read).length;
    });

    es.addEventListener('notification', (e) => {
        const n = JSON.parse(e.data);
        notifications.value.unshift(n);
        unreadCount.value++;
    });
});

onBeforeUnmount(() => es?.close());
onUnmounted(() => console.log("unmounted"));

const markAsRead = async (notificationId: string) => {
    const currentCount = unreadCount.value
    const notif = notifications.value.find((n) => n.id === notificationId);
    try {
        unreadCount.value--
        if (notif) {
            notif.is_read = true
        }
        await axios.post(route('notifications.read', { encoded: notificationId }));
    } catch (error) {
        unreadCount.value = currentCount
        if (notif) {
            notif.is_read = false
        }
        console.error(error);
    }
}

const clearNotifications = async () => {
    const currentCount = unreadCount.value
    try {
        unreadCount.value = 0
        await axios.post(route('notifications.clear'));
        notifications.value = [];
    } catch (error) {
        unreadCount.value = currentCount
        console.error(error);
    }
}

provide('notifications', {
    notifications,
    unreadCount,
    markAsRead,
    clearNotifications
});
</script>

<template>
    <slot />
</template>
