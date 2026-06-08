<script setup lang="ts">
import Sprint = App.Data.Sprint;

import Label from '@/components/ui/label/Label.vue';
import axios from 'axios';
import moment from 'moment';
import { onMounted, ref } from 'vue';
import { Project } from '..';
import SprintBurndown from './SprintBurndown.vue';
import SprintTaskTableReport from './SprintTaskTableReport.vue';

const props = defineProps<{
    project: Project;
}>();

const loading = ref({
    sprints: false,
    tasks: false,
    burndown: false,
});

const sprints = ref<Sprint.SprintData[]>([]);
const sprint = ref<Sprint.SprintData | null>(null);

const fetchSprints = async () => {
    loading.value.sprints = true;

    const response = await axios.get(route('sprints.all', props.project.id));
    sprints.value = response.data.data;
    loading.value.sprints = false;
};

onMounted(() => {
    fetchSprints();
});
</script>

<template>
    <div class="flex flex-col gap-12">
        <div class="flex flex-col gap-2">
            <Label>Sprint</Label>
            <Select v-model="sprint" :options="sprints" optionLabel="name" placeholder="Select Sprint" class="w-72" />
            <Tag v-if="sprint" :value="sprint.status?.name" :severity="sprint.status?.severity" class="w-fit" />
        </div>

        <div>
            <span class="text-sm font-medium text-gray-700">Date - </span>
            <span v-if="sprint"> {{ moment(sprint.start_date).format('DD MMMM YYYY') }} - {{ moment(sprint.end_date).format('DD MMMM YYYY') }} </span>
        </div>

        <SprintBurndown v-if="sprint" :sprint-id="sprint.id" :project="project" />
        <SprintTaskTableReport v-if="sprint" :sprint-id="sprint.id" :project="project" />
    </div>
</template>
