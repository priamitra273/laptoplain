<script setup lang="ts">
import axios from 'axios';
import { usePrimeVue } from 'primevue/config';
import { FileUploadEmits, FileUploadProps, FileUploadSelectEvent, FileUploadSlots } from 'primevue/fileupload';
import { computed, DefineComponent, defineEmits, defineProps, ref, watch } from 'vue';

interface Props {
    modelValue: (string | File)[];
    multiple?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    multiple: false,
});

const emits = defineEmits<{
    (event: 'update:modelValue', value: (string | File)[]): void;
}>();

const $primevue = usePrimeVue();

const fileUpload = ref<DefineComponent<FileUploadProps, FileUploadSlots, FileUploadEmits> | null>(null);

const totalSize = ref(0);
const totalSizePercent = ref(0);

const files = computed<(string | File)[]>({
    get: () => props.modelValue,
    set: (newValue) => emits('update:modelValue', newValue),
});

const onSelectedFiles = (event: FileUploadSelectEvent) => {
    files.value = event.files;
};

const onRemoveTemplatingFile = (file: File, removeFileCallback: (index: number) => void, index: number) => {
    removeFileCallback(index);
    totalSize.value -= parseInt(formatSize(file.size));
    totalSizePercent.value = totalSize.value / 10;

    emits('update:modelValue', fileUpload.value?.files || []);
};

const formatSize = (bytes: number): string => {
    const k = 1024;
    const dm = 3;
    const sizes = $primevue.config.locale?.fileSizeTypes || '';

    if (bytes === 0) {
        return `0 ${sizes[0]}`;
    }

    const i = Math.floor(Math.log(bytes) / Math.log(k));
    const formattedSize = parseFloat((bytes / Math.pow(k, i)).toFixed(dm));

    return `${formattedSize} ${sizes[i]}`;
};

const getKey = (file: File): string => {
    return file instanceof File ? file.name + file.type + file.size : file;
};

const getFilename = (file: File): string => {
    return file instanceof File ? file.name : file;
};

watch(
    () => props.modelValue.length,
    () => {
        for (const url of props.modelValue) {
            if (typeof url === 'string') {
                axios.get(url, { responseType: 'blob' }).then((res) => {
                    const file = new File([res.data], url.split('/').pop() || '', {
                        type: res.headers['content-type'],
                        lastModified: Date.now(),
                    });

                    fileUpload.value?.files.push(file);
                });
            }
        }

        emits('update:modelValue', fileUpload.value?.files || []);
    },
);
</script>

<template>
    <FileUpload
        name="demo[]"
        url="/api/upload"
        ref="fileUpload"
        :maxFileSize="10000000"
        choose-icon="pi pi-upload"
        :show-upload-button="false"
        :show-cancel-button="false"
        @select="onSelectedFiles"
        :multiple="props.multiple"
        :choose-button-props="{
            outlined: true,
            disabled: props.multiple ? false : files.length,
        }"
        :pt="{
            root: {
                class: '!border-2 !border-dashed',
            },
        }"
    >
        <template #content="{ files, uploadedFiles, removeUploadedFileCallback, removeFileCallback }">
            <div class="flex flex-col gap-2">
                <div v-for="(file, index) of files" :key="getKey(file)" class="flex items-center gap-4 rounded border p-4">
                    <div class="flex-1 overflow-hidden text-ellipsis">
                        <span class="overflow-hidden whitespace-nowrap font-semibold">{{ getFilename(file) }}</span>
                        <div>{{ formatSize(file.size) }}</div>
                    </div>
                    <Button icon="pi pi-trash" @click="onRemoveTemplatingFile(file, removeFileCallback, index)" outlined rounded severity="danger" />
                </div>
            </div>
        </template>

        <template #empty>
            <div class="flex flex-col items-center justify-center">
                <div class="flex size-12 items-center justify-center rounded-full border-2">
                    <i class="pi pi-plus !text-xl !text-muted-color" />
                </div>
                <p class="mb-0 mt-6">Drag and drop files to here to upload.</p>
            </div>
        </template>
    </FileUpload>
</template>
