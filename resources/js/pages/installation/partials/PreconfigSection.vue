<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { PreconfigCamera } from '@/pages/site/type';

interface Props {
    cctv: PreconfigCamera[];
}

defineProps<Props>();
</script>

<template>
    <Card>
        <template #title>
            <HeadingSmall title="Preconfig Camera" />
        </template>
        
        <template #content>
            <DataTable :value="cctv" striped-rows tableStyle="min-width: 50rem">
                <Column field="cctv_name" header="CCTV Name">
                </Column>
        
                <Column field="mac_address" header="Mac Address">
                </Column>
        
                <Column field="ip_dhcp" header="IP DHCP"></Column>
            </DataTable>
        </template>
    </Card>
</template>