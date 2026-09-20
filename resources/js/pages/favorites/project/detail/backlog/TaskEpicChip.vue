<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { BacklogEpic, BacklogTask } from './types';

const props = withDefaults(
    defineProps<{
        task: BacklogTask;
        epics: BacklogEpic[];
        canAct?: boolean;
    }>(),
    { canAct: false },
);

const emit = defineEmits<{
    assign: [epicId: string | null];
}>();

const epic = computed(() => props.epics.find((candidate) => candidate.id === props.task.parent_id) ?? null);

/** `USelectMenu` tidak bisa membawa `null` sebagai nilai, jadi "lepas epic" diwakili sentinel ini. */
const NO_EPIC = '__no_epic__';

const pickerItems = computed<Pick<BacklogEpic, 'id' | 'title'>[]>(() =>
    epic.value ? [{ id: NO_EPIC, title: 'No epic' }, ...props.epics] : [...props.epics],
);

const onPick = (value: string) => {
    const epicId = value === NO_EPIC ? null : value;

    if (epicId !== (epic.value?.id ?? null)) {
        emit('assign', epicId);
    }
};
</script>

<template>
    <div v-if="canAct" class="flex shrink-0 items-center gap-0.5">
        <USelectMenu
            :model-value="epic?.id"
            :items="pickerItems"
            label-key="title"
            value-key="id"
            size="xs"
            class="shrink-0"
            :ui="{
                base: 'bg-transparent px-1.5 ring-0 shadow-none hover:bg-elevated',
                trailing: 'hidden',
                content: 'w-auto min-w-64',
            }"
            :aria-label="epic ? `Epic: ${epic.title}` : 'Select epic'"
            @update:model-value="onPick"
        >
            <span
                v-if="epic"
                class="text-primary flex max-w-40 items-center gap-1 text-xs"
                :title="epic.title"
            >
                <UIcon
                    name="i-lucide-layers"
                    class="size-3 shrink-0"
                />
                <span class="truncate">{{ epic.title }}</span>
            </span>

            <span
                v-else
                class="text-muted flex items-center gap-1 text-xs"
            >
                <UIcon
                    name="i-lucide-plus"
                    class="size-3 shrink-0"
                />
                Epic
            </span>

            <template #empty>
                <p class="text-muted p-2 text-xs">
                    No epics in this project yet.
                </p>
            </template>
        </USelectMenu>

        <Link
            v-if="epic"
            :href="route('task.show', { task: epic.id })"
            class="text-muted hover:bg-elevated hover:text-default inline-flex size-7 items-center justify-center rounded-md"
            :aria-label="`Open Epic: ${epic.title}`"
            @click.stop
        >
            <UIcon
                name="i-lucide-external-link"
                class="size-3.5"
            />
        </Link>
    </div>

    <Link
        v-else-if="epic"
        :href="route('task.show', { task: epic.id })"
        class="text-primary hover:text-primary/80 flex max-w-40 items-center gap-1 text-xs"
        :title="epic.title"
        @click.stop
    >
        <UIcon
            name="i-lucide-layers"
            class="size-3 shrink-0"
        />
        <span class="truncate">{{ epic.title }}</span>
    </Link>

    <span v-else class="text-dimmed">—</span>
</template>
