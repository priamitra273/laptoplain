<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import TeamListTable from './TeamListTable.vue';
import { Team } from '@/types';

interface Props {
    teams?: Team[]
}

const props = withDefaults(defineProps<Props>(), {
    teams: () => []
})

</script>

<template>
    <Head title="Menu" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Team" description="Manage user's team" />

            <TeamListTable :teams="teams" />
        </div>
    </AppLayout>
</template>