<script setup lang="ts">
interface ArchivedOption {
    label: string;
    value: boolean;
}

interface Props {
    progressError?: string | null;
    archivedDisabled?: boolean;
}

const props = defineProps<Props>();

const isArchived = defineModel<boolean>('isArchived', { default: false });
const progressValue = defineModel<number>('progressValue', { default: 0 });

const archivedOptions: ArchivedOption[] = [
    { label: 'No', value: false },
    { label: 'Yes', value: true },
];

const onProgressChange = (val: number | null) => {
    if (val === null) {
        progressValue.value = 0;
        return;
    }
    if (val > 100) {
        progressValue.value = 100;
    } else if (val < 0) {
        progressValue.value = 0;
    } else {
        progressValue.value = val;
    }
};
</script>

<template>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="font-semibold">Archived</label>
            <Select
                :disabled="props.archivedDisabled"
                class="w-full"
                v-model="isArchived"
                :options="archivedOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select Archived Status"
            />
        </div>
        <div>
            <label class="font-semibold">Progress (%)</label>
            <InputNumber
                v-model="progressValue"
                class="w-full"
                placeholder="0 - 100"
                :min="0"
                :max="100"
                showButtons
                disabled
                @update:modelValue="onProgressChange"
                :class="{ 'p-invalid': props.progressError }"
            />
            <small class="text-muted-color">Progress automatically follows task status</small>
            <small v-if="props.progressError" class="p-error text-red-500">{{ props.progressError }}</small>
        </div>
    </div>
</template>
