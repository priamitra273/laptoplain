<script setup lang="ts">
import { Comment } from './type';

const props = defineProps<{
    comment: Comment;
}>();
</script>

<template>
    <div class="animate-pulse rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex gap-2">
            <div class="h-8 w-8 flex-shrink-0 rounded-full bg-gray-200 dark:bg-gray-700" />
            <div class="flex-1 space-y-2 pt-1">
                <div class="h-3 w-1/4 rounded bg-gray-200 dark:bg-gray-700" />
                <div class="h-3 w-3/4 rounded bg-gray-200 dark:bg-gray-700" />
            </div>
        </div>
    </div>
</template>
