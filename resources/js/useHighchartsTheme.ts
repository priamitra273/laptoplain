// src/composables/useHighchartsTheme.ts
import Highcharts from 'highcharts';

const cssVar = (name: string) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

export function applyHighchartsTheme() {
    Highcharts.setOptions({
        chart: {
            backgroundColor: cssVar('--surface-card'),
        },
        title: {
            style: {
                color: cssVar('--text-color'),
            },
        },
        xAxis: {
            labels: {
                style: { color: cssVar('--text-color') },
            },
            gridLineColor: cssVar('--surface-border'),
        },
        yAxis: {
            labels: {
                style: { color: cssVar('--text-color') },
            },
            gridLineColor: cssVar('--surface-border'),
        },
        tooltip: {
            backgroundColor: cssVar('--surface-ground'),
            style: { color: cssVar('--text-color') },
            borderColor: cssVar('--surface-border'),
        },
        credits: { enabled: false },
        colors: [cssVar('--primary-color'), cssVar('--surface-700'), cssVar('--surface-500'), cssVar('--surface-300')],
    });
}

// Observe perubahan class dark/light
export function watchThemeChanges(callback: () => void) {
    const observer = new MutationObserver(() => callback());
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}
