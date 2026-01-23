<script setup lang="ts">
import axios from 'axios';
import { Notification } from '@/types';
import { onBeforeUnmount, onMounted, provide, ref } from 'vue';

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

const markAsRead = async (notificationId: string) => {
    try {
        await axios.post(route('notifications.read', { notification: notificationId }));
        const notif = notifications.value.find((n) => n.id === notificationId);
        if (notif) {
            notif.is_read = true
            unreadCount.value--
        }
    } catch (error) {
        console.error(error);
    }
}

const clearNotifications = async () => {
    try {
        await axios.post(route('notifications.clear'));
        notifications.value = [];
        unreadCount.value = 0
    } catch (error) {
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
