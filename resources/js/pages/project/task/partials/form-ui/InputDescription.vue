<script setup lang="ts">
interface Props {
    error?: string | null;
    disabled?: boolean;
}

const props = defineProps<Props>();
const description = defineModel<string | undefined>('modelValue');
</script>

<template>
    <div>
        <div v-if="props.disabled" class="min-h-[200px] rounded-md border bg-surface-50 p-3 dark:bg-surface-900" v-html="description"></div>
        <Editor v-else v-model="description" editorStyle="height: 150px" :class="{ 'p-invalid': props.error }">
            <template #toolbar>
                <span class="ql-formats">
                    <button class="ql-bold"></button>
                    <button class="ql-italic"></button>
                    <button class="ql-underline"></button>
                    <button class="ql-strike"></button>
                </span>
                <span class="ql-formats">
                    <select class="ql-header">
                        <option value="1">Heading 1</option>
                        <option value="2">Heading 2</option>
                        <option value="3">Heading 3</option>
                        <option selected></option>
                    </select>
                </span>
                <span class="ql-formats">
                    <button class="ql-list" value="ordered"></button>
                    <button class="ql-list" value="bullet"></button>
                </span>
                <span class="ql-formats">
                    <button class="ql-link"></button>
                    <button class="ql-code-block"></button>
                </span>
                <span class="ql-formats">
                    <button class="ql-clean"></button>
                </span>
            </template>
        </Editor>
        <small v-if="props.error" class="p-error text-red-500">{{ props.error }}</small>
    </div>
</template>
