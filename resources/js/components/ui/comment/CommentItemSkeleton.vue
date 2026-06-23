<script setup lang="ts">
import { Comment } from './type';

const props = defineProps<{
    comment: Comment;
}>();
</script>

<template>
    <div
        class="animate-pulse rounded-xl border border-surface-200 bg-surface-50 p-3 motion-reduce:animate-none dark:border-surface-700 dark:bg-surface-800/50"
    >
        <div class="flex gap-3">
            <div class="size-9 shrink-0 rounded-full bg-surface-200 dark:bg-surface-700" />
            <div class="flex-1 space-y-2 pt-1">
                <div class="h-3 w-1/4 rounded bg-surface-200 dark:bg-surface-700" />
                <div class="h-3 w-3/4 rounded bg-surface-200 dark:bg-surface-700" />
            </div>
        </div>
    </div>
</template>
