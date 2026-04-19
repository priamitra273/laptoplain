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
    <div class="space-y-2">
        <MentionEditor
            :model-value="modelValue"
            @update:model-value="onUpdate"
            :projectMembers="projectMembers"
            :height="height || '120px'"
            :placeholder="placeholder || 'Write your message...'"
        />
        <div class="flex gap-1">
            <Button
                :label="submitLabel || 'Save'"
                :icon="submitIcon || 'pi pi-check'"
                size="small"
                :severity="submitLabel === 'Reply' ? 'primary' : 'success'"
                :loading="loading"
                :disabled="loading"
                @click="onSubmit"
                class="shadow-sm hover:shadow"
            />
            <Button
                label="Cancel"
                size="small"
                severity="secondary"
                text
                :disabled="loading"
                @click="onCancel"
                class="dark:text-gray-300 dark:hover:bg-gray-700"
            />
        </div>
    </div>
</template>
