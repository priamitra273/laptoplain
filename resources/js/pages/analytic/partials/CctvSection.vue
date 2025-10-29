<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Analytic } from '../type';

interface Props {
    analytic: Analytic;
}

const props = defineProps<Props>();


</script>

<template>
    <Card>
        <template #content>
            <Heading :title="analytic.name" />

            <div class="flex flex-col gap-2">
                <div class="grid grid-cols-4">
                    <span>Project ID</span>
                    <span class="font-semibold md:col-span-3">{{ analytic.project_name }}</span>
                </div>
                
                <div class="grid grid-cols-4">
                    <span>Regency</span>
                    <span class="font-bold md:col-span-3">{{ analytic.regency_name }}</span>
                </div>

                <Divider type="dashed" />

                <div class="grid grid-cols-4">
                    <span>Site ID</span>
                    <span class="font-bold md:col-span-3">{{ analytic.site_id }}</span>
                </div>

                <div class="grid grid-cols-4">
                    <span>Site Name</span>
                    <span class="font-bold md:col-span-3">{{ analytic.site_name }}</span>
                </div>

                <div class="grid grid-cols-4">
                    <span>Coordinate</span>
                    <span class="font-bold md:col-span-3">{{ analytic.latitude }}, {{ analytic.longitude }}</span>
                </div>
            </div>
        </template>
    </Card>
</template>