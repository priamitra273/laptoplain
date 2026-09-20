import { describe, expect, it } from 'vitest';
import TaskTypeBadge from './TaskTypeBadge.vue';

describe('TaskTypeBadge', () => {
    it('can be imported as a shared Vue component', () => {
        expect(TaskTypeBadge).toBeDefined();
    });
});
