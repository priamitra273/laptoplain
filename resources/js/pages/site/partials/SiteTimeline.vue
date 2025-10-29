<script setup lang="ts">
import moment from 'moment';
import { SiteHistory } from '../type';
import Icon from '@/components/Icon.vue';

interface Props {
    value?: SiteHistory[];
}

const props = withDefaults(defineProps<Props>(), {
    value: () => []
});

const setTitle = (data: SiteHistory): string => {
    return data.status === 'OPEN' 
        ? `${data.created_by} created new site`
        : `${data.created_by} updated to ${data.status.toLowerCase()}`
}
</script>

<template>
    <Timeline :value="props.value" :pt="{
        eventOpposite: {
            class: 'hidden'
        }
    }">
        <template #marker="{item}">
            <span class="size-8 rounded-full z-10 overflow-hidden">
                <img :src="`https://ui-avatars.com/api/?background=random&name=${item.created_by}`" alt="Avatar" />
            </span>
        </template>

        <template #content="{ item }">
            <div class="mb-4">
                <div class="flex gap-1 items-center">
                    <span class="font-bold">{{ setTitle(item) }}</span>
                    <Icon name="Dot" />
                    <span class="text-sm text-surface-500">{{ moment(item.created_at).format('DD MMM YYYY, HH:mm') }}</span>
                </div>
    
                <div class="text-surface-500" v-html="item.remark"></div>
    
                <ul v-if="item.attachments">
                    <li v-for="attachment in item.attachments">
                        <a :href="attachment.url" target="_blank" class="text-blue-700 text-sm dark:text-blue-300 hover:underline">
                            {{ attachment.name }}
                        </a>
                    </li>
                </ul>
            </div>
        </template>
    </Timeline>
</template>