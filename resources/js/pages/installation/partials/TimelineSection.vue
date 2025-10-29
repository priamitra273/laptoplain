<script setup lang="ts">
import { onMounted, Prop, ref } from 'vue';
import { Installation, SiteHistory } from '../type';
import moment from 'moment';
import axios from 'axios';
import Icon from '@/components/Icon.vue';
import Heading from '@/components/Heading.vue';
import InputProgress from './InputProgress.vue';
import { SiteStatus } from '@/pages/site/type';
import AttachmentGallery from './AttachmentGallery.vue';

interface Props {
    value: Installation;
    statuses: SiteStatus[]
}

const props = defineProps<Props>();
const histories = ref<SiteHistory[]>([]);

const loading = ref(false);
const visible = ref(false);

const input = ref();

const setTitle = (data: SiteHistory): string => {
    return data.status === 'OPEN'
        ? `${data.created_by} created new site`
        : `${data.created_by} updated to ${data.status.toLowerCase()}`
}

async function getHistories() {
    loading.value = true
    const response = await axios.get(route('installation.log', props.value.uuid));

    histories.value = response.data.data
    loading.value = false;
}

onMounted(() => {
    getHistories();
})
</script>

<template>
    <Card>
        <template #content>
            <div class="flex justify-between">
                <Heading title="Work Progress" />
                <div>
                    <Button icon="pi pi-plus" label="Add Progress" severity="contrast" @click="visible = true" />
                </div>
            </div>

            <div class="flex flex-col gap-6" v-if="loading">
                <div class="flex gap-4">
                    <Skeleton shape="circle" size="2rem" />

                    <div>
                        <Skeleton class="mt-2" width="13rem" height="1rem" />
                        <Skeleton class="mt-2" width="20rem" height="1rem" />
                    </div>
                </div>

                <div class="flex gap-4">
                    <Skeleton shape="circle" size="2rem" />

                    <div>
                        <Skeleton class="mt-2" width="13rem" height="1rem" />
                        <Skeleton class="mt-2" width="20rem" height="1rem" />
                    </div>
                </div>
            </div>

            <Timeline :value="histories" pt:eventOpposite:class="hidden" v-else>
                <template #marker="{ item }">
                    <span class="size-8 rounded-full z-10 overflow-hidden">
                        <img :src="`https://ui-avatars.com/api/?background=random&name=${item.created_by}`"
                            alt="Avatar" />
                    </span>
                </template>

                <template #content="{ item, index }">
                    <div class="mb-4">
                        <div class="flex gap-1 items-center">
                            <span class="font-bold" :class="{ 'text-surface-500': index !== 0 }">{{ setTitle(item) }}</span>
                            <Icon name="Dot" />
                            <span class="text-sm text-surface-500">{{ moment(item.created_at).format('DD MMM YYYY, HH:mm') }}</span>
                        </div>

                        <div class="text-surface-500" v-html="item.remark"></div>

                        <ul v-if="item.attachments">
                            <AttachmentGallery :value="item.attachments" />
                        </ul>
                    </div>
                </template>
            </Timeline>
        </template>
    </Card>

    <Dialog v-model:visible="visible" modal header="Add Progress" dismissable-mask :style="{ width: '50vw' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <InputProgress ref="input" :site-uuid="props.value.uuid" :statuses="statuses" />

        <template #footer>
            <Button label="Close" severity="secondary" @click="visible = false" />
            <Button label="Submit" :loading="input?.loading" :disabled="input?.loading" @click="input.save()" />
        </template>
    </Dialog>
</template>