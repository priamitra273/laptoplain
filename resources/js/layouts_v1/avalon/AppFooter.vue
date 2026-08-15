<script setup>
import { useLayout } from '@/composables/useLayouts';

const { isDarkTheme } = useLayout();
</script>

<template>
    <div class="layout-footer">
        <img :src="'/storage/layout/images/logo/footer-' + (isDarkTheme ? 'avalon-dark' : 'avalon') + '.svg'" alt="avalon-footer-logo" />
        <div class="flex gap-2">
            <Button icon="pi pi-github" rounded text plain></Button>
            <Button icon="pi pi-facebook" rounded text plain></Button>
            <Button icon="pi pi-twitter" rounded text plain></Button>
        </div>
    </div>
</template>
