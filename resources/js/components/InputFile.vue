<script setup lang="ts">
import { computed, ref } from 'vue';

interface Props {
    modelValue: File | File[] | null;
    multiple?: boolean;
    accept?: string;
}

interface Emits {
    (e: 'update:modelValue', value: File | File[] | null): void;
    (e: 'change', value: Event): void;
}

const props = defineProps<Props>();
const emits = defineEmits<Emits>();

const file = ref<HTMLInputElement | null>(null);

const fileLabel = computed<string | null>(() => {
    if (!props.modelValue) return null;
    if (Array.isArray(props.modelValue)) {
        return `${props.modelValue.length} file dipilih`;
    }
    return props.modelValue.name;
});

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = target.files;

    emits('change', event);

    if (!files || files.length === 0) {
        emits('update:modelValue', null);
        return;
    }

    if (props.multiple) {
        emits('update:modelValue', Array.from(files));
    } else {
        emits('update:modelValue', files[0]);
    }
};
</script>

<template>
    <InputGroup @click="file?.click()">
        <InputText :value="fileLabel" placeholder="Tidak ada file dipilih" variant="filled" readonly class="cursor-pointer" />
        <input ref="file" type="file" id="file" class="hidden" :multiple="multiple" :accept="accept" @change="handleFileSelect" />
        <Button label="Pilih File" />
    </InputGroup>
</template>
