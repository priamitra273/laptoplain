<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { CheckCheck } from 'lucide-vue-next';
import { Head, Link } from '@inertiajs/vue3';
import moment from 'moment';
import { computed, inject, Ref, ref, watch } from 'vue';

interface NotificationItem {
    id: string;
    message: string;
    task_id: string;
    is_read: boolean;
    is_mention: boolean;
    created_at: string | null;
}

interface NotificationStore {
    notifications: Ref<{ id: string; is_read: boolean }[]>;
    markAsRead: (notificationId: string) => Promise<void>;
    markAllAsRead: () => Promise<void>;
}

interface Props {
    notifications: NotificationItem[];
}

const props = withDefaults(defineProps<Props>(), {
    notifications: () => [],
});

const notificationStore = inject<NotificationStore>('notifications');

const items = ref<NotificationItem[]>(props.notifications.map((n) => ({ ...n })));

watch(
    () => props.notifications,
    (next) => {
        items.value = next.map((n) => ({ ...n }));
    },
);

const activeTab = ref<'all' | 'unread' | 'mentions'>('all');

const unreadNotifications = computed(() => items.value.filter((n) => !n.is_read));
const mentionNotifications = computed(() => items.value.filter((n) => n.is_mention));

const counts = computed(() => ({
    all: items.value.length,
    unread: unreadNotifications.value.length,
    mentions: mentionNotifications.value.length,
}));

const visibleNotifications = computed(() => {
    if (activeTab.value === 'unread') {
        return unreadNotifications.value;
    }
    if (activeTab.value === 'mentions') {
        return mentionNotifications.value;
    }
    return items.value;
});

const GROUP_ORDER = ['Today', 'Yesterday', 'This Week', 'This Month', 'Older'] as const;

type GroupLabel = (typeof GROUP_ORDER)[number];

function groupLabelFor(date: string | null): GroupLabel {
    if (!date) {
        return 'Older';
    }

    const created = moment(date);
    const now = moment();

    if (created.isSame(now, 'day')) {
        return 'Today';
    }
    if (created.isSame(now.clone().subtract(1, 'day'), 'day')) {
        return 'Yesterday';
    }
    if (created.isSame(now, 'isoWeek')) {
        return 'This Week';
    }
    if (created.isSame(now, 'month')) {
        return 'This Month';
    }

    return 'Older';
}

const groupedNotifications = computed(() => {
    const groups = new Map<GroupLabel, NotificationItem[]>();

    for (const notif of visibleNotifications.value) {
        const label = groupLabelFor(notif.created_at);
        const bucket = groups.get(label) ?? [];
        bucket.push(notif);
        groups.set(label, bucket);
    }

    return GROUP_ORDER.filter((label) => groups.has(label)).map((label) => ({
        label,
        items: groups.get(label) as NotificationItem[],
    }));
});

const readNotification = (notif: NotificationItem): void => {
    if (notif.is_read) {
        return;
    }

    notif.is_read = true;
    notificationStore?.markAsRead(notif.id);
};

const markAllAsRead = (): void => {
    if (counts.value.unread === 0) {
        return;
    }

    items.value.forEach((n) => {
        n.is_read = true;
    });
    notificationStore?.markAllAsRead();
};

const tabs = computed(() => [
    { value: 'all' as const, label: 'All', count: counts.value.all },
    { value: 'unread' as const, label: 'Unread', count: counts.value.unread },
    { value: 'mentions' as const, label: 'Mentions', count: counts.value.mentions },
]);

function relativeTime(date: string | null): string {
    return date ? moment(date).fromNow() : '';
}

function isDelete(message: string): boolean {
    return message.toLowerCase().includes('delete');
}

function getNotifIcon(message: string): string {
    const m = message.toLowerCase();
    if (m.includes('delete')) return 'pi pi-trash';
    if (m.includes('create') || m.includes('new')) return 'pi pi-plus-circle';
    if (m.includes('mention')) return 'pi pi-at';
    return 'pi pi-pencil';
}

function notifIconClasses(message: string): string {
    const m = message.toLowerCase();
    if (m.includes('delete')) return 'bg-red-500/10 text-red-500';
    if (m.includes('create') || m.includes('new')) return 'bg-emerald-500/10 text-emerald-500';
    if (m.includes('mention')) return 'bg-violet-500/10 text-violet-500';
    return 'bg-blue-500/10 text-blue-500';
}
</script>

<template>
    <Head title="Notifications" />

    <AppLayout>
        <div class="flex flex-col gap-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <Heading title="Notifications" description="View all your notifications in one place" />
                <Button label="Mark all as read" severity="primary" outlined size="small" :disabled="counts.unread === 0" @click="markAllAsRead">
                    <template #icon>
                        <CheckCheck class="size-4" />
                    </template>
                </Button>
            </div>

            <Tabs v-model:value="activeTab" class="!bg-transparent">
                <TabList
                    :pt="{
                        root: { class: '!bg-transparent' },
                        content: { class: '!bg-transparent' },
                        tabList: { class: '!bg-transparent' },
                    }"
                >
                    <Tab v-for="tab in tabs" :key="tab.value" :value="tab.value" class="!bg-transparent !px-4">
                        <span class="flex items-center gap-2">
                            <span>{{ tab.label }}</span>
                            <span
                                class="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-[var(--surface-200)] px-[6px] text-[11px] font-semibold leading-none text-[color:var(--text-color-secondary)]"
                            >
                                {{ tab.count }}
                            </span>
                        </span>
                    </Tab>
                </TabList>

                <TabPanels class="!bg-transparent !px-0">
                    <TabPanel v-for="tab in tabs" :key="tab.value" :value="tab.value" class="!bg-transparent">
                        <div v-if="visibleNotifications.length > 0" class="flex flex-col gap-6 pt-2">
                            <section v-for="group in groupedNotifications" :key="group.label" class="flex flex-col gap-2">
                                <h3 class="px-1 text-xs font-semibold uppercase tracking-wide text-[color:var(--text-color-secondary)]">
                                    {{ group.label }}
                                </h3>

                                <Card
                                    v-for="notif in group.items"
                                    :key="notif.id"
                                    :class="{ 'ring-1 ring-[var(--primary-color)]/30': !notif.is_read }"
                                    :pt="{ body: { class: '!p-3' }, content: { class: '!p-0' } }"
                                >
                                    <template #content>
                                        <component
                                            :is="!isDelete(notif.message) ? Link : 'div'"
                                            :href="!isDelete(notif.message) ? route('task.show', notif.task_id) : null"
                                            class="flex cursor-pointer items-start gap-3 text-[color:var(--text-color)] no-underline"
                                            @click="readNotification(notif)"
                                        >
                                            <span
                                                class="mt-px flex h-7 w-7 shrink-0 items-center justify-center rounded-[7px]"
                                                :class="notifIconClasses(notif.message)"
                                            >
                                                <i :class="getNotifIcon(notif.message)" class="text-[0.7rem]"></i>
                                            </span>
                                            <span class="min-w-0 flex-1 break-words text-sm leading-[1.55] text-[color:var(--text-color)]">
                                                {{ notif.message }}
                                            </span>
                                            <span
                                                v-if="notif.created_at"
                                                class="mt-px shrink-0 whitespace-nowrap text-xs text-muted-foreground"
                                            >
                                                {{ relativeTime(notif.created_at) }}
                                            </span>
                                            <span
                                                v-if="!notif.is_read"
                                                class="mt-[6px] h-[7px] w-[7px] shrink-0 rounded-full bg-[var(--primary-color)]"
                                            ></span>
                                        </component>
                                    </template>
                                </Card>
                            </section>
                        </div>

                        <div
                            v-else
                            class="flex flex-col items-center justify-center gap-2 px-4 py-24 text-sm text-[color:var(--text-color-secondary)]"
                        >
                            <i class="pi pi-bell-slash text-2xl opacity-20"></i>
                            <span>You're all caught up</span>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </AppLayout>
</template>
