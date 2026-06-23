<script setup lang="ts">
withDefaults(
    defineProps<{
        rows?: number;
    }>(),
    {
        rows: 6,
    },
);
</script>

<template>
    <div class="overflow-x-auto" aria-hidden="true">
        <!-- Header row (selaras dengan kolom DataTable) -->
        <div class="flex items-center gap-4 border-b border-surface-200 px-3 py-3 dark:border-surface-700">
            <div style="width: 40%"><Skeleton width="3rem" height="0.7rem" /></div>
            <div style="width: 14%"><Skeleton width="3.5rem" height="0.7rem" /></div>
            <div style="width: 16%"><Skeleton width="3.5rem" height="0.7rem" /></div>
            <div style="width: 14%"><Skeleton width="2.5rem" height="0.7rem" /></div>
            <div style="width: 16%"><Skeleton width="3.5rem" height="0.7rem" /></div>
        </div>

        <!-- Body rows -->
        <div v-for="row in rows" :key="row" class="flex items-center gap-4 border-b border-surface-100 px-3 py-3.5 dark:border-surface-800">
            <!-- Task: title + project -->
            <div style="width: 40%" class="flex flex-col gap-1.5">
                <Skeleton width="70%" height="0.85rem" />
                <Skeleton width="45%" height="0.7rem" />
            </div>

            <!-- Status -->
            <div style="width: 14%"><Skeleton width="4rem" height="1.25rem" borderRadius="9999px" /></div>

            <!-- Priority -->
            <div style="width: 16%" class="flex items-center gap-2">
                <Skeleton shape="circle" size="1rem" />
                <Skeleton width="3.5rem" height="0.8rem" />
            </div>

            <!-- Type -->
            <div style="width: 14%"><Skeleton width="3.5rem" height="1.25rem" borderRadius="9999px" /></div>

            <!-- Due date -->
            <div style="width: 16%"><Skeleton width="4.5rem" height="0.8rem" /></div>
        </div>
    </div>
</template>
