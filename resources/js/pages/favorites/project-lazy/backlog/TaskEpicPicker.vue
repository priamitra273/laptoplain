<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { BacklogEpic, BacklogTask } from './types';

interface Props {
    task: BacklogTask;
    epics: BacklogEpic[];
    canAct?: boolean;
}

const props = withDefaults(defineProps<Props>(), { canAct: false });

const emit = defineEmits<{
    assign: [task: BacklogTask, epicId: string | null];
    createEpic: [];
}>();

const epic = computed(() => props.epics.find((candidate) => String(candidate.id) === String(props.task.parent_id)) ?? null);

const picking = ref(false);

const menuItems = computed(() => [
    [
        { label: 'Change epic', icon: 'i-lucide-pencil', onSelect: () => (picking.value = true) },
        { label: 'View epic', icon: 'i-lucide-eye', onSelect: () => router.visit(route('task.show', String(epic.value?.id))) },
    ],
    [{ label: 'Remove from epic', icon: 'i-lucide-x', color: 'error' as const, onSelect: () => emit('assign', props.task, null) }],
]);

const onPick = (epicId: string) => {
    picking.value = false;

    if (String(epicId) === String(epic.value?.id)) return;

    emit('assign', props.task, epicId);
};
</script>

<template>
    <div class="hidden w-36 shrink-0 sm:block">
        <USelectMenu
            v-if="picking"
            :items="epics"
            label-key="title"
            value-key="id"
            placeholder="Select epic"
            size="xs"
            open
            class="w-full"
            @update:open="(value: boolean) => !value && (picking = false)"
            @update:model-value="onPick"
        >
            <template #empty>
                <p class="p-2 text-xs text-muted">No epics in this project yet.</p>
            </template>
        </USelectMenu>

        <UDropdownMenu v-else-if="epic && canAct" :items="menuItems" :content="{ align: 'start' }" @click.stop>
            <button
                type="button"
                class="flex w-full min-w-0 items-center gap-1 text-left text-xs text-muted hover:text-highlighted"
                :title="epic.title"
            >
                <UIcon name="i-lucide-bolt" class="size-3.5 shrink-0 text-purple-500" />
                <span class="truncate">{{ epic.title }}</span>
            </button>
        </UDropdownMenu>

        <div v-else-if="epic" class="flex min-w-0 items-center gap-1 text-xs text-muted" :title="epic.title">
            <UIcon name="i-lucide-bolt" class="size-3.5 shrink-0 text-purple-500" />
            <span class="truncate">{{ epic.title }}</span>
        </div>

        <UButton
            v-else-if="canAct && epics.length"
            icon="i-lucide-plus"
            label="Epic"
            color="neutral"
            variant="ghost"
            size="xs"
            class="opacity-0 group-hover:opacity-100"
            @click.stop="picking = true"
        />

        <UButton
            v-else-if="canAct"
            icon="i-lucide-plus"
            label="Epic"
            color="neutral"
            variant="ghost"
            size="xs"
            class="opacity-0 group-hover:opacity-100"
            title="No epics yet. Create one first."
            @click.stop="emit('createEpic')"
        />
    </div>
</template>
