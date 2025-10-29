<script setup lang="ts">
import SiteMap from '@/components/SiteMap.vue';
import { Preconfig } from '../type';

interface Props {
    preconfig: Preconfig;
}

const props = defineProps<Props>();

</script>

<template>
    <Card class="md:!grid md:grid-cols-2">
        <template #header>
            <SiteMap :latitude="preconfig.latitude" :longitude="preconfig.longitude" variant="marker" container-class="!h-72" />
        </template>

        <template #content>
            <div class="flex flex-col gap-2">
                <div class="flex justify-between">
                    <span class="text-surface-600 dark:text-surface-400">Project Id</span>
                    <Tag :value="preconfig.project_name" />
                </div>

                <div class="flex justify-between">
                    <span class="text-surface-600 dark:text-surface-400">Site Id</span>
                    <span>{{ preconfig.site_id }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-surface-600 dark:text-surface-400">Site Name</span>
                    <span>{{ preconfig.site_name }}</span>
                </div>

                <div class="flex justify-between gap-6">
                    <span class="text-surface-600 dark:text-surface-400">Coordinate</span>
                    <span class="text-right">{{ preconfig.latitude }}, {{ preconfig.longitude }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-surface-600 dark:text-surface-400">Regency</span>
                    <span>{{ preconfig.regency_name }}</span>
                </div>
            </div>
        </template>
    </Card>
</template>
