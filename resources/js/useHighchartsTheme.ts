// resources/js/useHighchartsTheme.ts
import Highcharts from 'highcharts';

const cssVar = (name: string) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

export function applyHighchartsTheme() {
    Highcharts.setOptions({
        chart: {
            backgroundColor: cssVar('--surface-card'),
            style: {
                fontFamily: cssVar('--font-family') || 'inherit',
            },
        },
        title: {
            style: {
                color: cssVar('--text-color'),
                fontWeight: '600',
            },
        },
        subtitle: {
            style: {
                color: cssVar('--text-color-secondary') || cssVar('--text-color'),
            },
        },
        xAxis: {
            labels: {
                style: {
                    color: cssVar('--text-color'),
                },
            },
            gridLineColor: cssVar('--surface-border'),
            lineColor: cssVar('--surface-border'),
            tickColor: cssVar('--surface-border'),
            title: {
                style: {
                    color: cssVar('--text-color'),
                },
            },
        },
        yAxis: {
            labels: {
                style: {
                    color: cssVar('--text-color'),
                },
            },
            gridLineColor: cssVar('--surface-border'),
            lineColor: cssVar('--surface-border'),
            tickColor: cssVar('--surface-border'),
            title: {
                style: {
                    color: cssVar('--text-color'),
                },
            },
        },
        tooltip: {
            backgroundColor: cssVar('--surface-ground') || cssVar('--surface-card'),
            borderColor: cssVar('--surface-border'),
            borderRadius: 6,
            style: {
                color: cssVar('--text-color'),
            },
        },
        legend: {
            itemStyle: {
                color: cssVar('--text-color'),
            },
            itemHoverStyle: {
                color: cssVar('--primary-color'),
            },
            itemHiddenStyle: {
                color: cssVar('--text-color-secondary') || '#999',
            },
        },
        plotOptions: {
            series: {
                borderColor: cssVar('--surface-border'),
                dataLabels: {
                    color: cssVar('--text-color'),
                    style: {
                        textOutline: 'none',
                        fontWeight: 'normal',
                    },
                },
            },
            gantt: {
                borderColor: cssVar('--surface-border'),
                connectors: {
                    lineColor: cssVar('--primary-color'),
                },
            },
        },
        navigator: {
            handles: {
                backgroundColor: cssVar('--surface-card'),
                borderColor: cssVar('--surface-border'),
            },
            maskFill: cssVar('--primary-color') + '33', // 20% opacity
            outlineColor: cssVar('--surface-border'),
            series: {
                color: cssVar('--primary-color'),
                lineColor: cssVar('--primary-color'),
            },
            xAxis: {
                gridLineColor: cssVar('--surface-border'),
                labels: {
                    style: {
                        color: cssVar('--text-color'),
                    },
                },
            },
        },
        scrollbar: {
            barBackgroundColor: cssVar('--surface-300') || '#ccc',
            barBorderColor: cssVar('--surface-border'),
            buttonBackgroundColor: cssVar('--surface-200') || '#ddd',
            buttonBorderColor: cssVar('--surface-border'),
            rifleColor: cssVar('--text-color'),
            trackBackgroundColor: cssVar('--surface-100') || '#f2f2f2',
            trackBorderColor: cssVar('--surface-border'),
        },
        rangeSelector: {
            buttonTheme: {
                fill: cssVar('--surface-card'),
                stroke: cssVar('--surface-border'),
                style: {
                    color: cssVar('--text-color'),
                },
                states: {
                    hover: {
                        fill: cssVar('--surface-hover') || cssVar('--surface-100'),
                        stroke: cssVar('--primary-color'),
                        style: {
                            color: cssVar('--primary-color'),
                        },
                    },
                    select: {
                        fill: cssVar('--primary-color'),
                        stroke: cssVar('--primary-color'),
                        style: {
                            color: '#ffffff',
                        },
                    },
                },
            },
            inputBoxBorderColor: cssVar('--surface-border'),
            inputStyle: {
                backgroundColor: cssVar('--surface-card'),
                color: cssVar('--text-color'),
            },
            labelStyle: {
                color: cssVar('--text-color'),
            },
        },
        credits: {
            enabled: false,
        },
        colors: [
            cssVar('--primary-color'),
            cssVar('--blue-500') || '#3B82F6',
            cssVar('--green-500') || '#10B981',
            cssVar('--orange-500') || '#F97316',
            cssVar('--purple-500') || '#A855F7',
            cssVar('--pink-500') || '#EC4899',
            cssVar('--teal-500') || '#14B8A6',
            cssVar('--indigo-500') || '#6366F1',
        ],
    });
}

// Pantau perubahan tema PrimeVue (via class .dark)
export function watchThemeChanges(callback: () => void) {
    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                callback();
                break;
            }
        }
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });

    // Return cleanup function
    return () => {
        observer.disconnect();
    };
}

// Helper untuk mengecek apakah dark mode aktif
export function isDarkMode(): boolean {
    return document.documentElement.classList.contains('dark');
}
