<script setup lang="ts">
import { Attachment } from '@/pages/site/type';
import { computed, ref } from 'vue';

interface Props {
    value: Attachment[]
}

const props = withDefaults(defineProps<Props>(), {
    value: () => []
})

const images = computed(() => props.value);

const activeIndex = ref(0);
const responsiveOptions = ref([
    {
        breakpoint: '1024px',
        numVisible: 5
    },
    {
        breakpoint: '768px',
        numVisible: 3
    },
    {
        breakpoint: '560px',
        numVisible: 1
    }
]);

const displayCustom = ref(false);

const imageClick = (index: number) => {
    activeIndex.value = index;
    displayCustom.value = true;
};
</script>

<template>
    <Galleria v-model:activeIndex="activeIndex" v-model:visible="displayCustom" :value="images"
        :responsiveOptions="responsiveOptions" :numVisible="7" containerStyle="max-width: 850px" :circular="true"
        :fullScreen="true" :showItemNavigators="true" :showThumbnails="false" key="url">
        <template #item="slotProps">
            <img :src="slotProps.item.url" :alt="slotProps.item.name" class="max-h-[90vh]" />
        </template>

        <template #thumbnail="slotProps">
            <img :src="slotProps.item.url" :alt="slotProps.item.name" style="display: block" />
        </template>
    </Galleria>

    <div class="grid grid-cols-12 gap-4" style="max-width: 400px">
        <div v-for="(image, index) of images" :key="index" class="col-span-4">
            <img :src="image.url" :alt="image.name" style="cursor: pointer" @click="imageClick(index)" />
        </div>
    </div>
</template>