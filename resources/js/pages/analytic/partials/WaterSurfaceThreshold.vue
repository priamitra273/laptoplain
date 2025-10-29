<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { CrowdDetectionThreshold, WaterLevelThreshold, WaterSurfaceThreshold } from '../type';
import { computed, ref, watch } from 'vue';
import Label from '@/components/ui/label/Label.vue';
import InputError from '@/components/InputError.vue';
import { FormDataConvertible } from '@inertiajs/core';

type FormDataType = Record<string, FormDataConvertible>;

interface TForm extends FormDataType {
    
}

interface Props {
    modelValue: WaterLevelThreshold | WaterSurfaceThreshold | CrowdDetectionThreshold | null;
    errors: Partial<Record<keyof TForm, string>>;
}

const props = defineProps<Props>();

const emits = defineEmits<{
    (event: 'update:modelValue', value: WaterSurfaceThreshold): void;
}>();

const thresholds = ref<WaterSurfaceThreshold>({
    level_1: props.modelValue?.level_1 ?? null,
    level_2: props.modelValue?.level_2 ?? null,
    level_3: props.modelValue?.level_3 ?? null
});

for (const key in thresholds.value) {
    watch(() => thresholds.value[key], () => {
        emits('update:modelValue', {...thresholds.value})
        delete props.errors[`threshold.${key}`]
    })
}
</script>

<template>
    <Heading title="Water Surface Threshold" />

    <div class="grid grid-cols-3 gap-6">
        <div class="flex flex-col gap-1">
            <Label>Level 1</Label>
            <InputNumber v-model="thresholds.level_1" fluid placeholder="Enter threshold level 1" :min="0" :max="100" />
            <InputError :message="props.errors['threshold.level_1']" />
        </div>

        <div class="flex flex-col gap-1">
            <Label>Level 2</Label>
            <InputNumber v-model="thresholds.level_2" fluid placeholder="Enter threshold level 2" :min="thresholds.level_1 ?? 0" :max="100" />
            <InputError :message="props.errors['threshold.level_2']" />
        </div>

        <div class="flex flex-col gap-1">
            <Label>Level 3</Label>
            <InputNumber v-model="thresholds.level_3" fluid placeholder="Enter threshold level 3" :min="thresholds.level_2 ?? 0" :max="100" />
            <InputError :message="props.errors['threshold.level_3']" />
        </div>
    </div>
</template>
