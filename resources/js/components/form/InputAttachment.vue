<script lang="ts" setup>
import { formatFileSize } from '@/lib/utils';
import { UploadedFile } from '@/types';
import { onBeforeUnmount, ref } from 'vue';

const modelValue = defineModel<(File | UploadedFile)[] | null>('modelValue');

withDefaults(defineProps<{ label?: string | null }>(), { label: 'Attachment' });

const isDragging = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);

const isUploadedFile = (file: File | UploadedFile): file is UploadedFile => 'uuid' in file;

const getFileName = (file: File | UploadedFile) => (isUploadedFile(file) ? file.file_name : file.name);
const getFileSize = (file: File | UploadedFile) => file.size;
const getMimeType = (file: File | UploadedFile) => (isUploadedFile(file) ? file.mime_type : file.type);

const getFileIcon = (mimeType: string): string => {
    if (mimeType.startsWith('image/')) return 'image';
    if (mimeType.startsWith('video/')) return 'video';
    if (mimeType.startsWith('audio/')) return 'music';
    if (mimeType === 'application/pdf') return 'file-text';
    if (mimeType.includes('word') || mimeType.includes('document')) return 'file-text';
    if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'file-spreadsheet';
    if (mimeType.includes('zip') || mimeType.includes('archive') || mimeType.includes('compressed')) return 'archive';
    return 'file';
};

/** Satu object URL per File, supaya render ulang tidak terus membuat URL baru yang tidak pernah dilepas. */
const objectUrls = new Map<File, string>();

const getFileUrl = (file: File | UploadedFile) => {
    if (file instanceof File) {
        let url = objectUrls.get(file);

        if (!url) {
            url = URL.createObjectURL(file);
            objectUrls.set(file, url);
        }

        return url;
    }

    // `||`, bukan `??`: `url` bisa datang sebagai string kosong atau spasi, dan `??`
    // hanya jatuh ke fallback untuk null/undefined sehingga menghasilkan tautan mati.
    return file.url?.trim() || file.original_url;
};

onBeforeUnmount(() => {
    objectUrls.forEach((url) => URL.revokeObjectURL(url));
    objectUrls.clear();
});

/** Disamakan dengan aturan `FileOrMedia` di `TaskStoreRequest`/`TaskUpdateRequest`. */
const ALLOWED_EXTENSIONS = [
    'jpg',
    'jpeg',
    'png',
    'gif',
    'svg',
    'pdf',
    'doc',
    'docx',
    'xls',
    'xlsx',
    'ppt',
    'pptx',
    'csv',
    'txt',
    'mp4',
    'webm',
    'ogg',
    'm4v',
    'mov',
    'avi',
    'wmv',
    'flv',
    '3gp',
    'm4a',
    'wav',
    'flac',
    'aac',
    'mp3',
    'zip',
    'rar',
    '7z',
    'tar',
    'gz',
    'bz2',
];
const MAX_FILE_SIZE = 20 * 1024 * 1024;

const acceptAttribute = ALLOWED_EXTENSIONS.map((extension) => `.${extension}`).join(',');
const rejectedFiles = ref<string[]>([]);

const addFiles = (fileList: FileList) => {
    const accepted: File[] = [];
    const rejected: string[] = [];

    for (const file of Array.from(fileList)) {
        const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

        if (!ALLOWED_EXTENSIONS.includes(extension)) {
            rejected.push(`${file.name} (unsupported type)`);
        } else if (file.size > MAX_FILE_SIZE) {
            rejected.push(`${file.name} (${formatFileSize(file.size)}, over 20 MB)`);
        } else {
            accepted.push(file);
        }
    }

    rejectedFiles.value = rejected;

    if (accepted.length) {
        modelValue.value = [...(modelValue.value ?? []), ...accepted];
    }
};

const removeFile = (index: number) => {
    const next = [...(modelValue.value ?? [])];
    const [removed] = next.splice(index, 1);
    const url = removed instanceof File ? objectUrls.get(removed) : undefined;

    if (url) {
        URL.revokeObjectURL(url);
        objectUrls.delete(removed as File);
    }

    modelValue.value = next.length > 0 ? next : null;
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
        <p v-if="label" class="flex items-center gap-2 text-sm font-medium"><UIcon name="i-lucide-paperclip" class="size-4" />{{ label }}</p>

        <input ref="fileInputRef" type="file" multiple class="hidden" :accept="acceptAttribute" @change="onFileInputChange" />

        <UButton
            type="button"
            color="neutral"
            variant="ghost"
            class="flex w-full cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border border-dashed px-4 py-8 text-center text-sm transition-colors focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
            :class="isDragging ? 'border-primary bg-primary/5' : 'border-default hover:border-primary hover:bg-elevated'"
            @click="fileInputRef?.click()"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
        >
            <UIcon name="i-lucide-upload" class="mb-1 size-5 text-muted" />
            <span class="font-semibold text-highlighted">Drop files here or click to browse</span>
            <span class="text-xs text-dimmed">PDF, images, videos, and docs Â· up to 20 MB per file</span>
        </UButton>

        <ul v-if="rejectedFiles.length" class="flex flex-col gap-0.5 text-xs text-error">
            <li v-for="name in rejectedFiles" :key="name">Skipped {{ name }}</li>
        </ul>

        <div v-if="modelValue?.length" class="flex flex-col gap-2">
            <UCard
                v-for="(file, index) in modelValue"
                :key="index"
                class="border-default bg-elevated/50"
                :ui="{ body: 'flex items-center gap-4 px-3 py-2' }"
            >
                <UIcon :name="`i-lucide-${getFileIcon(getMimeType(file))}`" class="size-4 shrink-0 text-muted" />

                <div class="min-w-0 flex-1">
                    <ULink :href="getFileUrl(file)" target="_blank" class="truncate text-sm font-medium text-highlighted hover:underline">
                        {{ getFileName(file) }}
                    </ULink>
                    <p class="text-xs text-dimmed">{{ formatFileSize(getFileSize(file)) }}</p>
                </div>

                <UButton
                    type="button"
                    icon="i-lucide-x"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    square
                    class="shrink-0"
                    :aria-label="`Hapus ${getFileName(file)}`"
                    @click.stop="removeFile(index)"
                />
            </UCard>
        </div>
    </div>
</template>
