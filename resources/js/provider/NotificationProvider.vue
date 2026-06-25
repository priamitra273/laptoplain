<script setup lang="ts">
import axios from 'axios';
import { Notification } from '@/types';
import { onBeforeUnmount, onMounted, provide, ref } from 'vue';

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
let es: EventSource | null = null;

onBeforeUnmount(() => es?.close());

const connect = () => {
    if (es) return

    es = new EventSource('/notifications/stream')

    es.addEventListener('init', (e) => {
        notifications.value = JSON.parse(e.data);
        unreadCount.value = notifications.value.filter((n) => !n.is_read).length;
    })

    es.addEventListener('notification', (e) => {
        const n = JSON.parse(e.data);
        notifications.value.unshift(n);
        unreadCount.value++;
    })

    es.onerror = () => {
        es?.close()
        es = null
    }
}

const disconnect = () => {
    es?.close()
    es = null
    notifications.value = []
    unreadCount.value = 0
}

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

const markAllAsRead = async () => {
    const snapshot = notifications.value.map((n) => ({ ...n }))
    const currentCount = unreadCount.value
    try {
        notifications.value.forEach((n) => {
            n.is_read = true
        })
        unreadCount.value = 0
        await axios.post(route('notifications.read-all'));
    } catch (error) {
        notifications.value = snapshot
        unreadCount.value = currentCount
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
    markAllAsRead,
    clearNotifications,
    connect,
    disconnect
});
</script>

<template>
    <slot />
</template>
