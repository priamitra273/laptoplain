<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Team } from '@/types';
import { Head } from '@inertiajs/vue3';
import TeamTable from './TeamTable.vue';
import TeamForm from './TeamForm.vue';

interface Props {
    teams?: Team[]
}

const props = withDefaults(defineProps<Props>(), {
    teams: () => []
})

const overlay = useOverlay()

const teamForm = overlay.create(TeamForm)

const addTeam = () => {
    teamForm.open()
}

const editTeam = (team: Team) => {
    teamForm.open({ value: team })
}

</script>

<template>

    <Head title="Team" />

    <AppLayout title="Team">
        <Heading title="Team" description="Manage user's team">
            <UButton size="sm" @click="addTeam">Add Team</UButton>
        </Heading>

        <TeamTable :data="teams" @edit="editTeam" />
    </AppLayout>
</template>