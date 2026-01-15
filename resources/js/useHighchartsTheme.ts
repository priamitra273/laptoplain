// resources/js/useHighchartsTheme.ts
import Highcharts from 'highcharts';

const cssVar = (name: string) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

export function applyHighchartsTheme() {
    Highcharts.setOptions({
        chart: {
            backgroundColor: cssVar('--surface-card'),
        },
        title: {
            style: { color: cssVar('--text-color') },
        },
        xAxis: {
            labels: { style: { color: cssVar('--text-color') } },
            gridLineColor: cssVar('--surface-border'),
        },
        yAxis: {
            labels: { style: { color: cssVar('--text-color') } },
            gridLineColor: cssVar('--surface-border'),
        },
        tooltip: {
            backgroundColor: cssVar('--surface-ground'),
            borderColor: cssVar('--surface-border'),
            style: { color: cssVar('--text-color') },
        },
        credits: { enabled: false },
        colors: [cssVar('--primary-color'), cssVar('--surface-700'), cssVar('--surface-500'), cssVar('--surface-300')],
    });
}

// pantau perubahan tema PrimeVue (via class .dark)
export function watchThemeChanges(callback: () => void) {
    const observer = new MutationObserver(() => callback());
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
}
