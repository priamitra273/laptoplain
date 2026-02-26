<script setup lang="ts">
import axios from 'axios';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import Tag from 'primevue/tag';
import { computed, ref, watch } from 'vue';

interface ActivityChange {
    field: string;
    old_value: string | null;
    new_value: string | null;
}

interface ActivityCauser {
    id: number;
    name: string;
    avatar_url: string | null;
}

interface Activity {
    id: number;
    event: string;
    description: string;
    causer: ActivityCauser | null;
    changes: ActivityChange[];
    created_at: string;
}

interface Props {
    visible: boolean;
    taskId: string;
    taskTitle: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:visible', value: boolean): void;
}>();

const activities = ref<Activity[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

const formatDate = (iso: string) => moment(iso).format('DD MMM YYYY, HH:mm');
const timeAgo = (iso: string) => moment(iso).fromNow();

const getInitials = (name: string) =>
    name
        .split(' ')
        .map((w) => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

const getAvatarColor = (name: string) => {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    return `hsl(${Math.abs(hash) % 360}, 65%, 55%)`;
};

const eventSeverity = (event: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
    const map: Record<string, 'success' | 'info' | 'warn' | 'danger' | 'secondary'> = {
        created: 'success',
        updated: 'info',
        deleted: 'danger',
        restored: 'warn',
    };
    return map[event] ?? 'secondary';
};

const eventLabel = (event: string) => {
    const map: Record<string, string> = {
        created: 'Created',
        updated: 'Updated',
        deleted: 'Deleted',
        restored: 'Restored',
    };
    return map[event] ?? event.charAt(0).toUpperCase() + event.slice(1);
};

const eventIcon = (event: string) => {
    const map: Record<string, string> = {
        created: 'pi pi-plus-circle',
        updated: 'pi pi-pencil',
        deleted: 'pi pi-trash',
        restored: 'pi pi-refresh',
    };
    return map[event] ?? 'pi pi-circle';
};

const eventIconBg = (event: string) => {
    const map: Record<string, string> = {
        created: 'bg-green-500',
        updated: 'bg-blue-500',
        deleted: 'bg-red-500',
        restored: 'bg-yellow-500',
    };
    return map[event] ?? 'bg-surface-400';
};

const totalChanges = computed(() => activities.value.reduce((sum, a) => sum + a.changes.length, 0));

const uniqueActors = computed(() => {
    const seen = new Set<number>();
    return activities.value.filter((a) => a.causer && !seen.has(a.causer.id) && seen.add(a.causer.id)).map((a) => a.causer!);
});

const fetchActivities = async () => {
    if (!props.taskId) return;
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get(route('task.activities', { encoded: props.taskId }));
        activities.value = data.activities ?? [];
    } catch (err) {
        error.value = 'Failed to load activity log. Please try again.';
        console.error(err);
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.visible,
    (val) => {
        if (val) fetchActivities();
        else activities.value = [];
    },
);

const closeModal = () => emit('update:visible', false);
</script>

<template>
    <Dialog
        :visible="props.visible"
        @update:visible="closeModal"
        modal
        :header="`Activity Log — ${taskTitle}`"
        :style="{ width: '680px', maxWidth: '95vw' }"
        :draggable="false"
        dismissableMask
        :pt="{
            content: { style: 'overflow-y: auto; max-height: 70vh; padding: 1.25rem;' },
        }"
    >
        <!-- Stats bar -->
        <div class="mb-5 flex flex-wrap items-center gap-4 rounded-lg bg-surface-100 px-4 py-3 dark:bg-surface-800">
            <div class="flex items-center gap-1.5 text-sm">
                <i class="pi pi-history text-primary-500" />
                <span class="font-semibold">{{ activities.length }}</span>
                <span class="text-surface-500">activities</span>
            </div>
            <div class="flex items-center gap-1.5 text-sm">
                <i class="pi pi-list text-blue-500" />
                <span class="font-semibold">{{ totalChanges }}</span>
                <span class="text-surface-500">changes</span>
            </div>
            <div class="flex items-center gap-1.5 text-sm">
                <i class="pi pi-users text-green-500" />
                <span class="font-semibold">{{ uniqueActors.length }}</span>
                <span class="text-surface-500">contributors</span>
            </div>

            <div v-if="uniqueActors.length" class="ml-auto flex -space-x-2">
                <Avatar
                    v-for="actor in uniqueActors.slice(0, 5)"
                    :key="actor.id"
                    :image="actor.avatar_url ?? undefined"
                    :label="!actor.avatar_url ? getInitials(actor.name) : undefined"
                    shape="circle"
                    size="small"
                    :style="
                        !actor.avatar_url
                            ? { backgroundColor: getAvatarColor(actor.name), color: 'white', fontWeight: '600', border: '2px solid white' }
                            : { border: '2px solid white' }
                    "
                    v-tooltip.top="actor.name"
                />
                <div
                    v-if="uniqueActors.length > 5"
                    class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-surface-300 text-xs font-semibold dark:bg-surface-600"
                >
                    +{{ uniqueActors.length - 5 }}
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex flex-col items-center justify-center gap-3 py-16">
            <ProgressSpinner style="width: 48px; height: 48px" strokeWidth="4" />
            <span class="text-sm text-surface-500">Loading activity log...</span>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="flex flex-col items-center justify-center gap-3 py-16 text-red-500">
            <i class="pi pi-exclamation-circle text-4xl" />
            <span class="text-sm">{{ error }}</span>
            <Button label="Retry" icon="pi pi-refresh" size="small" @click="fetchActivities" severity="secondary" />
        </div>

        <!-- Empty -->
        <div v-else-if="!activities.length" class="flex flex-col items-center justify-center gap-3 py-16 text-surface-400">
            <i class="pi pi-inbox text-4xl" />
            <span class="text-sm">No activity recorded for this task yet.</span>
        </div>

        <!-- Activity list — custom timeline tanpa PrimeVue Timeline -->
        <div v-else class="flex flex-col gap-0">
            <div v-for="(item, index) in activities" :key="item.id" class="flex gap-4">
                <!-- Timeline marker + line -->
                <div class="flex flex-col items-center">
                    <div
                        class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-white shadow-sm"
                        :class="eventIconBg(item.event)"
                    >
                        <i :class="eventIcon(item.event)" class="text-sm" />
                    </div>
                    <!-- Connector line -->
                    <div
                        v-if="index < activities.length - 1"
                        class="w-0.5 flex-1 bg-surface-200 dark:bg-surface-700"
                        style="min-height: 1.5rem; margin-top: 2px; margin-bottom: 2px"
                    />
                </div>

                <!-- Content card -->
                <div
                    class="mb-4 min-w-0 flex-1 rounded-lg border border-surface-200 bg-white p-4 shadow-sm dark:border-surface-700 dark:bg-surface-900"
                >
                    <!-- Header -->
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <Avatar
                                v-if="item.causer"
                                :image="item.causer.avatar_url ?? undefined"
                                :label="!item.causer.avatar_url ? getInitials(item.causer.name) : undefined"
                                shape="circle"
                                size="small"
                                :style="
                                    !item.causer.avatar_url
                                        ? { backgroundColor: getAvatarColor(item.causer.name), color: 'white', fontWeight: '600' }
                                        : {}
                                "
                            />
                            <Avatar v-else icon="pi pi-user" shape="circle" size="small" />
                            <div class="flex flex-col leading-tight">
                                <span class="text-sm font-semibold text-surface-800 dark:text-surface-100">
                                    {{ item.causer?.name ?? 'System' }}
                                </span>
                                <span class="text-xs text-surface-400" :title="formatDate(item.created_at)">
                                    {{ timeAgo(item.created_at) }}
                                </span>
                            </div>
                        </div>
                        <Tag :value="eventLabel(item.event)" :severity="eventSeverity(item.event)" class="text-xs" />
                    </div>

                    <!-- No changes -->
                    <p v-if="!item.changes.length" class="text-sm italic text-surface-500">
                        {{ item.event === 'created' ? 'Task was created.' : item.description }}
                    </p>

                    <!-- Changes table -->
                    <div v-else class="w-full overflow-hidden rounded-md border border-surface-200 dark:border-surface-700">
                        <table class="w-full table-fixed text-sm">
                            <colgroup>
                                <col style="width: 25%" />
                                <col style="width: 37.5%" />
                                <col style="width: 37.5%" />
                            </colgroup>
                            <thead>
                                <tr class="bg-surface-50 dark:bg-surface-800">
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-surface-500">Field</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-surface-500">Before</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-surface-500">After</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(change, idx) in item.changes"
                                    :key="idx"
                                    class="border-t border-surface-100 dark:border-surface-700"
                                    :class="idx % 2 === 0 ? 'bg-white dark:bg-surface-900' : 'bg-surface-50 dark:bg-surface-800'"
                                >
                                    <td class="px-3 py-2 font-medium text-surface-700 dark:text-surface-200">
                                        {{ change.field }}
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            v-if="change.old_value"
                                            class="rounded bg-red-50 px-1.5 py-0.5 text-xs text-red-600 line-through decoration-red-400 dark:bg-red-900/20 dark:text-red-400"
                                        >
                                            {{ change.old_value }}
                                        </span>
                                        <span v-else class="text-xs italic text-surface-400">empty</span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            v-if="change.new_value"
                                            class="rounded bg-green-50 px-1.5 py-0.5 text-xs text-green-700 dark:bg-green-900/20 dark:text-green-400"
                                        >
                                            {{ change.new_value }}
                                        </span>
                                        <span v-else class="text-xs italic text-surface-400">empty</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Timestamp -->
                    <p class="mt-2 text-right text-xs text-surface-400">{{ formatDate(item.created_at) }}</p>
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Close" icon="pi pi-times" @click="closeModal" severity="secondary" />
        </template>
    </Dialog>
</template>
