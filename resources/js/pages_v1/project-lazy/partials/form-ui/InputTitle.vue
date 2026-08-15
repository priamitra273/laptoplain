<script setup lang="ts">
interface Props {
    error?: string | null;
    disabled?: boolean;
}

const props = defineProps<Props>();
const title = defineModel<string | null>('modelValue');
</script>

<template>
    <div>
        <InputText
            v-model="title"
            unstyled
            class="w-full border-b bg-transparent py-2 focus-visible:border-primary-500 focus-visible:outline-none dark:border-b-gray-500 dark:focus-visible:border-primary-400"
            placeholder="Task title"
            :class="{ 'p-invalid': error }"
            :disabled="props.disabled"
        />

        <small v-if="error" class="p-error text-red-500">
            {{ error }}
        </small>
    </div>
</template>
