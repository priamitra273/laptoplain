<script setup lang="ts">
defineProps<{
    title?: string;
    /** Lepas padding badan — untuk kartu berisi daftar yang garisnya harus penuh sampai tepi. */
    flush?: boolean;
}>();
</script>

<template>
    <UCard
        :ui="{
            header: flush ? '' : 'px-4 pt-4 pb-0',
            body: flush ? 'flex flex-col p-0' : 'flex flex-col gap-4',
        }"
    >
        <template v-if="title || $slots.meta || $slots.action" #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 v-if="title" class="text-base font-semibold text-highlighted">{{ title }}</h2>
                    <slot name="meta" />
                </div>

                <slot name="action" />
            </div>
        </template>

        <slot />
    </UCard>
</template>
