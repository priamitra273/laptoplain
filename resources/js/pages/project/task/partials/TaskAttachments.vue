<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import type { UploadedFile } from '@/types';
import { computed } from 'vue';
import type { Task } from '../../index.d.ts';
import SectionPanel from './SectionPanel.vue';

interface Props {
    task: Task;
}

const props = defineProps<Props>();

const attachments = computed<UploadedFile[]>(() => (props.task.media ?? []) as UploadedFile[]);

const formatSize = (bytes: number): string => {
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const getFileIcon = (mimeType?: string): string => {
    if (!mimeType) {
        return 'File';
    }
    if (mimeType.startsWith('image/')) {
        return 'Image';
    }
    if (mimeType.startsWith('video/')) {
        return 'Video';
    }
    if (mimeType.startsWith('audio/')) {
        return 'Music';
    }
    if (mimeType === 'application/pdf' || mimeType.includes('word') || mimeType.includes('document')) {
        return 'FileText';
    }
    if (mimeType.includes('sheet') || mimeType.includes('excel')) {
        return 'Sheet';
    }
    if (mimeType.includes('zip') || mimeType.includes('archive') || mimeType.includes('compressed')) {
        return 'Archive';
    }
    return 'File';
};

const isImage = (file: UploadedFile): boolean => !!file.mime_type?.startsWith('image/');
const fileUrl = (file: UploadedFile): string => file.url ?? file.original_url;
</script>

<template>
    <SectionPanel title="Attachments" icon="pi pi-paperclip" toggleable>
        <template #actions>
            <span
                v-if="attachments.length"
                class="rounded-full bg-surface-100 px-2 py-0.5 text-xs font-medium tabular-nums text-surface-600 dark:bg-surface-800 dark:text-surface-300"
            >
                {{ attachments.length }}
            </span>
        </template>

        <div v-if="attachments.length" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <a
                v-for="file in attachments"
                :key="file.uuid"
                :href="fileUrl(file)"
                target="_blank"
                rel="noopener noreferrer"
                class="group flex items-center gap-3 rounded-lg border border-surface-200 p-2.5 transition-colors hover:border-surface-300 hover:bg-surface-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-surface-700 dark:hover:border-surface-600 dark:hover:bg-surface-800"
            >
                <img v-if="isImage(file)" :src="fileUrl(file)" :alt="file.file_name" class="h-10 w-10 shrink-0 rounded-md object-cover" />
                <span
                    v-else
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-100 text-surface-500 dark:bg-surface-800 dark:text-surface-400"
                >
                    <Icon :name="getFileIcon(file.mime_type)" class="size-5" />
                </span>

                <div class="min-w-0 flex-1">
                    <p
                        :title="file.file_name"
                        class="truncate text-sm font-medium text-surface-800 transition-colors group-hover:text-primary-600 dark:text-surface-100 dark:group-hover:text-primary-400"
                    >
                        {{ file.file_name }}
                    </p>
                    <p class="text-xs text-surface-500 dark:text-surface-400">{{ formatSize(file.size) }}</p>
                </div>

                <Icon
                    name="Download"
                    class="size-4 shrink-0 text-surface-400 opacity-0 transition-opacity group-hover:opacity-100 dark:text-surface-500"
                />
            </a>
        </div>

        <div v-else class="flex flex-col items-center justify-center gap-2 py-8 text-center">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-surface-100 dark:bg-surface-800">
                <Icon name="Paperclip" class="size-5 text-surface-400 dark:text-surface-500" />
            </div>
            <p class="text-sm text-surface-500 dark:text-surface-400">No attachments</p>
        </div>
    </SectionPanel>
</template>
