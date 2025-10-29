<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Label from '@/components/ui/label/Label.vue';
import { InertiaForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Analytic, Form } from '../type';
import InputError from '@/components/InputError.vue';
import { useClipboard } from '@vueuse/core';
import { VideoPlayer } from '@videojs-player/vue'

import 'video.js/dist/video-js.css'

interface Props {
    analytic: Analytic;
    form: InertiaForm<Form>;
}
const props = defineProps<Props>();

const visible = ref(false);
const preview = ref('');
const modalHeader = ref('');

const isHlsPreview = computed(() => preview.value.endsWith('.m3u8'))

const openModal = (title: string, source: string) => {
    visible.value = true
    preview.value = source
    modalHeader.value = title
}

const { copy, isSupported } = useClipboard()
</script>

<template>
    <Card>
        <template #content>
            <Heading title="Stream Information" description="Please fill the required fields." />

            <div class="grid grid-cols-3 gap-6">
                <div>
                    <Label for="ip_static">IP Static</Label>
                    <IconField>
                        <InputText :value="analytic.ip_static" id="ip_static" fluid disabled />
                        <span class="p-inputicon pi pi-copy cursor-pointer" v-tooltip.bottom="'copy'"
                            @click="copy(analytic.ip_static ?? '')"></span>
                    </IconField>
                </div>

                <div>
                    <Label for="ip_flussonic">IP Flussonic</Label>
                    <IconField>
                        <InputText :value="analytic.ip_flussonic" id="ip_flussonic" fluid disabled />
                        <span class="p-inputicon pi pi-copy cursor-pointer" v-tooltip.bottom="'copy'"
                            @click="copy(analytic.ip_flussonic ?? '')"></span>
                    </IconField>
                </div>

                <div>
                    <Label for="link_rtsp">RTSP</Label>
                    <IconField>
                        <InputText :value="analytic.link_rtsp" id="link_rtsp" fluid disabled />
                        <span class="p-inputicon pi pi-copy cursor-pointer" v-tooltip.bottom="'copy'"
                            @click="copy(analytic.link_rtsp ?? '')"></span>
                    </IconField>
                </div>

                <div>
                    <Label for="link_embed">Link Embed</Label>

                    <InputGroup>
                        <IconField>
                            <InputText :value="analytic.link_embed" id="link_embed" fluid disabled />
                            <span class="p-inputicon pi pi-copy cursor-pointer" v-tooltip.bottom="'copy'"
                                @click="copy(analytic.link_embed ?? '')"></span>
                        </IconField>
                        <Button label="Preview" severity="contrast"
                            @click="openModal('Preview Embed', analytic.link_embed ?? '')" />
                    </InputGroup>
                </div>

                <div>
                    <Label for="link_embed_nonrelay">Link Embed Non-Relay (Recording)</Label>

                    <InputGroup>
                        <IconField>
                            <InputText v-model="analytic.link_embed_nonrelay" id="link_embed_nonrelay" fluid disabled/>
                            <span class="p-inputicon pi pi-copy cursor-pointer" v-tooltip.bottom="'copy'"
                                @click="copy(analytic.link_embed_nonrelay ?? '')"></span>
                        </IconField>
                        <Button label="Preview" severity="contrast"
                            @click="openModal('Preview Embed Non-Relay (Recording)', analytic.link_embed_nonrelay ?? '')" />
                    </InputGroup>
                </div>

                <div>
                    <Label for="link_embed_bb">Link Embed Bounding Box</Label>

                    <InputGroup>
                        <InputText v-model="form.link_embed_bb" id="link_embed_bb" fluid/>
                        <Button label="Preview" severity="contrast"
                            @click="openModal('Preview Embed Bounding Box', form.link_embed_bb ?? '')" />
                    </InputGroup>

                    <InputError :message="form.errors.link_embed_bb" class="mt-1"/>
                </div>

                <Dialog v-model:visible="visible" modal :header="modalHeader" :style="{ width: '50vw' }"
                    :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">

                    <div v-if="isHlsPreview" class="w-full aspect-video rounded-lg overflow-hidden">
                        <VideoPlayer :src="preview" controls autoplay class="size-full" />
                    </div>

                    <iframe v-else :src="preview" class="w-full aspect-video rounded-lg">
                    </iframe>

                    <template #footer>
                        <Button label="Close" severity="secondary" @click="visible = false" />
                    </template>
                </Dialog>
            </div>
        </template>
    </Card>
</template>
