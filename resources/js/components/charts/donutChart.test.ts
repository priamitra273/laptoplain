import { describe, expect, it } from 'vitest';
import { normalizeDonutSegments } from './donutChart';

describe('normalizeDonutSegments', () => {
    it('mempertahankan segmen ketika donut memiliki data', () => {
        const segments = [{ label: 'Active', value: 3, color: '#6366f1' }];

        expect(normalizeDonutSegments(segments, '#eeeeee')).toEqual({ hasData: true, data: segments });
    });

    it('memberikan cincin netral ketika seluruh nilai kosong', () => {
        expect(normalizeDonutSegments([{ label: 'Active', value: 0, color: '#6366f1' }], '#eeeeee')).toEqual({
            hasData: false,
            data: [{ label: 'No data', value: 1, color: '#eeeeee' }],
        });
    });
});
