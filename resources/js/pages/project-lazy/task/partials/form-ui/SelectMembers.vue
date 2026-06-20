<script setup lang="ts">
import Label from '@/components/Label.vue';
import type { SlimUser } from '@/pages/project-lazy';

interface Props {
    options: SlimUser[];
    disabled?: boolean;
}

const props = defineProps<Props>();

const modelValue = defineModel<SlimUser[]>('modelValue', { default: () => [] });
</script>

<template>
    <div class="grid grid-cols-4 gap-4">
        <Label value="Assignees" icon="Users" />
        <div class="col-span-3">
            <MultiSelect
                v-model="modelValue"
                display="chip"
                :options="props.options"
                optionLabel="name"
                filter
                :showClear="false"
                placeholder="Select Member"
                class="w-full !border-0 !shadow-none hover:bg-surface-100 dark:hover:bg-surface-900"
                :disabled="props.disabled"
                :pt="{
                    label: {
                        class: 'flex-wrap',
                    },
                    dropdown: {
                        class: '!w-0',
                    },
                }"
            >
                <template #chip="{ value, removeCallback }">
                    <Chip :label="value.name" :image="value.avatar_url" removable @remove="(event) => removeCallback(event, value)" />
                </template>
            </MultiSelect>
        </div>
    </div>
</template>
