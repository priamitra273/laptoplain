<script setup lang="ts">
import Label from '@/components/Label.vue';
import { ref } from 'vue';

interface TagOption {
    id: string;
    name: string;
    severity: string;
}

interface Props {
    options: TagOption[];
    error?: string | null;
    disabled?: boolean;
}

const props = defineProps<Props>();

const modelValue = defineModel<TagOption[]>('modelValue', { default: () => [] });

const filteredTags = ref<TagOption[]>([]);

const search = (event: any) => {
    const query = event.query.trim().toLowerCase();

    if (!query.length) {
        filteredTags.value = props.options.filter((tag) => !modelValue.value.some((sel) => sel.id === tag.id));
        return;
    }

    let result = props.options
        .filter((tag) => tag.name.toLowerCase().includes(query))
        .filter((tag) => !modelValue.value.some((sel) => sel.id === tag.id));

    const existsInOptions = props.options.some((tag) => tag.name.toLowerCase() === query);
    const existsInSelected = modelValue.value.some((tag) => tag.name.toLowerCase() === query);

    if (!existsInOptions && !existsInSelected) {
        result = [{ id: '', name: event.query.trim(), severity: '' }, ...result];
    }

    filteredTags.value = result;
};

const addNewTag = (event: any) => {
    const inputValue = event.target.value.trim();
    if (!inputValue) return;

    const normalized = inputValue.toLowerCase();
    const existsInOptions = props.options.some((tag) => tag.name.toLowerCase() === normalized);
    const existsInSelected = modelValue.value.some((tag) => tag.name.toLowerCase() === normalized);

    if (existsInOptions || existsInSelected) {
        event.target.value = '';
        return;
    }

    const severities = ['primary', 'secondary', 'success', 'info', 'warn', 'danger', 'contrast'];
    const randomSeverity = severities[Math.floor(Math.random() * severities.length)];

    modelValue.value.push({ id: '', name: inputValue, severity: randomSeverity });
    event.target.value = '';
};
</script>

<template>
    <div class="grid grid-cols-4 gap-4">
        <Label value="Tags" icon="Tags" />
        <div class="col-span-3">
            <AutoComplete
                v-model="modelValue"
                multiple
                optionLabel="name"
                :suggestions="filteredTags"
                fluid
                :disabled="props.disabled"
                @complete="search"
                @keydown.enter.prevent="addNewTag"
            >
                <template #option="slotProps">
                    <div class="flex items-center gap-2">
                        <span v-if="!slotProps.option.id" class="font-bold">{{ slotProps.option.name }}</span>
                        <span v-else>{{ slotProps.option.name }}</span>
                    </div>
                </template>
            </AutoComplete>
            <small v-if="props.error" class="p-error text-red-500">{{ props.error }}</small>
        </div>
    </div>
</template>
