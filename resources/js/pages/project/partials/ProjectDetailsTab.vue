<script setup lang="ts">
import moment from 'moment';
import Editor from 'primevue/editor';
import { ref, watch } from 'vue';
import type { Project } from '../index';

interface Props {
    project: Project;
    canEdit: boolean;
}

interface Emits {
    (e: 'update', value: string, field: string): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const editMode = ref(false);
const localDescription = ref(props.project.description || '');

watch(
    () => props.project.description,
    (newVal) => {
        localDescription.value = newVal || '';
    },
);

const enableEdit = () => {
    if (props.canEdit) {
        editMode.value = true;
    }
};

const onDescriptionBlur = () => {
    if (localDescription.value !== (props.project.description || '')) {
        emit('update', localDescription.value, 'description');
    }
    editMode.value = false;
};
</script>

<template>
    <div class="grid grid-cols-1 gap-8 py-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h3 class="mb-3 text-sm font-semibold uppercase text-surface-500 dark:text-surface-400">Description</h3>
            <div
                v-if="!editMode"
                @click="enableEdit"
                :class="{
                    'cursor-pointer rounded p-2 hover:bg-surface-50 dark:hover:bg-surface-800': canEdit,
                }"
            >
                <div
                    class="prose dark:prose-invert max-w-none break-words text-surface-700 dark:text-surface-300"
                    v-html="project.description || '<p class=\'text-surface-500 italic\'>No description provided. Click to add.</p>'"
                />
            </div>

            <div v-else class="relative" @click.stop>
                <div class="fixed inset-0 z-10" @click="onDescriptionBlur"></div>
                <div class="relative z-20">
                    <Editor v-model="localDescription" editorStyle="height: 200px">
                        <template #toolbar>
                            <span class="ql-formats">
                                <button class="ql-bold"></button>
                                <button class="ql-italic"></button>
                                <button class="ql-underline"></button>
                                <button class="ql-list" value="ordered"></button>
                                <button class="ql-list" value="bullet"></button>
                            </span>
                        </template>
                    </Editor>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            <div>
                <h3 class="mb-3 text-sm font-semibold uppercase text-surface-500 dark:text-surface-400">Details</h3>
                <div class="flex flex-col gap-3">
                    <div class="flex items-start justify-between">
                        <span class="text-sm text-surface-600 dark:text-surface-400">Created</span>
                        <span class="text-sm font-medium text-surface-800 dark:text-surface-200">
                            {{ moment(project.created_at).format('MMM DD, YYYY') }}
                        </span>
                    </div>
                    <div class="flex items-start justify-between">
                        <span class="text-sm text-surface-600 dark:text-surface-400">Updated</span>
                        <span class="text-sm font-medium text-surface-800 dark:text-surface-200">{{ moment(project.updated_at).fromNow() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
