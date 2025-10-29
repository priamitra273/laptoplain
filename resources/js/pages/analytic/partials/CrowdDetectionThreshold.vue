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
    (event: 'update:modelValue', value: CrowdDetectionThreshold): void;
}>();

const thresholds = ref<CrowdDetectionThreshold>({
    people: props.modelValue?.people ?? null,
    street_vendor: props.modelValue?.street_vendor ?? null,
    vehicle: props.modelValue?.vehicle ?? null
});

for (const key in thresholds.value) {
    watch(() => thresholds.value[key], () => {
        emits('update:modelValue', {...thresholds.value})
        delete props.errors[`threshold.${key}`]
    })
}
</script>

<template>
    <Heading title="Crowd Detection Threshold" />

    <div class="grid grid-cols-3 gap-6">
        <div class="flex flex-col gap-1">
            <Label>People</Label>
            <InputNumber v-model="thresholds.people" fluid placeholder="Enter crowd people" />
            <InputError :message="props.errors['threshold.people']" />
        </div>

        <div class="flex flex-col gap-1">
            <Label>Vehicle</Label>
            <InputNumber v-model="thresholds.vehicle" fluid placeholder="Enter crowd vehicle" />
            <InputError :message="props.errors['threshold.vehicle']" />
        </div>

        <div class="flex flex-col gap-1">
            <Label>Street Vendor</Label>
            <InputNumber v-model="thresholds.street_vendor" fluid placeholder="Enter crowd street vendor" />
            <InputError :message="props.errors['threshold.street_vendor']" />
        </div>
    </div>
</template>
