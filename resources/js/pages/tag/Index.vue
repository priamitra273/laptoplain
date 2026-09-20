<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { can } from '@/lib/utils';
import { Head } from '@inertiajs/vue3';
import type { MasterDataItem } from '../masterdata/types';
import TagForm from './Form.vue';
import TagTable from './Table.vue';

export type TagItem = MasterDataItem;

interface Props {
    tag?: TagItem[];
}

withDefaults(defineProps<Props>(), {
    tag: () => [],
});

const overlay = useOverlay();

const tagForm = overlay.create(TagForm);

const addTag = () => {
    tagForm.open();
};

const editTag = (value: TagItem) => {
    tagForm.open({ value });
};
</script>

<template>
    <Head title="Tag" />

    <AppLayout title="Tag">
        <Heading title="Tag" description="Manage master data tag">
            <UButton v-if="can('tag.create')" size="sm" @click="addTag">Add Tag</UButton>
        </Heading>

        <TagTable :data="tag" @edit="editTag" />
    </AppLayout>
</template>
