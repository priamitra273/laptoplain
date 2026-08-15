import { describe, expect, it } from 'vitest';
import { taskCategoryColorByName, taskCategoryIcon } from './useTaskCategoryStyle';

describe('taskCategoryIcon', () => {
    it('prefers an explicit icon', () => {
        expect(taskCategoryIcon({ id: '1', name: 'Epic', icon: 'pi pi-star' })).toBe('pi pi-star');
    });

    it('falls back to the name map', () => {
        expect(taskCategoryIcon({ id: '1', name: 'Epic' })).toBe('pi pi-bolt');
        expect(taskCategoryIcon({ id: '2', name: 'Story' })).toBe('pi pi-book');
    });

    it('defaults to a tag icon', () => {
        expect(taskCategoryIcon(null)).toBe('pi pi-tag');
        expect(taskCategoryIcon({ id: '3', name: 'Unknown' })).toBe('pi pi-tag');
    });
});

describe('taskCategoryColorByName', () => {
    it('maps a known name', () => {
        expect(taskCategoryColorByName({ id: '1', name: 'Story' })).toBe('#16a34a');
        expect(taskCategoryColorByName({ id: '2', name: 'Epic' })).toBe('#7c3aed');
    });

    it('defaults to slate', () => {
        expect(taskCategoryColorByName(null)).toBe('#64748b');
        expect(taskCategoryColorByName({ id: '3', name: 'Nope' })).toBe('#64748b');
    });
});
