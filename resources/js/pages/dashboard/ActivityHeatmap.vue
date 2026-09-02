<script setup lang="ts">
import { CalendarHeatmap } from 'vue3-calendar-heatmap';
import 'vue3-calendar-heatmap/dist/style.css';

withDefaults(
    defineProps<{
        values: { date: string; count: number }[];
        // Indeks 0 untuk hari tanpa data, 1 untuk hari bernilai nol, sisanya tingkat
        // intensitas. Dijadikan prop supaya nanti gampang ditukar saat tema gelap/terang
        // dikerjakan — tinggal kirim palet lain, komponennya tidak perlu diubah.
        rangeColor?: string[];
    }>(),
    {
        rangeColor: () => ['#1e293b', '#1e293b', '#3730a3', '#4338ca', '#6366f1', '#818cf8'],
    },
);
</script>

<template>
    <div class="overflow-x-auto">
        <CalendarHeatmap
            :values="values"
            :end-date="new Date()"
            :range-color="rangeColor"
            :round="2"
            tooltip-unit="task events"
        />
    </div>
</template>
