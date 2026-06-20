<script lang="ts" setup>
import Icon from '@/components/Icon.vue';
import Label from '@/components/Label.vue';
import { UploadedFile } from '@/types';
import { ref } from 'vue';

const modelValue = defineModel<File[] | UploadedFile[] | null>('modelValue');

const isDragging = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

const isUploadedFile = (file: File | UploadedFile): file is UploadedFile => 'uuid' in file;

const getFileName = (file: File | UploadedFile) => (isUploadedFile(file) ? file.file_name : file.name);
const getFileSize = (file: File | UploadedFile) => file.size;
const getMimeType = (file: File | UploadedFile) => (isUploadedFile(file) ? file.mime_type : file.type);

const formatSize = (bytes: number): string => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const getFileIcon = (mimeType: string): string => {
    if (mimeType.startsWith('image/')) return 'Image';
    if (mimeType.startsWith('video/')) return 'Video';
    if (mimeType.startsWith('audio/')) return 'Music';
    if (mimeType === 'application/pdf') return 'FileText';
    if (mimeType.includes('word') || mimeType.includes('document')) return 'FileText';
    if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'Sheet';
    if (mimeType.includes('zip') || mimeType.includes('archive') || mimeType.includes('compressed')) return 'Archive';
    return 'File';
};

const getFileUrl = (file: File | UploadedFile) => {
    if (file instanceof File) {
        return URL.createObjectURL(file);
    }

    return file.url ?? file.original_url;
};

const addFiles = (fileList: FileList) => {
    const incoming = Array.from(fileList);
    const current = (modelValue.value ?? []) as File[];
    modelValue.value = [...current, ...incoming] as File[];
};

const removeFile = (index: number) => {
    const next = [...(modelValue.value ?? [])];
    next.splice(index, 1);
    modelValue.value = next.length > 0 ? (next as File[]) : null;
};

const onDragOver = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = true;
};

const onDragLeave = () => {
    isDragging.value = false;
};

const onDrop = (e: DragEvent) => {
    e.preventDefault();
    isDragging.value = false;
    if (e.dataTransfer?.files.length) {
        addFiles(e.dataTransfer.files);
    }
};

const onFileInputChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (input.files?.length) {
        addFiles(input.files);
        input.value = '';
    }
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <Label value="Attachment" icon="Paperclip" />

        <input
            ref="fileInputRef"
            type="file"
            multiple
            class="hidden"
            accept="image/*,video/*,audio/*,application/pdf,.doc,.docx,.xls,.xlsx,.zip"
            @change="onFileInputChange"
        />

        <div
            class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded border border-dashed px-4 py-8 text-center text-sm transition-colors"
            :class="
                isDragging
                    ? 'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-950'
                    : 'border-surface-300 hover:border-primary-500 hover:bg-surface-100 dark:border-surface-700 dark:hover:border-primary-400 dark:hover:bg-surface-900'
            "
            @click="fileInputRef?.click()"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
        >
            <span class="flex items-center gap-2">
                <Icon name="Paperclip" class="size-4 text-primary" />
                <span class="font-bold">Drop files here</span>
                <span>or click to upload</span>
            </span>

            <span class="flex items-center gap-0.5 text-surface-400">
                <span>Up to 20 MB</span>
                <Icon name="Dot" />
                <span>PDF, images, videos, and docs</span>
            </span>
        </div>

        <div v-if="modelValue?.length" class="flex flex-col gap-2">
            <div
                v-for="(file, index) in modelValue"
                :key="index"
                class="flex items-center gap-4 rounded border border-surface-200 bg-surface-100 px-3 py-2 dark:border-surface-700"
            >
                <Icon :name="getFileIcon(getMimeType(file))" class="size-4 shrink-0 text-surface-500" />

                <div class="min-w-0 flex-1">
                    <a
                        :href="getFileUrl(file)"
                        target="_blank"
                        class="cursor-pointer truncate text-sm font-medium text-surface-800 hover:underline dark:text-surface-200"
                    >
                        {{ getFileName(file) }}
                    </a>
                    <p class="text-xs text-surface-400">{{ formatSize(getFileSize(file)) }}</p>
                </div>

                <button
                    type="button"
                    class="shrink-0 rounded p-1 text-surface-400 transition-colors hover:bg-surface-100 hover:text-red-500 dark:hover:bg-surface-800"
                    @click.stop="removeFile(index)"
                >
                    <Icon name="X" class="size-3.5" />
                </button>
            </div>
        </div>
    </div>
</template>
