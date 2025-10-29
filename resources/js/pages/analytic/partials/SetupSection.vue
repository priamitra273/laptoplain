<script setup lang="ts">
import { InertiaForm } from '@inertiajs/vue3';
import { Analytic, Category, Form } from '../type';
import Heading from '@/components/Heading.vue';
import Label from '@/components/ui/label/Label.vue';
import InputError from '@/components/InputError.vue';
import { AnalyticServer, Department } from '@/types';
import { computed, onMounted, reactive, ref } from 'vue';
import WaterLevelThreshold from './WaterLevelThreshold.vue';
import WaterSurfaceThreshold from './WaterSurfaceThreshold.vue';
import CrowdDetectionThreshold from './CrowdDetectionThreshold.vue';
import { useResizeObserver, watchDebounced } from '@vueuse/core';

interface Props {
    analytic: Analytic;
    form: InertiaForm<Form>;
    departments: Department[];
    categories: Category[];
    servers: AnalyticServer[];
}

interface Position {
    x: number;
    y: number;
}

const props = defineProps<Props>();

const canvas = ref<HTMLCanvasElement>();
const img = ref<HTMLImageElement>();

const canvasWidth = ref(854);
const canvasHeight = ref(480);

const canvasScale = computed(() => ({
    width: canvas.value ? canvas.value.width / canvas.value.clientWidth : 1,
    height: canvas.value ? canvas.value.height / canvas.value.clientHeight : 1
}));

const imageLoaded = ref(false);

let clicks = reactive<Position[]>([])

const lisence = computed(() => props.servers.find(item => item.uuid === props.form.server_uuid)?.lisence);

const isPolygonEnabled = computed(() => {
    const value = props.form.category_uuid;

    return value === category.water_level || value === category.water_surface || value === category.crowd_detection
})

const category = {
    object_counting: '39976be9-bfa2-49ee-8bc7-fc9af92d9eee',
    water_level: '028a09cd-c2d8-4239-b163-2234ba8f3d68',
    water_surface: 'ce55d7c8-9f44-4efd-a5e8-dcf2435cc6f8',
    crowd_detection: '50e33358-b305-4c06-9bb2-f2c3768a71a0'
}

function loadImgToCanvas() {
    const ctx = canvas.value?.getContext('2d');
    const width = canvas.value?.width ?? canvas.value?.parentElement?.clientWidth;
    const height = canvas.value?.height ?? canvasHeight.value;

    img.value = new Image();

    img.value.src = route('analytic.thumbnail', props.analytic.uuid);

    img.value.onload = () => {
        imageLoaded.value = true;

        if (img.value) {
            ctx?.drawImage(img.value, 0, 0, width ?? canvasWidth.value, height);

            clicks = props.form.polygon.map((item) => ({
                x: item[0] * canvasWidth.value,
                y: item[1] * canvasHeight.value
            })) ?? [];

            drawPolygon();
            drawPoints();
        }
    }

    img.value.onerror = (err) => {
        console.log(err)
    }

    ctx?.drawImage(img.value, 0, 0, width ?? canvasWidth.value, height)
}

function drawPolygon() {
    const context = canvas.value?.getContext('2d');

    if (context) {
        context.fillStyle = 'rgba(100,100,100,0.5)';
        context.strokeStyle = "#df4b26";
        context.lineWidth = 1;

        const width = canvas.value?.width;
        const height = canvas.value?.height;

        props.form.polygon = clicks.map(item => [
            item.x / (width ?? canvasWidth.value),
            item.y / (height ?? canvasHeight.value)
        ]);

        context.beginPath();

        if (clicks.length) {
            context.moveTo(clicks[0].x, clicks[0].y);
        }

        for (var i = 1; i < clicks.length; i++) {
            context.lineTo(clicks[i].x, clicks[i].y);
        }

        // context.closePath();
        // context.fill();
        context.stroke();
    }
};

function drawPoints() {
    const context = canvas.value?.getContext('2d');

    if (context) {
        context.strokeStyle = "#df4b26";
        context.lineJoin = "round";
        context.lineWidth = 5;

        for (var i = 0; i < clicks.length; i++) {
            context.beginPath();
            context.arc(clicks[i].x, clicks[i].y, 3, 0, 2 * Math.PI, false);
            context.fillStyle = '#ffffff';
            context.fill();
            context.lineWidth = 5;
            context.stroke();
        }
    }
};

function setPosition(e: MouseEvent) {
    if (!isPolygonEnabled.value) {
        return;
    }

    if (props.form.category_uuid === category.water_level && clicks.length >= 2) {
        return
    }

    clicks.push({
        x: e.offsetX * canvasScale.value.width,
        y: e.offsetY * canvasScale.value.height
    });

    drawPolygon();
    drawPoints();
}

function clearPolygon() {
    clicks = []

    loadImgToCanvas();
    drawPolygon();
    drawPoints();
}

onMounted(() => {
    loadImgToCanvas();
})

useResizeObserver(canvas, (entries) => {
    const entry = entries[0]
    const { width, height } = entry.contentRect

    canvasWidth.value = width;
    canvasHeight.value = height;

    imageLoaded.value = false;
});

watchDebounced(canvasWidth, () => {
    clicks = props.form.polygon.map((item) => ({
        x: item[0] * canvasWidth.value,
        y: item[1] * canvasHeight.value
    }));

    loadImgToCanvas()
}, { debounce: 500, maxWait: 1000 })

</script>

<template>
    <Card class="overflow-hidden">
        <template #header>
            <Skeleton :height="canvasHeight + 'px'" class="w-full" v-if="!imageLoaded"></Skeleton>
            <canvas ref="canvas" class="w-full aspect-video" :width="canvasWidth" :height="canvasHeight"
                @click="setPosition"></canvas>
        </template>

        <template #footer>
            <Button label="Clear Polygon" icon="pi pi-eraser" severity="danger" outlined :disabled="!isPolygonEnabled"
                @click="clearPolygon" />
        </template>
    </Card>

    <Card class="overflow-hidden">
        <template #content>
            <Heading title="Setup Analytic" description="Please fill the required fileds." />

            <div class="grid grid-cols-2 gap-6">
                <div class="flex flex-col gap-1">
                    <Label>Department</Label>
                    <Select v-model="form.department_uuid" :options="departments" option-label="name"
                        option-value="uuid" placeholder="Select a department" filter />
                    <InputError :message="form.errors.department_uuid" />
                </div>

                <div class="flex flex-col gap-1">
                    <Label>Category</Label>
                    <Select v-model="form.category_uuid" :options="categories" option-label="name" option-value="uuid"
                        placeholder="Select a category" />
                    <InputError :message="form.errors.category_uuid" />
                </div>

                <div class="flex flex-col gap-1">
                    <Label>Server</Label>
                    <Select v-model="form.server_uuid" :options="servers" option-label="ip_address" option-value="uuid"
                        placeholder="Select a server" filter />
                    <InputError :message="form.errors.server_uuid" />
                </div>

                <div class="flex flex-col gap-1">
                    <Label>Lisence</Label>
                    <InputText :value="lisence" fluid disabled />
                </div>

                <Divider type="dashed" class="col-span-2" />

                <div class="col-span-2">
                    <WaterLevelThreshold v-model="form.threshold" :errors="form.errors"
                        v-if="form.category_uuid === category.water_level" />
                    <WaterSurfaceThreshold v-model="form.threshold" :errors="form.errors"
                        v-else-if="form.category_uuid === category.water_surface" />
                    <CrowdDetectionThreshold v-model="form.threshold" :errors="form.errors"
                        v-else-if="form.category_uuid === category.crowd_detection" />
                </div>
            </div>
        </template>
    </Card>
</template>