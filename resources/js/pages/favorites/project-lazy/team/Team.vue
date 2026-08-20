<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import ProjectShellLayout from '../layouts/ProjectShellLayout.vue';
import type { ShellProps } from '../types';
import TeamTable from './TeamTable.vue';
import type { TeamMember, TeamRole, TeamUserOption } from './types';

interface Props extends Omit<ShellProps, 'members'> {
    members?: TeamMember[];
    roles?: TeamRole[];
    users?: TeamUserOption[];
}

withDefaults(defineProps<Props>(), {
    members: () => [],
    roles: () => [],
    users: () => [],
});
</script>

<template>
    <Head :title="`Team - ${project.title}`" />

    <ProjectShellLayout>
        <TeamTable :project-id="project.id" :members="members" :roles="roles" :users="users" />
    </ProjectShellLayout>
</template>
