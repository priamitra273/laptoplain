<script setup lang="ts">
import { ref } from 'vue';
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';

interface Props {
    src: string;
}

defineProps<Props>();

const emits = defineEmits<{ close: [file: File | null] }>();

const canvas = ref<HTMLCanvasElement | null>(null);

const onChange = ({ canvas: result }: { canvas: HTMLCanvasElement }) => {
    canvas.value = result;
};

const apply = () => {
    canvas.value?.toBlob((blob) => {
        if (!blob) return;
        emits('close', new File([blob], 'avatar.png', { type: 'image/png' }));
    }, 'image/png');
};
</script>

<template>
    <UModal title="Adjust profile picture" :dismissible="false" :ui="{ footer: 'justify-end' }">
        <template #body>
            <Cropper :src="src" class="h-96 w-full rounded-lg border border-default" :stencil-props="{ aspectRatio: 1 }" @change="onChange" />
        </template>

        <template #footer>
            <UButton label="Cancel" color="neutral" variant="outline" @click="emits('close', null)" />
            <UButton label="Apply" icon="i-lucide-check" @click="apply" />
        </template>
    </UModal>
</template>
