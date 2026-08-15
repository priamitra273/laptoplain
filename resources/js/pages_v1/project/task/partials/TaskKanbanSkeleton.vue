<script setup lang="ts">
withDefaults(
    defineProps<{
        columns?: number;
    }>(),
    {
        columns: 4,
    },
);

// Jumlah kartu per kolom dibuat bervariasi agar placeholder terasa natural (deterministik).
const cardCounts = [3, 2, 4, 2, 3];
</script>

<template>
    <div class="overflow-x-auto py-3" aria-hidden="true">
        <div class="flex flex-nowrap gap-3 pb-1">
            <!-- Column placeholder -->
            <div
                v-for="col in columns"
                :key="col"
                class="flex w-[300px] shrink-0 flex-col rounded-xl border border-surface-200 bg-surface-50/60 dark:border-surface-700 dark:bg-surface-800/40"
            >
                <!-- Column header -->
                <div class="flex items-center gap-2 px-3 py-2.5">
                    <Skeleton shape="circle" size="0.5rem" />
                    <Skeleton width="45%" height="0.7rem" />
                    <Skeleton width="1.25rem" height="1.1rem" borderRadius="9999px" class="ml-auto" />
                </div>

                <!-- Card placeholders -->
                <div class="flex flex-col gap-2 px-2 pb-2">
                    <div
                        v-for="card in cardCounts[(col - 1) % cardCounts.length]"
                        :key="card"
                        class="rounded-lg border border-surface-200 bg-white p-2.5 dark:border-surface-700 dark:bg-surface-800"
                    >
                        <!-- Type + priority chips -->
                        <div class="mb-2 flex items-center gap-1">
                            <Skeleton width="2.75rem" height="0.95rem" />
                            <Skeleton width="2.5rem" height="0.95rem" />
                        </div>

                        <!-- Title -->
                        <Skeleton width="92%" height="0.8rem" class="mb-1.5" />
                        <Skeleton width="68%" height="0.8rem" class="mb-2.5" />

                        <!-- Project line -->
                        <Skeleton width="55%" height="0.7rem" class="mb-2.5" />

                        <!-- Footer: id + avatars -->
                        <div class="flex items-center justify-between">
                            <Skeleton width="2rem" height="0.65rem" />
                            <div class="flex -space-x-1.5">
                                <Skeleton shape="circle" size="1.25rem" />
                                <Skeleton shape="circle" size="1.25rem" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
