<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { watch } from 'vue';

const page = usePage();
const toast = useToast();

watch(
    () => page.props?.flash,
    (flash) => {
        if (!flash) return;

        const hasFlash = flash && (flash.success || flash.error);
        if (hasFlash) {
            const severity = flash.success ? 'success' : 'error';
            toast.add({
                severity: severity,
                summary: flash.success ? 'Success' : 'Error',
                detail: flash.success || flash.error,
                life: 3000,
            });
        }
    },
    { deep: true },
);
</script>

<template>
    <slot />
</template>
