<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Label from '@/components/ui/label/Label.vue';
import { InertiaForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Form } from '../type';
import InputError from '@/components/InputError.vue';

interface Props {
    form: InertiaForm<Form>;
}
const props = defineProps<Props>();

const visible = ref(false);
const preview = ref();
const modalHeader = ref('');

const openModal = (title: string, source: string) => {
    visible.value = true
    preview.value = source
    modalHeader.value = title
}
</script>

<template>
    <Card>
        <template #content>
            <Heading title="Stream Information" description="Please fill the required fields." />

            <div class="grid grid-cols-3 gap-6">
                <div>
                    <Label for="cctv_name">CCTV Name</Label>
                    <InputText v-model="form.cctv_name" id="cctv_name" fluid />
                    <InputError :message="form.errors.cctv_name" class="mt-1" />
                </div>

                <div>
                    <Label for="link_embed">Link Embed</Label>

                    <InputGroup>
                        <InputText v-model="form.link_embed" id="link_embed" fluid />
                        <Button label="Preview" severity="contrast"
                            @click="openModal('Preview Embed', form.link_embed ?? '')" />
                    </InputGroup>

                    <InputError :message="form.errors.link_embed" class="mt-1" />
                </div>

                <div>
                    <Label for="link_embed_nonrelay">Link Embed Non-Relay (Recording)</Label>

                    <InputGroup>
                        <InputText v-model="form.link_embed_nonrelay" id="link_embed_nonrelay" fluid />
                        <Button label="Preview" severity="contrast"
                            @click="openModal('Preview Embed Non-Relay (Recording)', form.link_embed_nonrelay ?? '')"  />
                    </InputGroup>

                    <InputError :message="form.errors.link_embed_nonrelay" class="mt-1" />
                </div>

                <div>
                    <Label for="link_rtsp">RTSP</Label>
                    <InputText v-model="form.link_rtsp" id="link_rtsp" fluid />
                    <InputError :message="form.errors.link_rtsp" class="mt-1" />
                </div>

                <div>
                    <Label for="ip_flussonic">IP Flussonic</Label>
                    <InputText v-model="form.ip_flussonic" id="ip_flussonic" fluid />
                    <InputError :message="form.errors.ip_flussonic" class="mt-1" />
                </div>

                <Dialog v-model:visible="visible" modal :header="modalHeader" :style="{ width: '50vw' }"
                    :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
                    <iframe :src="preview" class="w-full aspect-video rounded-lg">
                    </iframe>

                    <template #footer>
                        <Button label="Close" severity="secondary" @click="visible = false"/>
                    </template>
                </Dialog>
            </div>
        </template>
    </Card>
</template>
