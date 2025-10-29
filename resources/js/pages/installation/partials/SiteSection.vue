<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Installation } from '../type';
import SiteMap from '@/components/SiteMap.vue';

interface Props {
    value: Installation;
}

defineProps<Props>();
</script>

<template>
    <Card class="overflow-hidden" pt:body:class="!p-0">
        <template #content>
            <Tabs value="0">
                <TabList>
                    <Tab value="0">Site Information</Tab>
                    <Tab value="1">Map</Tab>
                </TabList>
                
                <TabPanels>
                    <TabPanel value="0">
                        <div class="flex flex-col gap-2">
                            <div class="flex justify-between">
                                <span class="text-surface-600 dark:text-surface-400">Project Id</span>
                                <Tag :value="value.project_name" />
                            </div>
            
                            <div class="flex justify-between">
                                <span class="text-surface-600 dark:text-surface-400">Site Id</span>
                                <span>{{ value.site_id }}</span>
                            </div>
            
                            <div class="flex justify-between">
                                <span class="text-surface-600 dark:text-surface-400">Site Name</span>
                                <span>{{ value.site_name }}</span>
                            </div>
            
                            <div class="flex justify-between gap-6">
                                <span class="text-surface-600 dark:text-surface-400">Coordinate</span>
                                <span class="text-right">{{ value.latitude }}, {{ value.longitude }}</span>
                            </div>
            
                            <div class="flex justify-between">
                                <span class="text-surface-600 dark:text-surface-400">Regency</span>
                                <span>{{ value.regency_name }}</span>
                            </div>
                        </div>
                    </TabPanel>

                    <TabPanel value="1">
                        <SiteMap :latitude="value.latitude" :longitude="value.longitude" variant="marker" />
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </template>
    </Card>
</template>