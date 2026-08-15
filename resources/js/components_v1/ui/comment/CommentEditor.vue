<script setup lang="ts">
import MentionEditor from '@/components/Mentioneditor.vue';
import Button from 'primevue/button';
import { EditorEmits, EditorProps } from './type';

const props = defineProps<EditorProps>();
const emit = defineEmits<EditorEmits>();

const onUpdate = (value: string) => {
    emit('update:modelValue', value);
};

const onSubmit = () => {
    emit('submit');
};

const onCancel = () => {
    emit('cancel');
};
</script>

<template>
    <div
        class="comment-editor overflow-hidden rounded-xl border border-surface-300 bg-surface-0 shadow-sm transition-colors focus-within:border-primary-400 dark:border-surface-600 dark:bg-surface-900"
    >
        <MentionEditor
            :model-value="modelValue"
            @update:model-value="onUpdate"
            :projectMembers="projectMembers"
            :height="height || '110px'"
            :placeholder="placeholder || 'Write your message...'"
        />
        <div class="flex items-center justify-end gap-2 border-t border-surface-200 px-3 py-2 dark:border-surface-700">
            <Button label="Cancel" size="small" severity="secondary" text :disabled="loading" @click="onCancel" />
            <Button
                :label="submitLabel || 'Save'"
                :icon="submitIcon || 'pi pi-check'"
                size="small"
                :severity="submitLabel === 'Reply' ? 'primary' : 'success'"
                :loading="loading"
                :disabled="loading"
                @click="onSubmit"
            />
        </div>
    </div>
</template>

<style scoped>
.comment-editor :deep(.ql-toolbar) {
    border: 0 !important;
    border-bottom: 1px solid var(--p-content-border-color) !important;
    border-radius: 0 !important;
    background: transparent !important;
}

.comment-editor :deep(.ql-container) {
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
}
</style>
