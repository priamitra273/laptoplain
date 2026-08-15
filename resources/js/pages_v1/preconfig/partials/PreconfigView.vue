<script setup lang="ts">
import { Preconfig, PreconfigCamera } from '../type';
import { computed, ref } from 'vue';
import SiteSection from '@/pages/preconfig/partials/SiteSection.vue';
import axios from 'axios';

interface Props {
    preconfig?: Preconfig;
    visible: boolean;
}

interface Emits {
    (event: 'update:visible', value: boolean): void
}

//set default to jakarta
const props = withDefaults(defineProps<Props>(), {
    preconfig: (): Preconfig => ({
        uuid: '',
        site_id: 0,
        site_name: '',
        latitude: -6.194109439685576,
        longitude: 106.8169049493526,
        status_uuid: '',
        status: ''
    })
});

const emits = defineEmits<Emits>();

const visible = computed({
    get: () => props.visible,
    set: (newValue) => emits('update:visible', newValue)
});

const cctv = ref<PreconfigCamera[]>([]);
const loading = ref<boolean>(false);

async function onShow(): Promise<void> {
    loading.value = true;

    const response = await axios.get(route('preconfig.show', props.preconfig.uuid))
    cctv.value = response.data.cctv;

    loading.value = false;
}

function onHide(): void {
    cctv.value = []
}

</script>

<template>
    <Drawer v-model:visible="visible" position="right" header="Preconfig Detail" class="!w-11/12 lg:!w-3/5" @show="onShow" @hide="onHide">
        <SiteSection :preconfig="props.preconfig" class="!shadow-none"/>

        <Divider/>

        <div class="grid max-w-sm md:max-w-full">
            <div class="w-full overflow-auto">
                <DataTable :value="cctv" :loading="loading" striped-rows tableStyle="min-width: 50rem">
                    <Column field="cctv_name" header="CCTV Name">
                    </Column>

                    <Column field="mac_address" header="Mac Address">
                    </Column>

                    <Column field="ip_dhcp" header="IP DHCP"></Column>
                </DataTable>
            </div>
        </div>
    </Drawer>
</template>
