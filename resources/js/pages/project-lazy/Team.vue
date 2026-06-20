<script lang="ts">
import ProjectShellLayout from './layouts/ProjectShellLayout.vue';

export default { layout: ProjectShellLayout };
</script>

<script setup lang="ts">
import { useProjectPermissions } from '@/composables/useProjectPermissions';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { TeamMember, TeamProps } from './index';
import MemberEditForm from './member/EditFormTemp.vue';
import MemberAddForm from './member/Form.vue';
import MembersTable from './member/Table.vue';

const props = defineProps<TeamProps>();

const { canAction } = useProjectPermissions(props.policy);

const hasPermission = computed(
    () => canAction('project_member', 'create') || canAction('project_member', 'update') || canAction('project_member', 'delete'),
);

const visibleAdd = ref(false);
const visibleEdit = ref(false);
const selectedMember = ref<TeamMember | null>(null);

const openAdd = () => (visibleAdd.value = true);

const openEdit = (member: TeamMember) => {
    selectedMember.value = member;
    visibleEdit.value = true;
};

const onSaved = () => {
    visibleAdd.value = false;
    visibleEdit.value = false;
};
</script>

<template>
    <Head :title="`Team - ${props.project.title}`" />

    <MembersTable
        :projectId="props.project.id"
        :members="props.members"
        :roles="props.roles"
        :users="props.users"
        :hasPermission="hasPermission"
        @add="openAdd"
        @edit="openEdit"
    />

    <Dialog v-model:visible="visibleAdd" header="Add Member" modal class="w-96">
        <MemberAddForm :projectId="props.project.id" :users="props.users" :roles="props.roles" @close="visibleAdd = false" @saved="onSaved" />
    </Dialog>

    <Dialog v-model:visible="visibleEdit" header="Edit Member" modal class="w-96">
        <MemberEditForm
            v-if="selectedMember"
            :projectId="props.project.id"
            :member="selectedMember"
            :roles="props.roles"
            @close="visibleEdit = false"
            @saved="onSaved"
        />
    </Dialog>
</template>
