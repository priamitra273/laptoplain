<script setup lang="ts">
import axios from 'axios';
import moment from 'moment';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import ProgressSpinner from 'primevue/progressspinner';
import { computed, ref, watch } from 'vue';

interface ActivityCauser {
    id: number;
    name: string;
    avatar_url: string | null;
}

interface ActivityChangedField {
    field: string;
    old_value: string | null;
    new_value: string | null;
    has_value: boolean;
}

interface Activity {
    id: number;
    event: string;
    causer: ActivityCauser | null;
    changed_fields: ActivityChangedField[];
    created_at: string;
}

interface Props {
    visible: boolean;
    taskId: string;
    taskTitle: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'update:visible', value: boolean): void }>();

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
    for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return `hsl(${Math.abs(hash) % 360}, 65%, 55%)`;
};

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
        error.value = 'Failed to load activity log.';
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
        :header="`History Log — ${taskTitle}`"
        :style="{ width: '480px', maxWidth: '95vw' }"
        :draggable="false"
        dismissableMask
        :pt="{ content: { style: 'overflow-y: auto; max-height: 70vh; padding: 1.25rem;' } }"
    >
        <!-- Stats bar -->
        <div class="mb-4 flex items-center gap-4 rounded-lg bg-surface-100 px-4 py-3 dark:bg-surface-800">
            <div class="flex items-center gap-1.5 text-sm">
                <i class="pi pi-history text-primary-500" />
                <span class="font-semibold">{{ activities.length }}</span>
                <span class="text-surface-500">updates</span>
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
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex flex-col items-center justify-center gap-3 py-16">
            <ProgressSpinner style="width: 40px; height: 40px" strokeWidth="4" />
            <span class="text-sm text-surface-500">Loading...</span>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="flex flex-col items-center justify-center gap-3 py-16 text-red-500">
            <i class="pi pi-exclamation-circle text-3xl" />
            <span class="text-sm">{{ error }}</span>
            <Button label="Retry" icon="pi pi-refresh" size="small" @click="fetchActivities" severity="secondary" />
        </div>

        <!-- Empty -->
        <div v-else-if="!activities.length" class="flex flex-col items-center justify-center gap-3 py-16 text-surface-400">
            <i class="pi pi-inbox text-3xl" />
            <span class="text-sm">No activity recorded yet.</span>
        </div>

        <!-- Activity list -->
        <div v-else class="flex flex-col">
            <div v-for="(item, index) in activities" :key="item.id" class="flex gap-3">
                <!-- Timeline dot + connector -->
                <div class="flex flex-col items-center pt-1">
                    <div class="h-2.5 w-2.5 flex-shrink-0 rounded-full bg-blue-500 ring-2 ring-blue-200 dark:ring-blue-900" />
                    <div v-if="index < activities.length - 1" class="mt-1 w-px flex-1 bg-surface-200 dark:bg-surface-700" style="min-height: 2rem" />
                </div>

                <!-- Row content -->
                <div class="mb-4 min-w-0 flex-1">
                    <!-- User + time -->
                    <div class="mb-2 flex items-center justify-between gap-2">
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
                            <span class="text-sm font-semibold text-surface-800 dark:text-surface-100">
                                {{ item.causer?.name ?? 'System' }}
                            </span>
                        </div>
                        <span class="flex-shrink-0 text-xs text-surface-400" :title="formatDate(item.created_at)">
                            {{ timeAgo(item.created_at) }}
                        </span>
                    </div>

                    <!-- Changed fields -->
                    <div class="flex flex-col gap-1.5">
                        <div v-for="f in item.changed_fields" :key="f.field" class="flex flex-wrap items-center gap-1.5">
                            <span class="text-xs text-surface-500">updated</span>
                            <span
                                class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-900/20 dark:text-blue-400"
                            >
                                {{ f.field }}
                            </span>

                            <!-- Status: tampilkan old → new value -->
                            <template v-if="f.has_value">
                                <span
                                    v-if="f.old_value"
                                    class="rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-600 line-through dark:bg-red-900/20 dark:text-red-400"
                                >
                                    {{ f.old_value }}
                                </span>
                                <span v-else class="text-xs text-surface-400">—</span>
                                <i class="pi pi-arrow-right text-xs text-surface-300" />
                                <span
                                    v-if="f.new_value"
                                    class="rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/20 dark:text-green-400"
                                >
                                    {{ f.new_value }}
                                </span>
                                <span v-else class="text-xs text-surface-400">—</span>
                            </template>
                        </div>
                    </div>

                    <!-- Exact date -->
                    <p class="mt-1.5 text-xs text-surface-400">{{ formatDate(item.created_at) }}</p>
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Close" icon="pi pi-times" @click="closeModal" severity="secondary" />
        </template>
    </Dialog>
</template>
