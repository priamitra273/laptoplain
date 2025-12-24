<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Tag } from '@/types';
import { Head } from '@inertiajs/vue3';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import TagTable from './Table.vue';

interface Props {
    tag?: Tag[];
}

const props = withDefaults(defineProps<Props>(), {
    tag: () => [],
});
</script>

<template>
    <Head title="Tag" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Tag" description="Manage master data tag type" />
            <TagTable :tag="props.tag" />
        </div>
        <Toast />
        <ConfirmDialog />
    </AppLayout>
</template>
