<script setup lang="ts">
interface Props {
    label: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 py-16 text-center">
        <UIcon name="i-lucide-hammer" class="size-10 text-muted" />
        <div class="space-y-1">
            <p class="text-base font-medium">{{ label }} is coming soon</p>
            <p class="text-sm text-muted">This tab is still being migrated to the new interface.</p>
        </div>
    </div>
</template>
