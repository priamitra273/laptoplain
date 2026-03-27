<script setup lang="ts">
interface Epic {
    id: string;
    title: string;
}

const props = defineProps<{
    epics: Epic[];
    selectedEpicId: string | null;
}>();

const emit = defineEmits<{ select: [id: string | null] }>();

const toggle = (id: string) => emit('select', props.selectedEpicId === id ? null : id);
</script>

<template>
    <div v-if="epics.length" class="flex flex-wrap items-center gap-2 border-b border-surface-100 px-1 py-2 dark:border-surface-700">
        <span class="shrink-0 text-xs font-medium text-surface-500">Epic</span>

        <!-- All -->
        <button
            class="rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors"
            :class="
                selectedEpicId === null
                    ? 'border-surface-800 bg-surface-800 text-white dark:bg-surface-100 dark:text-surface-900'
                    : 'border-surface-300 text-surface-600 hover:border-surface-500 dark:border-surface-600 dark:text-surface-400'
            "
            @click="emit('select', null)"
        >
            All
        </button>

        <!-- Per epic -->
        <button
            v-for="epic in epics"
            :key="epic.id"
            class="flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors"
            :class="
                selectedEpicId === epic.id
                    ? 'border-violet-600 bg-violet-600 text-white'
                    : 'border-surface-300 text-surface-600 hover:border-violet-400 dark:border-surface-600 dark:text-surface-400'
            "
            @click="toggle(epic.id)"
        >
            <i class="pi pi-bolt text-[10px]" />
            {{ epic.title }}
        </button>
    </div>
</template>
