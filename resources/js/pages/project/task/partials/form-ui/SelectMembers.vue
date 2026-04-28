<script setup lang="ts">
interface MemberOption {
    id: string;
    name: string;
}

interface Props {
    options: MemberOption[];
    disabled?: boolean;
}

const props = defineProps<Props>();

const modelValue = defineModel<MemberOption[]>('modelValue', { default: () => [] });
</script>

<template>
    <div class="flex flex-col">
        <label class="font-semibold">Assigned Member</label>
        <MultiSelect
            :disabled="props.disabled"
            v-model="modelValue"
            display="chip"
            :options="props.options"
            optionLabel="name"
            filter
            :showClear="false"
            placeholder="Select Member"
            :maxSelectedLabels="3"
            class="w-full"
        />
    </div>
</template>
