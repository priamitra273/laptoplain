import type { DonutChartSegment } from './types';

export function normalizeDonutSegments(segments: DonutChartSegment[], emptyColor: string) {
    const hasData = segments.some((segment) => segment.value > 0);

    return {
        hasData,
        data: hasData ? segments : [{ label: 'No data', value: 1, color: emptyColor }],
    };
}
